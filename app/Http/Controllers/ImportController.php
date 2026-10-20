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

    /** Columnas que la tabla de cada modulo exige para poder guardar la fila. */
    private const OBLIGATORIOS = [
        'animales' => ['codigo_interno', 'categoria', 'raza', 'fecha_nacimiento', 'sexo', 'estado'],
        // Produccion son dos tablas distintas y cada una exige lo suyo: la leche
        // se mide en litros y la carne en kilos. Son las mismas columnas que
        // piden las pantallas de alta (StoreProduccionLecheRequest y
        // StoreProduccionCarneRequest), para que importar y escribir a mano no
        // pidan cosas distintas.
        'produccion_leche' => ['animal_id', 'fecha', 'litros'],
        'produccion_carne' => ['animal_id', 'fecha', 'peso'],
        'compras' => ['fecha', 'proveedor', 'descripcion', 'monto_total'],
        'ventas' => ['fecha', 'cliente', 'descripcion', 'monto_total'],
    ];

    /** Titulo de cada pantalla de produccion. */
    private const PRODUCCION_TITULOS = [
        'leche' => 'Producción de leche',
        'carne' => 'Producción de carne',
    ];

    /**
     * Columnas que ofrece cada pantalla de produccion, con la etiqueta que ve el
     * usuario.
     *
     * No comparten flujo porque no comparten datos. Antes habia una sola
     * pantalla con las columnas de las dos tablas, y el resultado era una
     * plantilla con la mitad de las celdas vacias y un "obligatorio" que
     * significaba una cosa distinta segun la fila.
     *
     * @var array<string, array<string, string>>
     */
    private const PRODUCCION_CAMPOS = [
        'leche' => [
            'animal_id' => 'ID del Animal',
            'fecha' => 'Fecha',
            'litros' => 'Litros',
            'turno' => 'Turno',
            'observaciones' => 'Observaciones',
        ],
        'carne' => [
            'animal_id' => 'ID del Animal',
            'fecha' => 'Fecha',
            'peso' => 'Peso (kg)',
            'ganancia_diaria' => 'Ganancia diaria (kg/día)',
            'observaciones' => 'Observaciones',
        ],
    ];

    /** Otros titulos con los que la gente suele nombrar esas columnas en Excel. */
    private const SINONIMOS = [
        'monto_total' => ['monto', 'total', 'valor', 'importe', 'precio', 'monto total', 'valor total', 'total compra', 'total venta'],
        'proveedor' => ['proveedor', 'nombre proveedor', 'suministra', 'vendedor'],
        'cliente' => ['cliente', 'nombre cliente', 'comprador'],
        'descripcion' => ['descripcion', 'detalle', 'concepto', 'observacion', 'observaciones'],
        'fecha' => ['fecha', 'dia', 'fecha compra', 'fecha venta'],
        'animal_id' => ['animal_id', 'id animal', 'id_animal', 'animal', 'codigo animal'],
        'codigo_interno' => ['codigo_interno', 'codigo', 'arete', 'numero', 'chapeta'],
        'categoria' => ['categoria', 'tipo'],
        'raza' => ['raza'],
        'fecha_nacimiento' => ['fecha_nacimiento', 'nacimiento', 'fecha nacimiento'],
        'sexo' => ['sexo', 'genero'],
        'estado' => ['estado', 'situacion'],
    ];

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
     * El motivo de cada fila omitida se calcula aqui y se dice EN QUE se falla:
     * si falta una columna por mapear, si la celda esta vacia, o si el valor no
     * se puede leer (con el valor entre comillas). Un "le falta monto_total"
     * generico no sirve para nada: puede significar tres cosas distintas.
     *
     * @param  Collection<int, mixed>  $rows
     * @param  array<string, string>  $map
     * @param  callable(array<string, mixed>): ?string  $guardar  null si se guardo; si no, el motivo.
     * @return array{0: int, 1: int} [importados, omitidos]
     */
    private function importarFilas(string $tipo, Collection $rows, array $map, callable $guardar): array
    {
        $columnas = array_keys((array) ($rows->first()?->toArray() ?? []));
        $obligatorios = self::OBLIGATORIOS[$tipo] ?? [];

        // Si falta la columna de un campo obligatorio no se puede importar nada:
        // se dice que campo falta, que columnas trae el archivo y, si hay una con
        // nombre parecido, cual es.
        $sinMapear = array_values(array_filter(
            $obligatorios,
            fn (string $campo): bool => ! isset($map[$campo]) || $map[$campo] === ''
        ));

        if ($sinMapear !== []) {
            $detalle = [];

            foreach ($sinMapear as $campo) {
                $parecida = $this->columnaParecida($campo, $columnas);
                $detalle[] = $campo.($parecida !== null ? " (en tu archivo parece «{$parecida}»)" : '');
            }

            session(['import_motivos' => [
                'No se pudo importar: falta asignar '.implode(', ', $detalle).'. Vuelve al paso de mapeo y elige esa columna.',
                'Columnas que tiene tu archivo: '.implode(', ', $columnas),
            ]]);

            return [0, $rows->count()];
        }

        $importados = 0;
        $omitidos = 0;
        $motivos = [];

        foreach ($rows as $numero => $row) {
            $data = [];

            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            // Una celda vacia de una hoja de calculo llega como cadena vacia, no
            // como null. Un 0 de verdad se respeta.
            foreach ($data as $campo => $valor) {
                if (is_string($valor) && trim($valor) === '') {
                    $data[$campo] = null;
                }
            }

            $motivo = null;

            // Fechas e importes: si un valor no se puede leer, se dice cual.
            foreach ($data as $campo => $valor) {
                if ($valor === null || $motivo !== null) {
                    continue;
                }

                if (in_array($campo, self::CAMPOS_FECHA, true)) {
                    $data[$campo] = $this->normalizarFecha((string) $valor);

                    if ($data[$campo] === null) {
                        $motivo = "la fecha «{$valor}» de {$campo} no se entiende (vale 2026-02-01 o 01/02/2026)";
                    }
                } elseif (in_array($campo, self::CAMPOS_NUMERO, true)) {
                    $data[$campo] = $this->normalizarNumero($valor);

                    if ($data[$campo] === null) {
                        $motivo = "el valor «{$valor}» de {$campo} no es un numero";
                    }
                }
            }

            if ($motivo === null) {
                $vacias = [];

                foreach ($obligatorios as $campo) {
                    if (! isset($data[$campo]) || $data[$campo] === '') {
                        $vacias[] = $campo;
                    }
                }

                if ($vacias !== []) {
                    $motivo = 'la celda esta vacia en '.implode(', ', $vacias);
                }
            }

            if ($motivo === null) {
                try {
                    $motivo = DB::transaction(fn (): ?string => $guardar($data));
                } catch (Throwable $e) {
                    Log::error('Importacion: fila omitida', ['error' => $e->getMessage(), 'datos' => $data]);
                    $motivo = 'no se pudo guardar (revisa los valores de la fila)';
                }
            }

            if ($motivo === null) {
                $importados++;

                continue;
            }

            $omitidos++;

            if (count($motivos) < 10) {
                $motivos[] = 'Fila '.($numero + 2).': '.$motivo;
            }
        }

        session(['import_motivos' => $motivos]);

        return [$importados, $omitidos];
    }

    /**
     * Busca en las columnas del archivo una que se llame como el campo, como
     * alguno de sus sinonimos habituales o que lo contenga.
     *
     * @param  array<int, string>  $columnas
     */
    private function columnaParecida(string $campo, array $columnas): ?string
    {
        $candidatos = array_merge([$campo], self::SINONIMOS[$campo] ?? []);

        foreach ($columnas as $columna) {
            $normalizada = mb_strtolower(trim((string) $columna));

            if (in_array($normalizada, $candidatos, true)) {
                return (string) $columna;
            }
        }

        foreach ($columnas as $columna) {
            $normalizada = mb_strtolower(trim((string) $columna));

            foreach ($candidatos as $candidato) {
                if ($normalizada !== '' && str_contains($normalizada, $candidato)) {
                    return (string) $columna;
                }
            }
        }

        return null;
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
            'animales',
            $this->leerFilas('animales'),
            session('map_animales') ?? [],
            function (array $data) use ($inquilino): ?string {
                // updateOrCreate por (ganaderia, codigo): reimportar el mismo
                // archivo ACTUALIZA los animales en vez de duplicarlos.
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
    // PRODUCCIÓN (leche y carne)
    // ============================
    /**
     * Lo que necesita cualquiera de las dos pantallas: su titulo, sus columnas y
     * cules son obligatorias.
     *
     * El tipo llega validado por la ruta ({tipo} solo admite leche o carne),
     * pero se comprueba igualmente: si manana se anade otra ruta y se olvida
     * esta comprobacion, es mejor un 404 que un error de indice sin definir.
     *
     * @return array{tipo: string, titulo: string, campos: array<string, string>, obligatorios: array<int, string>}
     */
    private function datosProduccion(string $tipo): array
    {
        if (! isset(self::PRODUCCION_TITULOS[$tipo], self::PRODUCCION_CAMPOS[$tipo])) {
            abort(404, 'Tipo de producción desconocido: '.$tipo);
        }

        return [
            'tipo' => $tipo,
            'titulo' => self::PRODUCCION_TITULOS[$tipo],
            'campos' => self::PRODUCCION_CAMPOS[$tipo],
            // Aqui no hay ?? [] a proposito: cada tipo de produccion TIENE que
            // estar en OBLIGATORIOS, y PHPStan lo comprueba por el tipo. Si
            // manana se anade un tipo nuevo, esto avisa en vez de dejar una
            // importacion que acepta filas sin sus columnas obligatorias.
            'obligatorios' => self::OBLIGATORIOS['produccion_'.$tipo],
        ];
    }

    public function produccionForm(string $tipo)
    {
        return view('import.produccion.form', $this->datosProduccion($tipo));
    }

    public function produccionUpload(Request $request, string $tipo)
    {
        // Se resuelve antes de tocar el archivo: un tipo desconocido no debe
        // llegar a procesar nada.
        $datos = $this->datosProduccion($tipo);

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

            $sugerencias = $this->sugerirMapeo($headers, array_keys($datos['campos']));

            // La clave de cache lleva el tipo: un archivo de leche a medio mapear
            // no debe pisar uno de carne abierto en otra pestana.
            $this->guardarFilas('produccion_'.$tipo, $rows);

            return view('import.produccion.map', array_merge($datos, compact('headers', 'sugerencias')));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error en produccionUpload ('.$tipo.'): '.$e->getMessage());

            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    public function produccionPreview(Request $request, string $tipo)
    {
        $datos = $this->datosProduccion($tipo);
        $map = $request->map;
        $rows = $this->leerFilas('produccion_'.$tipo);

        $preview = $rows->take(20)->map(function ($row) use ($map) {
            $data = [];
            foreach ($map as $campo => $col) {
                $data[$campo] = $row[$col] ?? null;
            }

            return $data;
        });

        session(['map_produccion_'.$tipo => $map]);

        return view('import.produccion.preview', array_merge($datos, compact('preview')));
    }

    public function produccionImport(string $tipo)
    {
        $datos = $this->datosProduccion($tipo);
        $inquilino = (int) Auth::user()->inquilino_id;

        [$importados, $omitidos] = $this->importarFilas(
            'produccion_'.$tipo,
            $this->leerFilas('produccion_'.$tipo),
            session('map_produccion_'.$tipo) ?? [],
            function (array $fila) use ($tipo, $inquilino): ?string {
                $this->guardarProduccion($tipo, $fila, $inquilino);

                // Si el guardado falla, importarFilas lo captura y escribe el
                // motivo de la fila; llegar hasta aqui ya es un exito.
                return null;
            }
        );

        session(['ultimo_importado' => $importados]);

        return view('import.resultado', array_merge($datos, compact('importados', 'omitidos')));
    }

    /**
     * Guarda una fila en la tabla que toca: produccion_leche o produccion_carne.
     *
     * Aqui esta la unica diferencia entre los dos tipos, y por eso se decide con
     * un match en vez de guardar el nombre del modelo en una constante: si
     * manana aparece otro tipo, PHPStan lo senala en vez de fallar en silencio.
     *
     * @param  array<string, mixed>  $fila
     */
    private function guardarProduccion(string $tipo, array $fila, int $inquilino): void
    {
        $datos = array_merge($fila, ['inquilino_id' => $inquilino]);

        match ($tipo) {
            'leche' => ProduccionLeche::create($datos),
            'carne' => ProduccionCarne::create($datos),
            default => throw new \InvalidArgumentException('Tipo de producción desconocido: '.$tipo),
        };
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
            'compras',
            $this->leerFilas('compras'),
            session('map_compras') ?? [],
            function (array $data) use ($inquilino): ?string {
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
            'ventas',
            $this->leerFilas('ventas'),
            session('map_ventas') ?? [],
            function (array $data) use ($inquilino): ?string {
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
