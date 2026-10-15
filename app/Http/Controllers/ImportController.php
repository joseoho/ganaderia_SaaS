<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\Venta;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportController extends Controller
{
    /** Minutos que se conservan las filas de una importacion a medio hacer. */
    private const MINUTOS_FILAS = 30;

    protected ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Guarda las filas leidas del archivo entre el paso de mapeo y el de
     * confirmacion.
     *
     * NO se guardan en la sesion: con SESSION_DRIVER=database el contenido
     * entero de la sesion se escribe y se RELEE EN CADA PETICION del usuario,
     * asi que un CSV de miles de filas penalizaba todas las paginas que visitara
     * despues durante las dos horas de vida de la sesion. En la cache el
     * payload solo se lee cuando se pide de verdad, y caduca solo.
     *
     * La clave incluye el usuario para que dos personas no se pisen el archivo.
     */
    private function guardarFilas(string $tipo, Collection $filas): void
    {
        Cache::put($this->claveFilas($tipo), $filas, now()->addMinutes(self::MINUTOS_FILAS));
    }

    private function leerFilas(string $tipo): Collection
    {
        return collect(Cache::get($this->claveFilas($tipo), []));
    }

    private function claveFilas(string $tipo): string
    {
        return 'importacion:'.Auth::id().':'.$tipo;
    }

    /**
     * Ejecuta la importacion fila a fila, contando lo que entra y lo que no.
     *
     * Cada fila va en su propia transaccion y con su propio try. Una fila
     * invalida (una fecha mal escrita, por ejemplo) se cuenta como omitida y el
     * resto del archivo sigue entrando: antes, esa fila tumbaba la importacion
     * entera con un error 500 y dejaba dentro lo que ya se hubiera guardado.
     *
     * @param  Collection<int, mixed>  $rows
     * @param  array<string, string>  $map
     * @param  callable(array<string, mixed>): bool  $guardar
     * @return array{0: int, 1: int} [importados, omitidos]
     */
    private function importarFilas(Collection $rows, array $map, callable $guardar): array
    {
        $importados = 0;
        $omitidos = 0;

        foreach ($rows as $row) {
            $data = [];

            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            // Una celda vacia de una hoja de calculo llega como cadena vacia, no
            // como null. Sin normalizarla, ese "" acaba en una columna decimal o
            // en una NOT NULL y revienta. Un 0 de verdad se respeta.
            foreach ($data as $campo => $valor) {
                if (is_string($valor) && trim($valor) === '') {
                    $data[$campo] = null;
                }
            }

            // Las fechas se normalizan AQUI, sin depender del modo de la base de
            // datos. Con el modo estricto apagado (el de este equipo), MariaDB
            // acepta una fecha imposible y la guarda como 0000-00-00 sin avisar;
            // con el estricto encendido (el de produccion) la rechaza. En los dos
            // casos se pierde una fila: aqui se convierte en una fila OMITIDA y
            // contada, y nunca en un dato en cero.
            foreach ($data as $campo => $valor) {
                if (is_string($valor) && (str_starts_with($campo, 'fecha') || $campo === 'proxima_dosis')) {
                    $data[$campo] = $this->normalizarFecha($valor);
                }
            }

            try {
                $guardado = DB::transaction(fn (): bool => $guardar($data));
            } catch (Throwable $e) {
                Log::error('Importacion: fila omitida', [
                    'error' => $e->getMessage(),
                    'datos' => $data,
                ]);

                $omitidos++;

                continue;
            }

            $guardado ? $importados++ : $omitidos++;
        }

        return [$importados, $omitidos];
    }

    /**
     * Convierte una fecha del archivo a AAAA-MM-DD, o null si no es una fecha.
     */
    private function normalizarFecha(string $valor): ?string
    {
        try {
            return Carbon::parse($valor)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Comprueba que la fila traiga las columnas que la tabla exige (NOT NULL).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $obligatorios
     */
    private function faltaAlguno(array $data, array $obligatorios): bool
    {
        foreach ($obligatorios as $campo) {
            if (! isset($data[$campo]) || $data[$campo] === '') {
                return true;
            }
        }

        return false;
    }

    public function index()
    {
        $importados = session('ultimo_importado', 0);

        return view('import.resultado', compact('importados'));
    }

    // ============================
    // ANIMALES
    // ============================
    public function animalesForm()
    {
        return view('import.animales.form');
    }

    public function animalesUpload(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ], [
            // El limite que manda es el de PHP, no el de esta validacion:
            // con upload_max_filesize en 2M el servidor descarta el archivo
            // antes de que la aplicacion lo lea, y el mensaje por defecto
            // ("failed to upload") no explica nada.
            'archivo.uploaded' => 'No se pudo subir el archivo. Si pesa mas de 2 MB, el servidor lo rechaza antes de leerlo (upload_max_filesize). Dividelo o sube ese limite en el php.ini.',
            'archivo.file' => 'No se pudo subir el archivo. Si pesa mas de 2 MB, el servidor lo rechaza antes de leerlo (upload_max_filesize). Dividelo o sube ese limite en el php.ini.',
            'archivo.max' => 'El archivo no puede pasar de 10 MB. Dividelo en varios.',
            'archivo.mimes' => 'Formato no admitido: sube un CSV o un Excel (xlsx o xls).',
            'archivo.required' => 'Selecciona un archivo.',
        ]);

        if (! $request->hasFile('archivo') || ! $request->file('archivo')->isValid()) {
            return back()->with('error', 'Archivo no válido o no recibido.');
        }

        try {
            $rows = $this->importService->leerArchivo($request->file('archivo'));

            if ($rows->isEmpty()) {
                return back()->with('error', 'El archivo está vacío o no se pudo leer.');
            }

            // Los encabezados se leen ANTES de limpiar: limpiarFilas descarta la primera
            // fila (la de encabezados), asi que despues ya no se pueden obtener.
            $headers = $this->importService->obtenerEncabezados($rows);

            $rows = $this->importService->limpiarFilas($rows);

            if ($rows->isEmpty()) {
                return back()->with('error', 'No se encontraron datos válidos en el archivo.');
            }

            if (empty($headers)) {
                return back()->with('error', 'No se detectaron encabezados en el archivo.');
            }

            // Definir campos del sistema para el mapeo de animales
            $camposSistema = [
                'codigo_interno' => 'Código Interno',
                'categoria' => 'Categoría',
                'raza' => 'Raza',
                'tipo' => 'Tipo',
                'fecha_nacimiento' => 'Fecha de Nacimiento',
                'sexo' => 'Sexo',
                'peso_entrada' => 'Peso Entrada',
                'estado' => 'Estado',
            ];

            // Sugerir mapeo automático
            $sugerencias = $this->sugerirMapeo($headers, array_keys($camposSistema));

            $this->guardarFilas('animales', $rows);
            session(['headers_animales' => $headers]);

            return view('import.animales.map', compact('headers', 'camposSistema', 'sugerencias'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en animalesUpload: '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function animalesPreview(Request $request)
    {
        $map = $request->map;
        $rows = $this->leerFilas('animales');

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_animales' => $map]);

        return view('import.animales.preview', compact('preview'));
    }

    public function animalesImport()
    {
        $inquilino = Auth::user()->inquilino_id;

        [$importados, $omitidos] = $this->importarFilas(
            $this->leerFilas('animales'),
            session('map_animales') ?? [],
            function (array $data) use ($inquilino): bool {
                // Sin codigo interno no hay forma de identificar al animal.
                if (empty($data['codigo_interno'])) {
                    return false;
                }

                // updateOrCreate por (ganaderia, codigo): volver a importar el
                // mismo archivo ACTUALIZA los animales en vez de duplicarlos.
                Animal::updateOrCreate(
                    [
                        'inquilino_id' => $inquilino,
                        'codigo_interno' => $data['codigo_interno'],
                    ],
                    array_merge($data, ['inquilino_id' => $inquilino])
                );

                return true;
            }
        );

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', compact('importados', 'omitidos'));
    }

    // ============================
    // PRODUCCIÓN
    // ============================
    public function produccionForm()
    {
        return view('import.produccion.form');
    }

    public function produccionUpload(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ], [
            // El limite que manda es el de PHP, no el de esta validacion:
            // con upload_max_filesize en 2M el servidor descarta el archivo
            // antes de que la aplicacion lo lea, y el mensaje por defecto
            // ("failed to upload") no explica nada.
            'archivo.uploaded' => 'No se pudo subir el archivo. Si pesa mas de 2 MB, el servidor lo rechaza antes de leerlo (upload_max_filesize). Dividelo o sube ese limite en el php.ini.',
            'archivo.file' => 'No se pudo subir el archivo. Si pesa mas de 2 MB, el servidor lo rechaza antes de leerlo (upload_max_filesize). Dividelo o sube ese limite en el php.ini.',
            'archivo.max' => 'El archivo no puede pasar de 10 MB. Dividelo en varios.',
            'archivo.mimes' => 'Formato no admitido: sube un CSV o un Excel (xlsx o xls).',
            'archivo.required' => 'Selecciona un archivo.',
        ]);

        if (! $request->hasFile('archivo') || ! $request->file('archivo')->isValid()) {
            return back()->with('error', 'Archivo no válido o no recibido.');
        }

        try {
            $rows = $this->importService->leerArchivo($request->file('archivo'));

            if ($rows->isEmpty()) {
                return back()->with('error', 'El archivo está vacío o no se pudo leer.');
            }

            // Los encabezados se leen ANTES de limpiar: limpiarFilas descarta la primera
            // fila (la de encabezados), asi que despues ya no se pueden obtener.
            $headers = $this->importService->obtenerEncabezados($rows);

            $rows = $this->importService->limpiarFilas($rows);

            if ($rows->isEmpty()) {
                return back()->with('error', 'No se encontraron datos válidos en el archivo.');
            }

            if (empty($headers)) {
                return back()->with('error', 'No se detectaron encabezados en el archivo.');
            }

            // Definir campos del sistema para producción
            $camposSistema = [
                'animal_id' => 'ID del Animal',
                'fecha' => 'Fecha',
                'litros' => 'Litros (Leche)',
                'turno' => 'Turno',
                'ganancia_diaria' => 'Ganancia Diaria (Carne)',
                'peso' => 'Peso (Carne)',
                'observaciones' => 'Observaciones',
            ];

            $sugerencias = $this->sugerirMapeo($headers, array_keys($camposSistema));

            $this->guardarFilas('produccion', $rows);
            session(['headers_produccion' => $headers]);

            return view('import.produccion.map', compact('headers', 'camposSistema', 'sugerencias'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en produccionUpload: '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function produccionPreview(Request $request)
    {
        $map = $request->map;
        $rows = $this->leerFilas('produccion');

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_produccion' => $map]);

        return view('import.produccion.preview', compact('preview'));
    }

    public function produccionImport()
    {
        $inquilino = Auth::user()->inquilino_id;

        [$importados, $omitidos] = $this->importarFilas(
            $this->leerFilas('produccion'),
            session('map_produccion') ?? [],
            function (array $data) use ($inquilino): bool {
                // Sin animal no se sabe a quien pertenece la produccion.
                if ($this->faltaAlguno($data, ['animal_id', 'fecha'])) {
                    return false;
                }

                $guardado = false;

                // Una misma fila puede traer leche y carne: se crean las dos.
                if (($data['litros'] ?? null) !== null) {
                    ProduccionLeche::create($data + ['inquilino_id' => $inquilino]);
                    $guardado = true;
                }

                if (($data['ganancia_diaria'] ?? null) !== null || ($data['peso'] ?? null) !== null) {
                    ProduccionCarne::create($data + ['inquilino_id' => $inquilino]);
                    $guardado = true;
                }

                return $guardado;
            }
        );

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', compact('importados', 'omitidos'));
    }

    // ============================
    // COMPRAS
    // ============================
    public function comprasForm()
    {
        return view('import.compras.form');
    }

    public function comprasUpload(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ], [
            // El limite que manda es el de PHP, no el de esta validacion:
            // con upload_max_filesize en 2M el servidor descarta el archivo
            // antes de que la aplicacion lo lea, y el mensaje por defecto
            // ("failed to upload") no explica nada.
            'archivo.uploaded' => 'No se pudo subir el archivo. Si pesa mas de 2 MB, el servidor lo rechaza antes de leerlo (upload_max_filesize). Dividelo o sube ese limite en el php.ini.',
            'archivo.file' => 'No se pudo subir el archivo. Si pesa mas de 2 MB, el servidor lo rechaza antes de leerlo (upload_max_filesize). Dividelo o sube ese limite en el php.ini.',
            'archivo.max' => 'El archivo no puede pasar de 10 MB. Dividelo en varios.',
            'archivo.mimes' => 'Formato no admitido: sube un CSV o un Excel (xlsx o xls).',
            'archivo.required' => 'Selecciona un archivo.',
        ]);

        if (! $request->hasFile('archivo') || ! $request->file('archivo')->isValid()) {
            return back()->with('error', 'Archivo no válido o no recibido.');
        }

        try {
            $rows = $this->importService->leerArchivo($request->file('archivo'));

            if ($rows->isEmpty()) {
                return back()->with('error', 'El archivo está vacío o no se pudo leer.');
            }

            // Los encabezados se leen ANTES de limpiar: limpiarFilas descarta la primera
            // fila (la de encabezados), asi que despues ya no se pueden obtener.
            $headers = $this->importService->obtenerEncabezados($rows);

            $rows = $this->importService->limpiarFilas($rows);

            if ($rows->isEmpty()) {
                return back()->with('error', 'No se encontraron datos válidos en el archivo.');
            }

            if (empty($headers)) {
                return back()->with('error', 'No se detectaron encabezados en el archivo.');
            }

            // Definir campos del sistema para compras
            $camposSistema = [
                'fecha' => 'Fecha',
                'descripcion' => 'Descripción',
                'monto_total' => 'Monto Total',
                'proveedor' => 'Proveedor',
            ];

            $sugerencias = $this->sugerirMapeo($headers, array_keys($camposSistema));

            $this->guardarFilas('compras', $rows);
            session(['headers_compras' => $headers]);

            return view('import.compras.map', compact('headers', 'camposSistema', 'sugerencias'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en comprasUpload: '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function comprasPreview(Request $request)
    {
        $map = $request->map;
        $rows = $this->leerFilas('compras');

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_compras' => $map]);

        return view('import.compras.preview', compact('preview'));
    }

    public function comprasImport()
    {
        $inquilino = Auth::user()->inquilino_id;

        [$importados, $omitidos] = $this->importarFilas(
            $this->leerFilas('compras'),
            session('map_compras') ?? [],
            function (array $data) use ($inquilino): bool {
                // La tabla exige estas cuatro columnas. Una fila a la que le
                // falte cualquiera no se inventa: se omite y se avisa.
                if ($this->faltaAlguno($data, ['fecha', 'proveedor', 'descripcion', 'monto_total'])) {
                    return false;
                }

                Compra::create($data + ['inquilino_id' => $inquilino]);

                return true;
            }
        );

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', compact('importados', 'omitidos'));
    }

    // ============================
    // VENTAS
    // ============================
    public function ventasForm()
    {
        return view('import.ventas.form');
    }

    public function ventasUpload(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
        ], [
            // El limite que manda es el de PHP, no el de esta validacion:
            // con upload_max_filesize en 2M el servidor descarta el archivo
            // antes de que la aplicacion lo lea, y el mensaje por defecto
            // ("failed to upload") no explica nada.
            'archivo.uploaded' => 'No se pudo subir el archivo. Si pesa mas de 2 MB, el servidor lo rechaza antes de leerlo (upload_max_filesize). Dividelo o sube ese limite en el php.ini.',
            'archivo.file' => 'No se pudo subir el archivo. Si pesa mas de 2 MB, el servidor lo rechaza antes de leerlo (upload_max_filesize). Dividelo o sube ese limite en el php.ini.',
            'archivo.max' => 'El archivo no puede pasar de 10 MB. Dividelo en varios.',
            'archivo.mimes' => 'Formato no admitido: sube un CSV o un Excel (xlsx o xls).',
            'archivo.required' => 'Selecciona un archivo.',
        ]);

        if (! $request->hasFile('archivo') || ! $request->file('archivo')->isValid()) {
            return back()->with('error', 'Archivo no válido o no recibido.');
        }

        try {
            $rows = $this->importService->leerArchivo($request->file('archivo'));

            if ($rows->isEmpty()) {
                return back()->with('error', 'El archivo está vacío o no se pudo leer.');
            }

            // Los encabezados se leen ANTES de limpiar: limpiarFilas descarta la primera
            // fila (la de encabezados), asi que despues ya no se pueden obtener.
            $headers = $this->importService->obtenerEncabezados($rows);

            $rows = $this->importService->limpiarFilas($rows);

            if ($rows->isEmpty()) {
                return back()->with('error', 'No se encontraron datos válidos en el archivo.');
            }

            if (empty($headers)) {
                return back()->with('error', 'No se detectaron encabezados en el archivo.');
            }

            // Definir campos del sistema para ventas
            $camposSistema = [
                'cliente' => 'Cliente',
                'fecha' => 'Fecha',
                'descripcion' => 'Descripción',
                'monto_total' => 'Monto Total',
            ];

            $sugerencias = $this->sugerirMapeo($headers, array_keys($camposSistema));

            $this->guardarFilas('ventas', $rows);
            session(['headers_ventas' => $headers]);

            return view('import.ventas.map', compact('headers', 'camposSistema', 'sugerencias'));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en ventasUpload: '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function ventasPreview(Request $request)
    {
        $map = $request->map;
        $rows = $this->leerFilas('ventas');

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_ventas' => $map]);

        return view('import.ventas.preview', compact('preview'));
    }

    public function ventasImport()
    {
        $inquilino = Auth::user()->inquilino_id;

        [$importados, $omitidos] = $this->importarFilas(
            $this->leerFilas('ventas'),
            session('map_ventas') ?? [],
            function (array $data) use ($inquilino): bool {
                // La tabla exige estas cuatro columnas.
                if ($this->faltaAlguno($data, ['fecha', 'cliente', 'descripcion', 'monto_total'])) {
                    return false;
                }

                Venta::create($data + ['inquilino_id' => $inquilino]);

                return true;
            }
        );

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', compact('importados', 'omitidos'));
    }

    // ============================
    // MÉTODO AUXILIAR PARA SUGERIR MAPEO
    // ============================
    /**
     * Sugiere automáticamente el mapeo de columnas basado en similitud de nombres.
     */
    private function sugerirMapeo(array $headers, array $camposEsperados): array
    {
        $sugerencias = [];

        foreach ($camposEsperados as $campo) {
            // Buscar coincidencia exacta (case-insensitive)
            $match = array_filter($headers, function ($h) use ($campo) {
                return strtolower(trim($h)) === strtolower(trim($campo));
            });

            if (! empty($match)) {
                $sugerencias[$campo] = reset($match);

                continue;
            }

            // Buscar coincidencia parcial
            foreach ($headers as $header) {
                if (stripos($header, $campo) !== false || stripos($campo, $header) !== false) {
                    $sugerencias[$campo] = $header;
                    break;
                }
            }
        }

        return $sugerencias;
    }
}
