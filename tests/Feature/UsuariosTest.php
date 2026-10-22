<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Gestión de usuarios de la ganadería (Fase 2 del control de acceso).
 *
 * Lo importante que se prueba aquí:
 *
 *  - Solo el administrador entra (permiso `usuarios.gestionar`).
 *  - Nadie se cambia el rol a sí mismo ni se desactiva a sí mismo.
 *  - Un administrador de otra ganadería no puede tocar estas cuentas (404).
 *  - Las cuentas se desactivan, no se borran, y una cuenta desactivada no entra.
 *  - Un rol inventado en el formulario se rechaza.
 *
 * La protección de "último administrador" queda como defensa en profundidad: por
 * HTTP no se alcanza, porque quien podría disparar la regla (degradar al último
 * admin) solo puede ser ese mismo admin, y eso lo frena antes la regla de "no te
 * cambies el rol a ti mismo".
 */
class UsuariosTest extends TestCase
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

    private function trabajador(array $atributos = []): User
    {
        return User::factory()->create(array_merge([
            'inquilino_id' => $this->administrador->inquilino_id,
            'rol' => 'usuario',
        ], $atributos));
    }

    private function deOtraGanaderia(): User
    {
        return User::query()->where('email', 'admin@esperanza.test')->firstOrFail();
    }

    public function test_el_administrador_ve_los_usuarios_de_su_ganaderia(): void
    {
        $trabajador = $this->trabajador(['email' => 'peon@ganaderia.test']);

        $respuesta = $this->actingAs($this->administrador)->get('/usuarios');

        $respuesta->assertOk();
        $respuesta->assertSee($this->administrador->email);
        $respuesta->assertSee($trabajador->email);

        // Y no ve las cuentas de la otra ganadería.
        $respuesta->assertDontSee($this->deOtraGanaderia()->email);
    }

    public function test_el_usuario_no_entra_a_la_gestion_de_usuarios(): void
    {
        $usuario = $this->trabajador();

        $this->actingAs($usuario)->get('/usuarios')->assertForbidden();
        $this->actingAs($usuario)->get('/usuarios/create')->assertForbidden();
        $this->actingAs($usuario)->post('/usuarios', [
            'name' => 'Intruso',
            'email' => 'intruso@ganaderia.test',
            'rol' => 'administrador',
            'password' => 'secreto-2026',
            'password_confirmation' => 'secreto-2026',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@ganaderia.test']);
    }

    public function test_el_administrador_crea_un_usuario(): void
    {
        $respuesta = $this->actingAs($this->administrador)->post('/usuarios', [
            'name' => 'Peón Nuevo',
            'email' => 'peon.nuevo@ganaderia.test',
            'rol' => 'usuario',
            'password' => 'secreto-2026',
            'password_confirmation' => 'secreto-2026',
            'verificado' => '1',
        ]);

        $respuesta->assertRedirect(route('usuarios.index'));

        $creado = User::query()->where('email', 'peon.nuevo@ganaderia.test')->firstOrFail();

        $this->assertSame((int) $this->administrador->inquilino_id, (int) $creado->inquilino_id);
        $this->assertSame('Usuario', $creado->nombreDelRol());
        $this->assertTrue(Hash::check('secreto-2026', $creado->password), 'La contraseña no se guardó bien.');
        $this->assertTrue($creado->hasVerifiedEmail(), 'Marcado como verificado, debe entrar sin esperar al correo.');
        $this->assertTrue($creado->estaActivo());
    }

    public function test_sin_marcar_verificado_el_correo_queda_pendiente_y_no_entra(): void
    {
        $this->actingAs($this->administrador)->post('/usuarios', [
            'name' => 'Peón Pendiente',
            'email' => 'peon.pendiente@ganaderia.test',
            'rol' => 'usuario',
            'password' => 'secreto-2026',
            'password_confirmation' => 'secreto-2026',
        ])->assertRedirect(route('usuarios.index'));

        $creado = User::query()->where('email', 'peon.pendiente@ganaderia.test')->firstOrFail();

        $this->assertFalse($creado->hasVerifiedEmail());
        $this->actingAs($creado)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_un_rol_inventado_se_rechaza(): void
    {
        $this->actingAs($this->administrador)->post('/usuarios', [
            'name' => 'Jefe',
            'email' => 'jefe@ganaderia.test',
            'rol' => 'superjefe',
            'password' => 'secreto-2026',
            'password_confirmation' => 'secreto-2026',
        ])->assertSessionHasErrors('rol');

        $this->assertDatabaseMissing('users', ['email' => 'jefe@ganaderia.test']);
    }

    public function test_el_administrador_cambia_el_rol_de_otro(): void
    {
        $trabajador = $this->trabajador();

        $this->actingAs($this->administrador)
            ->put("/usuarios/{$trabajador->id}", [
                'name' => $trabajador->name,
                'email' => $trabajador->email,
                'rol' => 'administrador',
            ])
            ->assertRedirect(route('usuarios.index'));

        $this->assertTrue($trabajador->fresh()->esAdministrador());
    }

    public function test_nadie_se_cambia_el_rol_a_si_mismo(): void
    {
        $this->actingAs($this->administrador)
            ->put("/usuarios/{$this->administrador->id}", [
                'name' => $this->administrador->name,
                'email' => $this->administrador->email,
                'rol' => 'usuario',
            ])
            ->assertSessionHasErrors('rol');

        $this->assertTrue($this->administrador->fresh()->esAdministrador());
    }

    public function test_el_cambio_de_correo_invalida_la_verificacion(): void
    {
        $trabajador = $this->trabajador();
        $this->assertTrue($trabajador->hasVerifiedEmail());

        $this->actingAs($this->administrador)
            ->put("/usuarios/{$trabajador->id}", [
                'name' => $trabajador->name,
                'email' => 'nuevo.correo@ganaderia.test',
                'rol' => 'usuario',
            ])
            ->assertRedirect(route('usuarios.index'));

        $this->assertFalse($trabajador->fresh()->hasVerifiedEmail(), 'El correo nuevo no puede quedar verificado.');
    }

    public function test_el_administrador_desactiva_y_reactiva_una_cuenta(): void
    {
        $trabajador = $this->trabajador();

        $this->actingAs($this->administrador)
            ->patch("/usuarios/{$trabajador->id}/estado")
            ->assertRedirect();

        $this->assertFalse($trabajador->fresh()->estaActivo());
        $this->assertDatabaseHas('users', ['email' => $trabajador->email]); // no se borra

        $this->actingAs($this->administrador)
            ->patch("/usuarios/{$trabajador->id}/estado")
            ->assertRedirect();

        $this->assertTrue($trabajador->fresh()->estaActivo());
    }

    public function test_una_cuenta_desactivada_no_puede_iniciar_sesion(): void
    {
        $trabajador = $this->trabajador(['email' => 'desactivado@ganaderia.test']);
        $trabajador->forceFill(['activo' => false])->save();

        $this->post('/login', [
            'email' => 'desactivado@ganaderia.test',
            'password' => 'password',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_nadie_desactiva_su_propia_cuenta(): void
    {
        $this->actingAs($this->administrador)
            ->patch("/usuarios/{$this->administrador->id}/estado")
            ->assertSessionHas('warning');

        $this->assertTrue($this->administrador->fresh()->estaActivo());
    }

    public function test_no_se_puede_tocar_un_usuario_de_otra_ganaderia(): void
    {
        $ajeno = $this->deOtraGanaderia();

        $this->actingAs($this->administrador)->get("/usuarios/{$ajeno->id}/edit")->assertNotFound();

        $this->actingAs($this->administrador)
            ->put("/usuarios/{$ajeno->id}", [
                'name' => 'Secuestrado',
                'email' => 'secuestrado@ganaderia.test',
                'rol' => 'usuario',
            ])
            ->assertNotFound();

        $this->actingAs($this->administrador)
            ->patch("/usuarios/{$ajeno->id}/estado")
            ->assertNotFound();

        $this->assertSame('admin@esperanza.test', $ajeno->fresh()->email);
        $this->assertTrue($ajeno->fresh()->estaActivo());
    }

    public function test_el_menu_muestra_usuarios_solo_al_administrador(): void
    {
        $this->actingAs($this->administrador)
            ->get('/dashboard')
            ->assertSee('href="'.route('usuarios.index').'"', false);

        $this->actingAs($this->trabajador())
            ->get('/dashboard')
            ->assertDontSee('href="'.route('usuarios.index').'"', false);
    }
}
