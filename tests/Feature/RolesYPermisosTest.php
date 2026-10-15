<?php

namespace Tests\Feature;

use App\Models\Inquilino;
use App\Models\Potrero;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Roles de la ganaderia: administrador y operario.
 *
 * El administrador puede con todo. El operario hace el trabajo del dia a dia
 * (crear, editar) pero NO borra. Ante un rol desconocido o vacio se deniega.
 */
class RolesYPermisosTest extends TestCase
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

    private function operario(): User
    {
        return User::factory()->create([
            'inquilino_id' => $this->administrador->inquilino_id,
            'rol' => User::ROL_OPERARIO,
        ]);
    }

    public function test_el_usuario_que_crea_la_ganaderia_es_administrador(): void
    {
        $this->assertTrue($this->administrador->esAdministrador());
        $this->assertSame('Administrador', $this->administrador->nombreDelRol());
    }

    public function test_los_roles_estan_definidos(): void
    {
        $this->assertSame(['administrador', 'operario'], array_keys(User::ROLES));
    }

    public function test_un_rol_desconocido_no_da_permisos_de_administrador(): void
    {
        $raro = User::factory()->create([
            'inquilino_id' => $this->administrador->inquilino_id,
            'rol' => 'jefe',
        ]);

        $this->assertFalse($raro->esAdministrador(), 'Un rol desconocido no debe dar permisos.');
        $this->assertSame('Jefe', $raro->nombreDelRol());
    }

    public function test_el_administrador_puede_borrar(): void
    {
        $venta = Venta::factory()->create(['inquilino_id' => $this->administrador->inquilino_id]);

        $this->actingAs($this->administrador)
            ->delete("/ventas/{$venta->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('ventas', ['id' => $venta->id]);
    }

    public function test_el_operario_no_puede_borrar(): void
    {
        $venta = Venta::factory()->create(['inquilino_id' => $this->administrador->inquilino_id]);

        $this->actingAs($this->operario())
            ->delete("/ventas/{$venta->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('ventas', ['id' => $venta->id]);
    }

    public function test_el_operario_no_puede_borrar_en_ningun_modulo(): void
    {
        $operario = $this->operario();

        $rutas = [
            [Potrero::class, '/potreros/%d'],
            [Venta::class, '/ventas/%d'],
        ];

        foreach ($rutas as [$modelo, $ruta]) {
            $registro = $modelo::factory()->create(['inquilino_id' => $this->administrador->inquilino_id]);

            $this->actingAs($operario)
                ->delete(sprintf($ruta, $registro->id))
                ->assertForbidden();

            $this->assertDatabaseHas($registro->getTable(), ['id' => $registro->id]);
        }
    }

    public function test_el_operario_si_puede_hacer_el_trabajo_del_dia_a_dia(): void
    {
        $operario = $this->operario();

        // Listar, ver el formulario de creacion y crear.
        $this->actingAs($operario)->get('/ventas')->assertOk();
        $this->actingAs($operario)->get('/ventas/create')->assertOk();

        $respuesta = $this->actingAs($operario)->post('/ventas', [
            'cliente' => 'Cliente del operario',
            'fecha' => '2026-03-01',
            'descripcion' => 'Venta registrada por un operario',
            'monto_total' => '150000',
        ]);

        $respuesta->assertSessionHasNoErrors();
        $this->assertDatabaseHas('ventas', [
            'cliente' => 'Cliente del operario',
            'inquilino_id' => $this->administrador->inquilino_id,
        ]);
    }

    public function test_el_operario_puede_editar(): void
    {
        $operario = $this->operario();
        $venta = Venta::factory()->create(['inquilino_id' => $this->administrador->inquilino_id]);

        $this->actingAs($operario)->get("/ventas/{$venta->id}/edit")->assertOk();
    }

    public function test_el_comando_de_rol_cambia_el_rol(): void
    {
        $usuario = $this->operario();

        $codigo = Artisan::call('ganaderia:rol', ['email' => $usuario->email, 'rol' => 'administrador']);

        $this->assertSame(0, $codigo);
        $this->assertTrue($usuario->fresh()->esAdministrador());
    }

    public function test_el_comando_de_rol_rechaza_un_rol_inventado(): void
    {
        $usuario = $this->operario();

        $codigo = Artisan::call('ganaderia:rol', ['email' => $usuario->email, 'rol' => 'superjefe']);

        $this->assertSame(1, $codigo);
        $this->assertTrue($usuario->fresh()->esOperario(), 'El rol no debia cambiar.');
    }

    public function test_el_comando_de_rol_avisa_si_el_correo_no_existe(): void
    {
        $codigo = Artisan::call('ganaderia:rol', ['email' => 'nadie@ejemplo.test', 'rol' => 'operario']);

        $this->assertSame(1, $codigo);
    }

    public function test_el_listado_de_usuarios_muestra_los_roles(): void
    {
        $this->operario();

        Artisan::call('ganaderia:usuarios');
        $salida = Artisan::output();

        $this->assertStringContainsString('admin@ganaderia.test', $salida);
        $this->assertStringContainsString('Administrador', $salida);
        $this->assertStringContainsString('Operario', $salida);
    }

    public function test_el_comando_de_alta_crea_un_operario_en_la_ganaderia(): void
    {
        // Con dos ganaderias sembradas hay que indicar cual.
        $codigo = Artisan::call('ganaderia:usuario', [
            'email' => 'nuevo@ganaderia.test',
            'nombre' => 'Trabajador Nuevo',
            '--inquilino' => (string) $this->administrador->inquilino_id,
            '--password' => 'secreto-para-la-prueba',
        ]);

        $this->assertSame(0, $codigo);

        $creado = User::query()->where('email', 'nuevo@ganaderia.test')->first();

        $this->assertNotNull($creado);
        $this->assertSame((int) $this->administrador->inquilino_id, (int) $creado->inquilino_id);
        $this->assertTrue($creado->esOperario(), 'El alta debe entrar como operario por defecto.');
        $this->assertNull($creado->email_verified_at, 'El correo debe quedar pendiente de verificar.');
    }

    public function test_el_comando_de_alta_no_repite_correos(): void
    {
        $codigo = Artisan::call('ganaderia:usuario', [
            'email' => 'admin@ganaderia.test',
            'nombre' => 'Duplicado',
            '--inquilino' => (string) $this->administrador->inquilino_id,
        ]);

        $this->assertSame(1, $codigo);
    }

    public function test_el_comando_de_alta_exige_indicar_la_ganaderia_si_hay_varias(): void
    {
        $this->assertGreaterThan(1, Inquilino::count(), 'La prueba necesita mas de una ganaderia.');

        $codigo = Artisan::call('ganaderia:usuario', [
            'email' => 'sinelegir@ganaderia.test',
            'nombre' => 'Sin elegir',
        ]);

        $this->assertSame(1, $codigo);
        $this->assertNull(User::query()->where('email', 'sinelegir@ganaderia.test')->first());
    }
}
