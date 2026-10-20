<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\Venta;
use App\Services\ImportService;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportController extends Controller
{
    /** Campos que son una fecha: se normalizan a AAAA-MM-DD. */
    private const CAMPOS_FECHA = ['fecha', 'fecha_nacimiento', 'fecha_aplicacion', 'fecha_envio', 'fecha_fin', 'proxima_dosis'];

    /** Campos numericos: se normaliza el separador decimal (1.500,50 -> 1500.50). */
    private const CAMPOS_NUMERO = ['monto_total', 'monto', 'peso', 'peso_entrada', 'peso_anterior', 'ganancia_diaria', 'litros', 'litros_anteriores', 'cantidad', 'minimo', 'dosis', 'tamano', 'carga_animal'];

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
     * Cada fila va en su propia transaccion y con su propio control: una fila
     * invalida se cuenta como omitida CON SU MOTIVO y el resto del archivo sigue
     * entrando. Antes, una sola fila mala tumbaba la importacion entera con un
     * error 500 y dejaba dentro lo que ya se hubiera guardado.
     *
     * @param  Collection<int, mixed>  $rows
     * @param  array<string, string>  $map
     * @param  callable(array<string, mixed>): ?string  $guardar  null si se guardo; si no, el motivo.
     * @return array{0: int, 1: int} [importados, omitidos]
     */
    private function importarFilas(Collection $rows, array $map, callable $guardar): array
    {
        $importados = 0;
        $omitidos = 0;
        $motivos = [];

        foreach ($rows as $numero => $row) {
            $data = [];

            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            // Una celda vacia de una hoja de calculo llega como cadena vacia, no
            // como null: sin normalizarla, ese "" acaba en una columna decimal o
            // en una NOT NULL y revienta. Un 0 de verdad se respeta.
            foreach ($data as $campo => $valor) {
                if (is_string($valor) && trim($valor) === '') {
                    $data[$campo] = null;
                }
            }

            // Fechas e importes se traducen AQUI, en el codigo: asi da igual el
            // formato con el que los escriba el usuario y no depende del motor.
            foreach ($data as $campo => $valor) {
                if ($valor === null) {
                    continue;
                }

                if (in_array($campo, self::CAMPOS_FECHA, true)) {
                    $data[$campo] = $this->normalizarFecha((string) $valor);
                } elseif (in_array($campo, self::CAMPOS_NUMERO, true)) {
                    $data[$campo] = $this->normalizarNumero($valor);
                }
            }

            try {
                $motivo = DB::transaction(fn (): ?string => $guardar($data));
            } catch (Throwable $e) {
                Log::error('Importacion: fila omitida', ['error' => $e->getMessage(), 'datos' => $data]);
                $motivo = 'no se pudo guardar (revisa los valores de la fila)';
            }

            if ($motivo === null) {
                $importados++;

                continue;
            }

            $omitidos++;

            // Se guardan los primeros motivos para poder decir en pantalla QUE
            // falla, en vez de un aviso generico que no ayuda a nadie.
            if (count($motivos) < 10) {
                $motivos[] = 'Fila '.($numero + 2).': '.$motivo;
            }
        }

        session(['import_motivos' => $motivos]);

        return [$importados, $omitidos];
    }

    /**
     * Convierte una fecha del archivo a AAAA-MM-DD, o null si no es una fecha.
     *
     * Se prueban los formatos que la gente escribe de verdad, con dia/mes/año
     * PRIMERO porque es el orden de toda la vida en español. Importante: la base
     * de datos, por su cuenta, interpreta "01/02/2026" como el 2 de enero
     * (estilo estadounidense), asi que sin normalizarlo aqui el dato entraria con
     * el dia y el mes cambiados y sin ningun aviso.
     */
    private function normalizarFecha(string $valor): ?string
    {
        $valor = trim($valor);

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'd.m.Y', 'd/m/y'] as $formato) {
            $fecha = DateTimeImmutable::createFromFormat('!'.$formato, $valor);
            $errores = DateTimeImmutable::getLastErrors();

            if ($fecha !== false && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))) {
                return $fecha->format('Y-m-d');
            }
        }

        // Excel guarda las fechas como numero de serie: dias desde 1899-12-30.
        if (is_numeric($valor)) {
            $serie = (float) $valor;

            if ($serie > 20000 && $serie < 80000) {
                return (new DateTimeImmutable('1899-12-30'))->modify('+'.(int) $serie.' days')->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Traduce un importe o una cantidad escrita en español.
     *
     * "1.500,50" -> 1500.50 · "$ 2.500.000" -> 2500000 · "1500,50" -> 1500.50.
     * La decision se toma por la POSICION del ultimo separador: si detras quedan
     * una o dos cifras es el decimal; si no, todos los separadores son de miles.
     */
    private function normalizarNumero(mixed $valor): ?string
    {
        $texto = preg_replace('/[^0-9,.\-]/', '', (string) $valor) ?? '';

        if ($texto === '' || $texto === '-') {
            return null;
        }

        $coma = strrpos($texto, ',');
        $punto = strrpos($texto, '.');
        $ultimo = max($coma === false ? -1 : $coma, $punto === false ? -1 : $punto);

        if ($ultimo >= 0) {
            $decimales = strlen($texto) - $ultimo - 1;

            if ($decimales > 0 && $decimales <= 2) {
                return str_replace([',', '.'], '', substr($texto, 0, $ultimo)).'.'.substr($texto, $ultimo + 1);
            }
        }

        return str_replace([',', '.'], '', $texto);
    }

    /**
     * Columnas obligatorias que le faltan a la fila (vacio si estan todas).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $obligatorios
     * @return array<int, string>
     */
    private function faltantes(array $data, array $obligatorios): array
    {
        return array_values(array_filter(
            $obligatorios,
            fn (string $campo): bool => ! isset($data[$campo]) || $data[$campo] === ''
        ));
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
            function (array $data) use ($inquilino): ?string {
                if (empty($data['codigo_interno'])) {
                    return 'no tiene codigo_interno';
                }

                $faltan = $this->faltantes($data, ['categoria', 'raza', 'fecha_nacimiento', 'sexo', 'estado']);

                if ($faltan !== []) {
                    return 'le falta '.implode(', ', $faltan);
                }

                Animal::updateOrCreate(
                    [
                        'inquilino_id' => $inquilino,
                        'codigo_interno' => $data['codigo_interno'],
                    ],
                    array_merge($data, ['inquilino_id' => $inquilino])
                );

                return null;
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
            function (array $data) use ($inquilino): ?string {
                $faltan = $this->faltantes($data, ['animal_id', 'fecha']);

                if ($faltan !== []) {
                    return 'le falta '.implode(', ', $faltan);
                }

                $guardado = false;

                if (($data['litros'] ?? null) !== null) {
                    ProduccionLeche::create($data + ['inquilino_id' => $inquilino]);
                    $guardado = true;
                }

                if (($data['ganancia_diaria'] ?? null) !== null || ($data['peso'] ?? null) !== null) {
                    ProduccionCarne::create($data + ['inquilino_id' => $inquilino]);
                    $guardado = true;
                }

                return $guardado ? null : 'no trae litros, ni peso, ni ganancia';
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
            function (array $data) use ($inquilino): ?string {
                $faltan = $this->faltantes($data, ['fecha', 'proveedor', 'descripcion', 'monto_total']);

                if ($faltan !== []) {
                    return 'le falta '.implode(', ', $faltan);
                }

                Compra::create($data + ['inquilino_id' => $inquilino]);

                return null;
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
            function (array $data) use ($inquilino): ?string {
                $faltan = $this->faltantes($data, ['fecha', 'cliente', 'descripcion', 'monto_total']);

                if ($faltan !== []) {
                    return 'le falta '.implode(', ', $faltan);
                }

                Venta::create($data + ['inquilino_id' => $inquilino]);

                return null;
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
