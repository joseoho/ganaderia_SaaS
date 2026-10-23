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
 * Los dos roles de la ganaderia: administrador y usuario.
 *
 * El administrador es el dueño: dinero, borrados, importacion y gestion de la
 * gente. El usuario hace el trabajo con los animales (animales, produccion,
 * sanidad, potreros) y consulta informes. Ante un rol desconocido se deniega.
 *
 * Los permisos se deciden en App\Support\MatrizDePermisos; aqui se comprueba el
 * comportamiento de la aplicacion, no la matriz en si (eso es PermisosTest).
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

    private function usuario(): User
    {
        return User::factory()->create([
            'inquilino_id' => $this->administrador->inquilino_id,
            'rol' => 'usuario',
        ]);
    }

    private function animal(): \App\Models\Animal
    {
        return \App\Models\Animal::factory()->create([
            'inquilino_id' => $this->administrador->inquilino_id,
        ]);
    }

    public function test_el_usuario_que_crea_la_ganaderia_es_administrador(): void
    {
        $this->assertTrue($this->administrador->esAdministrador());
        $this->assertSame('Administrador', $this->administrador->nombreDelRol());
    }

    public function test_los_roles_estan_definidos(): void
    {
        $this->assertSame(
            ['administrador', 'usuario', 'operador'],
            array_keys(\App\Enums\Rol::etiquetas())
        );

        // Dentro de una ganadería solo se reparten los dos de finca: el operador
        // es de la plataforma y no se asigna desde Usuarios.
        $this->assertSame(
            ['administrador', 'usuario'],
            array_column(\App\Enums\Rol::deFinca(), 'value')
        );
    }

    public function test_un_rol_desconocido_no_da_permisos_de_administrador(): void
    {
        $raro = User::factory()->create([
            'inquilino_id' => $this->administrador->inquilino_id,
            'rol' => 'jefe',
        ]);

        $this->assertFalse($raro->esAdministrador(), 'Un rol desconocido no debe dar permisos.');
        $this->assertSame('Usuario', $raro->nombreDelRol(), 'Se cae en el rol de menos privilegio.');
    }

    public function test_el_administrador_puede_borrar(): void
    {
        $venta = Venta::factory()->create(['inquilino_id' => $this->administrador->inquilino_id]);

        $this->actingAs($this->administrador)
            ->delete("/ventas/{$venta->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('ventas', ['id' => $venta->id]);
    }

    public function test_el_usuario_no_puede_borrar(): void
    {
        $potrero = Potrero::factory()->create(['inquilino_id' => $this->administrador->inquilino_id]);

        $this->actingAs($this->usuario())
            ->delete("/potreros/{$potrero->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('potreros', ['id' => $potrero->id]);
    }

    public function test_el_usuario_hace_el_trabajo_con_los_animales(): void
    {
        $usuario = $this->usuario();
        $animal = $this->animal();

        $this->actingAs($usuario)->get('/animales')->assertOk();
        $this->actingAs($usuario)->get("/animales/{$animal->id}/edit")->assertOk();
        $this->actingAs($usuario)->get('/produccion_leche')->assertOk();
        $this->actingAs($usuario)->get('/potreros')->assertOk();
        $this->actingAs($usuario)->get('/dashboard')->assertOk();
    }

    public function test_el_comando_de_rol_cambia_el_rol(): void
    {
        $usuario = $this->usuario();

        $codigo = Artisan::call('ganaderia:rol', ['email' => $usuario->email, 'rol' => 'administrador']);

        $this->assertSame(0, $codigo);
        $this->assertTrue($usuario->fresh()->esAdministrador());
    }

    public function test_el_comando_de_rol_rechaza_un_rol_inventado(): void
    {
        $usuario = $this->usuario();

        $codigo = Artisan::call('ganaderia:rol', ['email' => $usuario->email, 'rol' => 'superjefe']);

        $this->assertSame(1, $codigo);
        $this->assertSame('Usuario', $usuario->fresh()->nombreDelRol(), 'El rol no debia cambiar.');
    }

    public function test_el_comando_de_rol_avisa_si_el_correo_no_existe(): void
    {
        $codigo = Artisan::call('ganaderia:rol', ['email' => 'nadie@ejemplo.test', 'rol' => 'usuario']);

        $this->assertSame(1, $codigo);
    }

    public function test_el_listado_de_usuarios_muestra_los_roles(): void
    {
        $this->usuario();

        Artisan::call('ganaderia:usuarios');
        $salida = Artisan::output();

        $this->assertStringContainsString('admin@ganaderia.test', $salida);
        $this->assertStringContainsString('Administrador', $salida);
        $this->assertStringContainsString('Usuario', $salida);
    }

    public function test_el_comando_de_alta_crea_un_usuario_en_la_ganaderia(): void
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
        $this->assertSame('Usuario', $creado->nombreDelRol(), 'El alta entra como usuario por defecto.');
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
