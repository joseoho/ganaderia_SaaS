<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Verificación de correo.
 *
 * Estaba apagada de facto: `User` no implementaba `MustVerifyEmail`, así que el
 * middleware `verified` no bloqueaba nada y una cuenta con el correo sin
 * confirmar entraba al panel igual (comprobado: respondía 200).
 *
 * Con el contrato implementado, el circulo queda cerrado: sin verificar no se
 * entra, el enlace firmado confirma la cuenta, y el aviso permite reenviarlo.
 */
class VerificacionDeCorreoTest extends TestCase
{
    use RefreshDatabase;

    private function sinVerificar(): User
    {
        return User::factory()->unverified()->create();
    }

    public function test_un_usuario_sin_verificar_no_entra_al_panel(): void
    {
        $usuario = $this->sinVerificar();

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($usuario)
            ->get('/animales')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_un_usuario_verificado_entra_al_panel(): void
    {
        $usuario = User::factory()->create(); // la fabrica crea verificados

        $this->assertNotNull($usuario->email_verified_at);

        $this->actingAs($usuario)->get('/dashboard')->assertOk();
    }

    public function test_la_pantalla_de_aviso_dice_a_que_correo_se_envio(): void
    {
        $usuario = $this->sinVerificar();

        $respuesta = $this->actingAs($usuario)->get(route('verification.notice'));

        $respuesta->assertOk();
        $respuesta->assertSee('Verifica tu correo');
        $respuesta->assertSee($usuario->email);
        $respuesta->assertSee('Reenviar el enlace');
    }

    public function test_el_enlace_firmado_verifica_la_cuenta(): void
    {
        $usuario = $this->sinVerificar();

        $this->assertFalse($usuario->hasVerifiedEmail());

        $enlace = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $usuario->id, 'hash' => sha1($usuario->email)]
        );

        $this->actingAs($usuario)->get($enlace)->assertRedirect('/home');

        $this->assertTrue($usuario->fresh()->hasVerifiedEmail(), 'El enlace no marco el correo como verificado.');

        // Y después del enlace, ya entra.
        $this->actingAs($usuario)->get('/dashboard')->assertOk();
    }

    public function test_un_enlace_sin_firma_valida_no_verifica(): void
    {
        $usuario = $this->sinVerificar();

        $sinFirma = route('verification.verify', ['id' => $usuario->id, 'hash' => sha1($usuario->email)]);

        $this->actingAs($usuario)->get($sinFirma)->assertForbidden();

        $this->assertFalse($usuario->fresh()->hasVerifiedEmail(), 'Un enlace sin firma no debe verificar nada.');
    }

    public function test_un_enlace_con_otro_hash_no_verifica(): void
    {
        $usuario = $this->sinVerificar();

        // Firma válida, pero el hash del correo no es el suyo: el enlace de otra
        // persona no debe servir para verificar esta cuenta.
        $enlace = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $usuario->id, 'hash' => sha1('otro@correo.test')]
        );

        $this->actingAs($usuario)->get($enlace)->assertForbidden();

        $this->assertFalse($usuario->fresh()->hasVerifiedEmail());
    }

    public function test_el_boton_de_reenvio_manda_otro_correo(): void
    {
        Notification::fake();

        $usuario = $this->sinVerificar();

        $this->actingAs($usuario)
            ->post(route('verification.resend'))
            ->assertRedirect();

        Notification::assertSentTo($usuario, VerifyEmail::class);
    }

    public function test_el_alta_publica_manda_el_correo_y_deja_la_cuenta_sin_verificar(): void
    {
        Notification::fake();

        config(['app.registro_publico' => true]);

        $this->post('/register', [
            'name' => 'Dueño Nuevo',
            'ganaderia' => 'Finca Nueva',
            'email' => 'dueno.nuevo@finca.test',
            'password' => 'secreto-2026',
            'password_confirmation' => 'secreto-2026',
        ])->assertRedirect('/home');

        $creado = User::query()->where('email', 'dueno.nuevo@finca.test')->first();

        $this->assertNotNull($creado);
        $this->assertFalse($creado->hasVerifiedEmail(), 'Nadie nace verificado.');
        $this->assertTrue($creado->esAdministrador(), 'Quien registra la ganadería es su administrador.');

        Notification::assertSentTo($creado, VerifyEmail::class);

        // Y no puede entrar todavía.
        $this->actingAs($creado)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_el_alta_por_consola_deja_el_correo_sin_verificar(): void
    {
        $this->seed();

        $administrador = User::query()->where('email', 'admin@ganaderia.test')->firstOrFail();

        $codigo = \Illuminate\Support\Facades\Artisan::call('ganaderia:usuario', [
            'email' => 'trabajador@ganaderia.test',
            'nombre' => 'Trabajador de Prueba',
            '--inquilino' => (string) $administrador->inquilino_id,
            '--password' => 'secreto-2026',
        ]);

        $this->assertSame(0, $codigo, 'El alta por consola fallo: '.trim(\Illuminate\Support\Facades\Artisan::output()));

        $creado = User::query()->where('email', 'trabajador@ganaderia.test')->firstOrFail();

        $this->assertFalse($creado->hasVerifiedEmail(), 'El alta por consola no debe dar el correo por verificado.');
        $this->actingAs($creado)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }
}
