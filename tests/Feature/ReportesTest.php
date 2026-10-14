<?php

namespace Tests\Feature;

use App\Models\Animal;
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

        $this->usuario = User::withoutTenantScope()
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
}
