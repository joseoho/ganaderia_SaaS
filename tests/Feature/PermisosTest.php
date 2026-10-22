<?php

namespace Tests\Feature;

use App\Enums\Permiso;
use App\Enums\Rol;
use App\Models\Animal;
use App\Models\User;
use App\Support\MatrizDePermisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * La matriz de permisos y su aplicacion en la Fase 1.
 *
 * Aqui se comprueba lo que el usuario ve y lo que la aplicacion le deja hacer:
 * el dinero (compras, ventas, gastos y sus informes), los borrados, la
 * importacion y la gestion de gente. Las pruebas de los comandos de consola
 * estan en RolesYPermisosTest.
 */
class PermisosTest extends TestCase
{
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->administrador = User::query()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();
    }

    private function usuario(): User
    {
        return User::factory()->create([
            'inquilino_id' => $this->administrador->inquilino_id,
            'rol' => Rol::Usuario->value,
        ]);
    }

    // ------------------------------------------------------------------
    //  La matriz
    // ------------------------------------------------------------------

    public function test_el_administrador_tiene_todos_los_permisos(): void
    {
        foreach (Permiso::cases() as $permiso) {
            $this->assertTrue(
                MatrizDePermisos::concede(Rol::Administrador, $permiso),
                'Al administrador le falta '.$permiso->value
            );
        }

        $this->assertTrue($this->administrador->puede(Permiso::DineroVer));
    }

    public function test_el_usuario_solo_puede_ver_reportes(): void
    {
        $concedidos = array_map(
            fn (Permiso $permiso): string => $permiso->value,
            MatrizDePermisos::permisosDe(Rol::Usuario)
        );

        $this->assertSame([Permiso::ReportesVer->value], $concedidos);
    }

    public function test_un_rol_desconocido_se_comporta_como_usuario(): void
    {
        $this->assertSame(Rol::Usuario, Rol::desde('jefe'));
        $this->assertSame(Rol::Usuario, Rol::desde(null));
        $this->assertFalse(MatrizDePermisos::concede(Rol::desde('jefe'), Permiso::DineroVer));
    }

    public function test_las_puertas_de_laravel_estan_declaradas_para_todos_los_permisos(): void
    {
        $usuario = $this->usuario();

        foreach (Permiso::cases() as $permiso) {
            $this->assertSame(
                $usuario->puede($permiso),
                $usuario->can($permiso->value),
                'La puerta de '.$permiso->value.' no coincide con la matriz.'
            );
        }
    }

    // ------------------------------------------------------------------
    //  Dinero: lo que no puede hacer el usuario
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{0: string}>
     */
    public static function pantallasDeDinero(): array
    {
        return [
            'compras' => ['/compras'],
            'ventas' => ['/ventas'],
            'gastos' => ['/gastos'],
            'nueva compra' => ['/compras/create'],
            'nueva venta' => ['/ventas/create'],
            'nuevo gasto' => ['/gastos/create'],
            'reporte financiero' => ['/reportes/financiero'],
            'reporte de compras' => ['/reportes/compras'],
            'reporte de ventas' => ['/reportes/ventas'],
        ];
    }

    #[DataProvider('pantallasDeDinero')]
    public function test_el_usuario_no_entra_a_las_pantallas_de_dinero(string $ruta): void
    {
        $this->actingAs($this->usuario())->get($ruta)->assertForbidden();
    }

    #[DataProvider('pantallasDeDinero')]
    public function test_el_administrador_si_entra_a_las_pantallas_de_dinero(string $ruta): void
    {
        $this->actingAs($this->administrador)->get($ruta)->assertOk();
    }

    public function test_el_usuario_no_puede_importar(): void
    {
        $this->actingAs($this->usuario())->get('/importar/animales')->assertForbidden();
        $this->actingAs($this->usuario())->get('/importar/compras')->assertForbidden();
    }

    public function test_el_administrador_si_puede_importar(): void
    {
        $this->actingAs($this->administrador)->get('/importar/animales')->assertOk();
    }

    // ------------------------------------------------------------------
    //  Lo que el usuario SI hace
    // ------------------------------------------------------------------

    public function test_el_usuario_entra_a_su_trabajo_y_a_los_informes_de_produccion(): void
    {
        $usuario = $this->usuario();
        $animal = Animal::factory()->create(['inquilino_id' => $this->administrador->inquilino_id]);

        foreach (['/animales', '/produccion_leche', '/produccion', '/registro_vacunas', '/tratamientos', '/potreros'] as $ruta) {
            $this->actingAs($usuario)->get($ruta)->assertOk();
        }

        $this->actingAs($usuario)->get("/animales/{$animal->id}")->assertOk();
        $this->actingAs($usuario)->get('/reportes/carne')->assertOk();
        $this->actingAs($usuario)->get('/reportes/leche')->assertOk();
        $this->actingAs($usuario)->get('/reportes/potreros')->assertOk();
    }

    // ------------------------------------------------------------------
    //  La interfaz no le ofrece lo que no puede
    // ------------------------------------------------------------------

    public function test_al_usuario_no_se_le_ofrece_el_dinero_en_el_menu_ni_en_el_dashboard(): void
    {
        $respuesta = $this->actingAs($this->usuario())->get('/dashboard');

        $respuesta->assertOk();
        $respuesta->assertDontSee('href="/ventas"', false);
        $respuesta->assertDontSee('href="/compras"', false);
        $respuesta->assertDontSee('href="/gastos"', false);
        $respuesta->assertDontSee('Importar datos');
        $respuesta->assertDontSee('Balance Neto');
        $respuesta->assertDontSee('Gastos operativos');
    }

    public function test_el_administrador_si_ve_el_dinero_en_el_dashboard_y_el_menu(): void
    {
        $respuesta = $this->actingAs($this->administrador)->get('/dashboard');

        $respuesta->assertOk();
        $respuesta->assertSee('href="/ventas"', false);
        $respuesta->assertSee('href="/compras"', false);
        $respuesta->assertSee('href="/gastos"', false);
        $respuesta->assertSee('Importar datos');
        $respuesta->assertSee('Balance Neto');
    }

    public function test_la_pantalla_403_explica_por_que_no_puede(): void
    {
        $respuesta = $this->actingAs($this->usuario())->get('/ventas');

        $respuesta->assertForbidden();
        $respuesta->assertSee('No tienes permiso');
        $respuesta->assertSee('pídele al administrador');
    }

    // ------------------------------------------------------------------
    //  Guardarrail: las rutas de dinero no pueden quedarse sin permiso
    // ------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function rutasQueExigenPermiso(): array
    {
        return [
            'ventas' => ['ventas.', 'can:dinero.ver'],
            'compras' => ['compras.', 'can:dinero.ver'],
            'gastos' => ['gastos.', 'can:dinero.ver'],
            'reporte de compras' => ['reportes.compras', 'can:dinero.ver'],
            'reporte de ventas' => ['reportes.ventas', 'can:dinero.ver'],
            'reporte financiero' => ['reportes.financiero', 'can:dinero.ver'],
            'importacion' => ['import.', 'can:datos.importar'],
        ];
    }

    #[DataProvider('rutasQueExigenPermiso')]
    public function test_las_rutas_de_dinero_e_importacion_llevan_su_permiso(string $prefijo, string $middleware): void
    {
        $rutas = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta): bool => $ruta->getName() !== null && str_starts_with($ruta->getName(), $prefijo));

        $this->assertNotEmpty($rutas, 'No hay rutas con el prefijo '.$prefijo.'.');

        foreach ($rutas as $ruta) {
            $this->assertContains(
                $middleware,
                $ruta->gatherMiddleware(),
                'La ruta '.$ruta->getName().' deberia exigir '.$middleware
            );
        }
    }
}
