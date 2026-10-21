<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\Gastos;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Los informes se generan sin romperse.
 *
 * Eran la ultima pantalla sin ninguna prueba: se llega a ellas desde el panel
 * de reportes y generan el archivo al vuelo. No se comprueba el contenido (cada
 * informe tiene el suyo), sino que respondan y que no devuelvan un error 500,
 * que es lo que se escapaba.
 */
class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::query()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function informes(): array
    {
        return [
            'potreros' => ['/reportes/potreros'],
            'compras' => ['/reportes/compras'],
            'ventas' => ['/reportes/ventas'],
            'carne' => ['/reportes/carne'],
            'leche' => ['/reportes/leche'],
            'financiero' => ['/reportes/financiero'],
            'general' => ['/reporte-general'],
            'por fechas' => ['/reporte-porfecha'],
            'dashboard de reportes' => ['/dashboard-reportes'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function informesGenerados(): array
    {
        return [
            'potreros' => ['/reportes/potreros/generar'],
            'compras' => ['/reportes/compras/generar'],
            'ventas' => ['/reportes/ventas/generar'],
            'carne' => ['/reportes/carne/generar'],
            'leche' => ['/reportes/leche/generar'],
        ];
    }

    #[DataProvider('informes')]
    public function test_la_pantalla_del_informe_responde(string $ruta): void
    {
        $respuesta = $this->actingAs($this->usuario)->get($ruta);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'La pantalla '.$ruta.' da error interno.');
        $this->assertContains(
            $respuesta->getStatusCode(),
            [200, 302],
            'La pantalla '.$ruta.' responde '.$respuesta->getStatusCode()
        );
    }

    #[DataProvider('informesGenerados')]
    public function test_el_informe_se_genera_sin_error_interno(string $ruta): void
    {
        $respuesta = $this->actingAs($this->usuario)->get($ruta);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Generar '.$ruta.' da error interno.');
    }

    public function test_el_informe_por_animal_se_genera_para_un_animal_propio(): void
    {
        $animal = Animal::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();

        $respuesta = $this->actingAs($this->usuario)
            ->get('/reporte-general/animal/generar?animal_id='.$animal->id);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'El informe por animal da error interno.');
    }

    public function test_los_informes_exigen_sesion(): void
    {
        $this->get('/reportes')->assertRedirect('/login');
        $this->get('/reportes/ventas/generar')->assertRedirect('/login');
    }

    /**
     * El informe de compras lee la tabla `compras`, no la de `gastos`.
     *
     * Estaba al reves: "Reporte de Compras" consultaba Gastos y mostraba una
     * columna "Categoria" (nominas, mantenimiento) con el importe en `monto`.
     * Por eso el usuario veia el mismo dinero en las dos pantallas y pensaba que
     * compras y gastos eran lo mismo.
     */
    public function test_el_informe_de_compras_lee_compras_y_no_gastos(): void
    {
        $proveedor = 'ProveedorDistintivo'.uniqid();
        $categoria = 'CategoriaDistintiva'.uniqid();

        Compra::create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'fecha' => '2026-03-01',
            'proveedor' => $proveedor,
            'descripcion' => 'Compra de prueba del informe',
            'monto_total' => 12345.67,
        ]);

        Gastos::create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'categoria' => $categoria,
            'monto' => 999.99,
            'fecha' => '2026-03-02',
            'descripcion' => 'Gasto de prueba del informe',
        ]);

        $respuesta = $this->actingAs($this->usuario)
            ->get('/reportes/compras/generar?desde=2026-03-01&hasta=2026-03-31');

        $respuesta->assertOk();

        // La compra aparece con su proveedor y su importe.
        $respuesta->assertSee($proveedor);
        $respuesta->assertSee('12,345.67');

        // Y el gasto NO se cuela en un informe de compras.
        $respuesta->assertDontSee($categoria);
        $respuesta->assertDontSee('Gasto de prueba del informe');
    }

    /**
     * El menu ofrece las dos pantallas, cada una con su nombre.
     *
     * "Compras" estaba comentada en la plantilla del menu: el modulo existia,
     * con su importacion, pero solo se llegaba escribiendo la URL.
     */
    public function test_el_menu_ofrece_compras_y_gastos_por_separado(): void
    {
        $respuesta = $this->actingAs($this->usuario)->get('/dashboard');

        $respuesta->assertOk();
        $respuesta->assertSee('href="/compras"', false);
        $respuesta->assertSee('href="/gastos"', false);
    }

    /**
     * El balance resta compras y gastos, asi que las dos cifras tienen que verse.
     *
     * La tarjeta de compras no existia: el balance descontaba un dinero que no
     * aparecia en ninguna parte de la pantalla.
     */
    public function test_el_dashboard_muestra_compras_y_gastos_operativos(): void
    {
        $respuesta = $this->actingAs($this->usuario)->get('/dashboard');

        $respuesta->assertOk();
        $respuesta->assertSee('Compras');
        $respuesta->assertSee('Gastos operativos');
        $respuesta->assertSee('Ventas − (compras + gastos)');
    }
}
