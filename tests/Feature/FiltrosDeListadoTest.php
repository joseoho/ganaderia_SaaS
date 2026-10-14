<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Filtros y busqueda de los listados.
 *
 * Los listados se recorrian sin parametros; con filtros nunca se habian
 * probado. Se comprueba que ningun filtro rompa la pantalla, que filtrar
 * reduzca de verdad el resultado y que una busqueda no pueda alcanzar datos de
 * otra ganaderia.
 */
class FiltrosDeListadoTest extends TestCase
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
     * @return array<string, array{0: string, 1: array<string, string>}>
     */
    public static function listados(): array
    {
        return [
            'animales' => ['/animales', ['search' => 'DEMO', 'categoria' => 'bovino', 'sexo' => 'M', 'estado' => 'activo']],
            'compras' => ['/compras', ['search' => 'a', 'fecha_desde' => '2020-01-01', 'fecha_hasta' => '2030-12-31']],
            'ventas' => ['/ventas', ['search' => 'a', 'fecha_desde' => '2020-01-01', 'fecha_hasta' => '2030-12-31']],
            'gastos' => ['/gastos', ['search' => 'a', 'categoria' => 'Nomina', 'fecha_desde' => '2020-01-01']],
            'inventario' => ['/inventario', ['search' => 'a']],
            'movilizaciones' => ['/movilizaciones', ['search' => 'a']],
            'produccion' => ['/produccion', ['search' => 'a']],
            'produccion_leche' => ['/produccion_leche', ['search' => 'a']],
            'registro_vacunas' => ['/registro_vacunas', ['search' => 'DEMO', 'nombre' => 'Aftosa']],
            'reproduccion' => ['/reproduccion', ['search' => 'a', 'tipo' => 'parto']],
            'tratamientos' => ['/tratamientos', ['search' => 'a']],
            'genealogia' => ['/genealogia', ['search' => 'a']],
            'notificaciones' => ['/notificaciones', ['search' => 'a', 'estado' => 'pendiente']],
        ];
    }

    /**
     * @param  array<string, string>  $filtros
     */
    #[DataProvider('listados')]
    public function test_los_filtros_no_rompen_el_listado(string $ruta, array $filtros): void
    {
        $this->actingAs($this->usuario)->get($ruta)->assertOk();

        foreach ($filtros as $parametro => $valor) {
            $respuesta = $this->actingAs($this->usuario)->get($ruta.'?'.http_build_query([$parametro => $valor]));

            $this->assertSame(
                200,
                $respuesta->getStatusCode(),
                'El listado '.$ruta.' falla con el filtro '.$parametro.'='.$valor
            );
        }

        // Todos los filtros a la vez y con caracteres que en SQL son comodines
        // o comillas: si algo se concatenara en la consulta, aqui se veria.
        $todos = [];
        foreach (array_keys($filtros) as $parametro) {
            $todos[$parametro] = "%'\"\\_";
        }

        $respuesta = $this->actingAs($this->usuario)->get($ruta.'?'.http_build_query($todos));

        $this->assertSame(200, $respuesta->getStatusCode(), 'El listado '.$ruta.' falla con todos los filtros a la vez.');
    }

    public function test_el_filtro_de_animales_encuentra_solo_el_buscado(): void
    {
        $animales = Animal::where('inquilino_id', $this->usuario->inquilino_id)->limit(2)->get();

        if ($animales->count() < 2) {
            $this->markTestSkipped('Hacen falta al menos dos animales para comparar.');
        }

        $buscado = $animales[0];
        $otro = $animales[1];

        $respuesta = $this->actingAs($this->usuario)
            ->get('/animales?search='.urlencode($buscado->codigo_interno));

        $respuesta->assertOk();
        $respuesta->assertSee($buscado->codigo_interno);
        $respuesta->assertDontSee($otro->codigo_interno);
    }

    /**
     * El buscador reeco el termino en su propio campo, asi que se quitan los
     * <input> antes de comprobar: lo que importa es que el codigo ajeno no
     * aparezca en los RESULTADOS.
     */
    private function resultados(string $html): string
    {
        return (string) preg_replace('/<input\b[^>]*>/i', '', $html);
    }

    public function test_la_busqueda_no_alcanza_animales_de_otra_ganaderia(): void
    {
        // El codigo se cambia SIN sesion iniciada: con una ganaderia activa el
        // filtro global impediria actualizar el registro ajeno (no afectaria a
        // ninguna fila), que es justo lo que se quiere.
        $ajeno = Animal::withoutTenantScope()
            ->where('inquilino_id', '!=', $this->usuario->inquilino_id)
            ->firstOrFail();

        // Se le da un codigo propio y unico: los datos de ejemplo repiten
        // codigos entre ganaderias, y esta prueba tiene que medir el aislamiento
        // sobre ESTE animal, no la casualidad de que los codigos coincidan.
        $codigo = 'AJENO-'.random_int(100000, 999999);
        $ajeno->update(['codigo_interno' => $codigo]);

        $this->actingAs($this->usuario);

        $this->assertSame(
            0,
            Animal::where('codigo_interno', $codigo)->count(),
            'Ese codigo ya existe en la ganaderia activa: la prueba no mediria el aislamiento.'
        );

        $respuesta = $this->get('/animales?search='.urlencode($codigo));

        $respuesta->assertOk();
        $this->assertStringNotContainsString(
            $codigo,
            $this->resultados($respuesta->getContent()),
            'La busqueda devolvio un animal de otra ganaderia.'
        );
    }

    public function test_la_busqueda_por_codigo_en_vacunas_no_alcanza_otra_ganaderia(): void
    {
        $ajeno = Animal::withoutTenantScope()
            ->where('inquilino_id', '!=', $this->usuario->inquilino_id)
            ->firstOrFail();

        $codigo = 'AJENO-'.random_int(100000, 999999);
        $ajeno->update(['codigo_interno' => $codigo]);

        $respuesta = $this->actingAs($this->usuario)
            ->get('/registro_vacunas?search='.urlencode($codigo));

        $respuesta->assertOk();
        $this->assertStringNotContainsString(
            $codigo,
            $this->resultados($respuesta->getContent()),
            'La busqueda de vacunas devolvio un animal de otra ganaderia.'
        );
    }
}
