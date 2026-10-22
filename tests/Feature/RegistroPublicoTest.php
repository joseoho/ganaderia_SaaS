<?php

namespace Tests\Feature;

use App\Models\Inquilino;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Registro publico (auto-servicio) y alta de ganaderias desde consola.
 *
 * El registro estaba roto (usaba una clave inexistente) y ademas listaba todas
 * las ganaderias existentes a cualquiera que abriera la pagina. Ahora quien se
 * registra CREA su ganaderia y queda como su administrador, y el registro se
 * puede cerrar con REGISTRO_PUBLICO.
 */
class RegistroPublicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Cerrado por defecto, como en produccion.
        config(['app.registro_publico' => false]);
    }

    public function test_con_el_registro_cerrado_la_pantalla_no_existe(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Alguien',
            'ganaderia' => 'Ganaderia Pirata',
            'email' => 'pirata@ejemplo.test',
            'password' => 'contrasena-larga',
            'password_confirmation' => 'contrasena-larga',
        ])->assertNotFound();

        $this->assertNull(User::query()->where('email', 'pirata@ejemplo.test')->first());
    }

    /**
     * Con el registro cerrado, la pantalla de login no puede ofrecer NINGÚN
     * enlace de alta. La prueba anterior solo miraba el texto «Crear una
     * cuenta» y se le colaba el «Register» de la barra de navegación del
     * layout, que llevaba a un 404.
     */
    public function test_con_el_registro_cerrado_el_login_no_ofrece_crear_cuenta(): void
    {
        $respuesta = $this->get('/login');

        $respuesta->assertOk();
        $respuesta->assertDontSee('Crear una cuenta');
        $respuesta->assertDontSee('Registrarse');

        // Y ningún enlace que apunte al alta, se llame como se llame.
        $this->assertStringNotContainsString(
            '/register',
            (string) $respuesta->getContent(),
            'El login no debe enlazar al registro cuando está cerrado.'
        );

        // Pero sí dice a quién pedirle la cuenta.
        $respuesta->assertSee('El administrador de tu ganadería te la crea', false);
    }

    public function test_con_el_registro_abierto_la_pantalla_carga_y_no_lista_las_ganaderias(): void
    {
        config(['app.registro_publico' => true]);

        $existente = Inquilino::create([
            'nombre' => 'Ganaderia Confidencial S.A.',
            'plan_suscripcion' => 'premium',
            'fecha_registro' => now()->toDateString(),
        ]);

        $respuesta = $this->get('/register');

        $respuesta->assertOk();
        $respuesta->assertSee('Nombre de la ganadería');
        $respuesta->assertDontSee(
            $existente->nombre,
            false,
            'La pantalla de registro no debe mostrar las ganaderias que ya existen.'
        );
    }

    public function test_abierto_el_login_ofrece_crear_cuenta(): void
    {
        config(['app.registro_publico' => true]);

        $this->get('/login')->assertOk()->assertSee('Crear una cuenta');
    }

    public function test_el_registro_crea_la_ganaderia_y_su_administrador(): void
    {
        config(['app.registro_publico' => true]);

        $antes = Inquilino::count();

        $respuesta = $this->post('/register', [
            'name' => 'Maria Ganadera',
            'ganaderia' => 'Ganaderia Nueva',
            'email' => 'maria@ejemplo.test',
            'password' => 'contrasena-larga',
            'password_confirmation' => 'contrasena-larga',
        ]);

        $respuesta->assertSessionHasNoErrors();

        $this->assertSame($antes + 1, Inquilino::count(), 'No se creo la ganaderia.');

        $inquilino = Inquilino::where('nombre', 'Ganaderia Nueva')->firstOrFail();
        $usuario = User::query()->where('email', 'maria@ejemplo.test')->first();

        $this->assertNotNull($usuario, 'No se creo el usuario.');
        $this->assertSame((int) $inquilino->id, (int) $usuario->inquilino_id, 'El usuario quedo en otra ganaderia.');
        $this->assertTrue($usuario->esAdministrador(), 'Quien crea la ganaderia debe ser su administrador.');

        // El registro deja la sesion iniciada.
        $this->assertTrue(auth()->check(), 'Tras registrarse, la sesion debe quedar iniciada.');
        $this->assertSame((int) $usuario->id, (int) auth()->id());
    }

    public function test_el_registro_valida_los_datos(): void
    {
        config(['app.registro_publico' => true]);

        // Sin nombre de ganaderia y con las contrasenas distintas.
        $this->post('/register', [
            'name' => 'Sin Ganaderia',
            'email' => 'singanaderia@ejemplo.test',
            'password' => 'contrasena-larga',
            'password_confirmation' => 'otra-cosa',
        ])->assertSessionHasErrors(['ganaderia', 'password']);

        $this->assertNull(User::query()->where('email', 'singanaderia@ejemplo.test')->first());
    }

    public function test_el_registro_no_repite_correos(): void
    {
        config(['app.registro_publico' => true]);

        $this->post('/register', [
            'name' => 'Primera',
            'ganaderia' => 'Ganaderia Primera',
            'email' => 'repetido@ejemplo.test',
            'password' => 'contrasena-larga',
            'password_confirmation' => 'contrasena-larga',
        ])->assertSessionHasNoErrors();

        // Tras registrarse la sesion queda iniciada, y /register es solo para
        // invitados: hay que salir para intentar la segunda alta.
        auth()->logout();

        $this->post('/register', [
            'name' => 'Segunda',
            'ganaderia' => 'Ganaderia Segunda',
            'email' => 'repetido@ejemplo.test',
            'password' => 'contrasena-larga',
            'password_confirmation' => 'contrasena-larga',
        ])->assertSessionHasErrors('email');
    }

    public function test_el_comando_de_ganaderia_crea_la_ganaderia_y_su_administrador(): void
    {
        $codigo = Artisan::call('ganaderia:ganaderia', [
            'nombre' => 'Ganaderia Por Consola',
            'email' => 'jefe@consola.test',
            '--password' => 'contrasena-de-prueba',
        ]);

        $this->assertSame(0, $codigo);

        $inquilino = Inquilino::where('nombre', 'Ganaderia Por Consola')->first();
        $usuario = User::query()->where('email', 'jefe@consola.test')->first();

        $this->assertNotNull($inquilino);
        $this->assertNotNull($usuario);
        $this->assertSame((int) $inquilino->id, (int) $usuario->inquilino_id);
        $this->assertTrue($usuario->esAdministrador());
        $this->assertNotNull($usuario->email_verified_at, 'La cuenta aprovisionada por consola queda verificada.');
    }

    public function test_el_comando_de_ganaderia_admite_dejarla_sin_verificar(): void
    {
        Artisan::call('ganaderia:ganaderia', [
            'nombre' => 'Ganaderia Sin Verificar',
            'email' => 'pendiente@consola.test',
            '--password' => 'contrasena-de-prueba',
            '--sin-verificar' => true,
        ]);

        $usuario = User::query()->where('email', 'pendiente@consola.test')->first();

        $this->assertNotNull($usuario);
        $this->assertNull($usuario->email_verified_at);
    }

    public function test_el_comando_de_ganaderia_no_repite_correos(): void
    {
        Artisan::call('ganaderia:ganaderia', [
            'nombre' => 'Primera Ganaderia',
            'email' => 'repetido@consola.test',
            '--password' => 'contrasena-de-prueba',
        ]);

        $codigo = Artisan::call('ganaderia:ganaderia', [
            'nombre' => 'Segunda Ganaderia',
            'email' => 'repetido@consola.test',
            '--password' => 'contrasena-de-prueba',
        ]);

        $this->assertSame(1, $codigo);
        $this->assertSame(0, Inquilino::where('nombre', 'Segunda Ganaderia')->count(), 'No debe crearse la ganaderia si el correo ya existe.');
    }
}
