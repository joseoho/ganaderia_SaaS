<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\Gastos;
use App\Models\Genealogia;
use App\Models\InventarioInsumo;
use App\Models\Movilizacion;
use App\Models\Notificacion;
use App\Models\Potrero;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\RegistroVacuna;
use App\Models\Reproduccion;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Venta;
use App\Support\Formato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Formularios de CREACION: se rellenan con datos validos, se envian y se
 * comprueba que el registro se crea y que lo enviado queda guardado.
 *
 * Los formularios de edicion ya tenian esta cobertura; los de creacion, no, y
 * es donde el mismo tipo de fallo (campos que el controlador ignora, o
 * formularios que no envian lo que la validacion exige) todavia no se habia
 * buscado. Los valores validos se toman del esquema a traves de las fabricas,
 * no escritos a mano modulo por modulo.
 */
class FormulariosDeCreacionTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::withoutTenantScope()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();
    }

    /**
     * modelo, ruta del formulario, ruta de creacion, [campo del formulario => columna]
     *
     * @return array<string, array{0: class-string, 1: string, 2: string, 3: array<string, string>}>
     */
    public static function modulos(): array
    {
        return [
            'animales' => [Animal::class, '/animales/create', '/animales', []],
            'potreros' => [Potrero::class, '/potreros/create', '/potreros', []],
            'compras' => [Compra::class, '/compras/create', '/compras', []],
            'ventas' => [Venta::class, '/ventas/create', '/ventas', []],
            'gastos' => [Gastos::class, '/gastos/create', '/gastos', []],
            'inventario' => [InventarioInsumo::class, '/inventario/create', '/inventario', []],
            'movilizaciones' => [Movilizacion::class, '/movilizaciones/create', '/movilizaciones', ['descripcion' => 'motivo']],
            'produccion' => [ProduccionCarne::class, '/produccion/create', '/produccion', []],
            'produccion_leche' => [ProduccionLeche::class, '/produccion_leche/create', '/produccion_leche', []],
            'registro_vacunas' => [RegistroVacuna::class, '/registro_vacunas/create', '/registro_vacunas', []],
            'reproduccion' => [Reproduccion::class, '/reproduccion/create', '/reproduccion', []],
            'tratamientos' => [Tratamiento::class, '/tratamientos/create', '/tratamientos', []],
            'notificaciones' => [Notificacion::class, '/notificaciones/create', '/notificaciones', []],
            'genealogia' => [Genealogia::class, '/genealogia/create', '/genealogia', []],
        ];
    }

    /**
     * Campos del formulario con su tipo y un valor valido.
     *
     * Para los desplegables se toma la primera opcion con valor: son datos que
     * el propio formulario ofrece. Para el resto se usan los atributos que
     * genera la fabrica del modelo, y si el campo no es una columna se rellena
     * con un valor generico segun el tipo.
     *
     * @param  array<string, string>  $renombrados
     * @return array{0: array<string, string>, 1: array<string, string>}
     */
    private function payloadValido(string $modelo, string $html, array $renombrados): array
    {
        $atributos = $modelo::factory()->make()->getAttributes();
        $enviados = [];
        $esperados = [];

        // <input ...>
        preg_match_all('/<input\b[^>]*>/i', $html, $inputs);
        foreach ($inputs[0] as $tag) {
            if (! preg_match('/name="([^"]+)"/i', $tag, $m)) {
                continue;
            }
            $campo = $m[1];
            if (in_array($campo, ['_token', '_method'], true)) {
                continue;
            }
            $tipo = preg_match('/type="([^"]+)"/i', $tag, $t) ? strtolower($t[1]) : 'text';
            if (in_array($tipo, ['submit', 'button', 'file', 'reset', 'checkbox'], true)) {
                continue;
            }

            $columna = $renombrados[$campo] ?? $campo;

            if (array_key_exists($columna, $atributos)) {
                $enviados[$campo] = $this->aValorDeFormulario($atributos[$columna], $tipo);
                $esperados[$campo] = $columna;
            } else {
                $enviados[$campo] = match ($tipo) {
                    'date' => '2026-01-15',
                    'number' => '10',
                    default => 'Valor de prueba',
                };
            }
        }

        // <select ...>: la primera opcion con valor (los *_id incluidos)
        preg_match_all('/<select\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/select>/is', $html, $selects, PREG_SET_ORDER);
        foreach ($selects as $select) {
            $campo = $select[1];
            if (preg_match_all('/<option[^>]*value="([^"]*)"/i', $select[2], $opciones)) {
                $valores = array_values(array_filter($opciones[1], fn ($v) => $v !== ''));
                if ($valores !== []) {
                    $enviados[$campo] = $valores[0];
                }
            }
        }

        // <textarea ...>
        preg_match_all('/<textarea\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/textarea>/is', $html, $areas, PREG_SET_ORDER);
        foreach ($areas as $area) {
            $campo = $area[1];
            $columna = $renombrados[$campo] ?? $campo;
            $enviados[$campo] = array_key_exists($columna, $atributos)
                ? $this->aValorDeFormulario($atributos[$columna], 'textarea')
                : 'Texto de prueba';
            if (array_key_exists($columna, $atributos)) {
                $esperados[$campo] = $columna;
            }
        }

        return [$enviados, $esperados];
    }

    /**
     * Convierte el valor de la fabrica a lo que enviaria el formulario.
     *
     * Las fabricas devuelven fechas como objetos Carbon; un <input type="date">
     * envia "Y-m-d" y no "Y-m-d H:i:s". Enviar la forma larga pasaba la
     * validacion `date` pero no es lo que hace el navegador.
     */
    private function aValorDeFormulario(mixed $valor, string $tipo): string
    {
        // Un <input type="date"> envia SIEMPRE Y-m-d. Ojo: con el cast `date`
        // declarado en el modelo, getAttributes() ya devuelve la cadena
        // "Y-m-d 00:00:00", por eso se formatea tambien cuando es texto.
        if ($tipo === 'date') {
            return Formato::fecha($valor);
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return (string) $valor;
    }

    /**
     * @param  class-string  $modelo
     * @param  array<string, string>  $renombrados
     */
    #[DataProvider('modulos')]
    public function test_el_formulario_de_creacion_acepta_datos_validos(string $modelo, string $rutaForm, string $rutaStore, array $renombrados): void
    {
        $html = $this->actingAs($this->usuario)->get($rutaForm)->assertOk()->getContent();
        [$enviados] = $this->payloadValido($modelo, $html, $renombrados);

        $this->assertNotEmpty($enviados, 'No se extrajo ningun campo del formulario '.$rutaForm);

        $antes = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->count();

        $respuesta = $this->actingAs($this->usuario)->post($rutaStore, $enviados);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Error interno al crear desde '.$rutaForm);
        $this->assertNotSame(403, $respuesta->getStatusCode(), 'Recibe 403 al crear desde '.$rutaForm);
        $this->assertNotSame(419, $respuesta->getStatusCode(), 'Token caducado al crear desde '.$rutaForm);

        try {
            $respuesta->assertSessionHasNoErrors();
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $this->fail(
                $e->getMessage()."\n".
                'El formulario de '.$rutaForm.' envia estos campos: '.implode(', ', array_keys($enviados))
            );
        }

        $despues = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->count();

        $this->assertSame(
            $antes + 1,
            $despues,
            'Al enviar el formulario de '.$rutaForm.' no se creo el registro.'
        );
    }

    /**
     * @param  class-string  $modelo
     * @param  array<string, string>  $renombrados
     */
    #[DataProvider('modulos')]
    public function test_lo_que_se_envia_en_la_creacion_se_guarda(string $modelo, string $rutaForm, string $rutaStore, array $renombrados): void
    {
        $html = $this->actingAs($this->usuario)->get($rutaForm)->assertOk()->getContent();
        [$enviados, $esperados] = $this->payloadValido($modelo, $html, $renombrados);

        if ($esperados === []) {
            $this->markTestSkipped('El formulario de '.$rutaForm.' no tiene campos que correspondan a columnas del modelo.');
        }

        $respuesta = $this->actingAs($this->usuario)->post($rutaStore, $enviados);
        $respuesta->assertSessionHasNoErrors();

        $creado = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->latest('id')
            ->first();

        $this->assertNotNull($creado, 'No se creo el registro desde '.$rutaForm);

        foreach ($esperados as $campo => $columna) {
            $this->assertSame(
                $this->normalizar($enviados[$campo]),
                $this->normalizar($creado->{$columna}),
                sprintf(
                    'Se envio "%s" en el campo %s de %s y la columna %s quedo en "%s": el controlador descarta ese campo al crear.',
                    $enviados[$campo],
                    $campo,
                    $rutaForm,
                    $columna,
                    is_scalar($creado->{$columna}) ? $creado->{$columna} : gettype($creado->{$columna})
                )
            );
        }
    }

    /**
     * Normaliza para comparar: las fechas segun las pinta la aplicacion y los
     * numeros como float, para no depender del formato con que vuelven de la
     * base de datos.
     */
    private function normalizar(mixed $valor): string
    {
        if ($valor instanceof \DateTimeInterface) {
            $valor = $valor->format('Y-m-d H:i:s');
        }

        $texto = trim((string) $valor);

        // Una fecha con hora se compara por su dia: el formulario envia Y-m-d y
        // hay columnas (como inventario_insumos.fecha_ingreso, de tipo
        // timestamp) que devuelven la hora aunque el dato sea una fecha.
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T]\d{2}:\d{2}:\d{2}$/', $texto, $coincidencia)) {
            $texto = $coincidencia[1];
        }

        if (is_numeric($texto)) {
            return (string) (float) $texto;
        }

        return $texto;
    }
}
