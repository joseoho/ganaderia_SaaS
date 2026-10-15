<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sesion real: entrar por el formulario, no con actingAs().
 *
 * Esta prueba existe por un fallo que la suite no veia. Todas las demas usan
 * `actingAs($usuario)`, que coloca el usuario directamente en el guard y NO
 * consulta la tabla `users`. En una peticion HTTP de verdad el guard si
 * consulta, y ahi estaba el problema: el modelo User llevaba el filtro global
 * por ganaderia, pero la ganaderia se deduce DEL usuario, asi que para
 * encontrarlo (al entrar o al recuperar la sesion) todavia no hay ninguna
 * activa y el filtro, que falla cerrado, abortaba.
 *
 * En PHPUnit no se veia porque la suite corre en consola y alli el filtro es
 * permisivo (para poder sembrar datos). Aqui se fuerza el comportamiento de
 * HTTP con TenantScope::$forceFailClosed, que es justo para lo que se creo.
 *
 * REGLA QUE SE DERIVA DE ESTO: probar el acceso con actingAs() no basta.
 * Cualquier cambio en la autenticacion o en el filtro multi-tenant tiene que
 * pasar por aqui.
 */
class SesionRealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // HTTP real: sin ganaderia activa, el filtro DENIEGA en lugar de
        // devolver datos de todas las ganaderias.
        TenantScope::$forceFailClosed = true;
    }

    protected function tearDown(): void
    {
        TenantScope::$forceFailClosed = null;

        parent::tearDown();
    }

    public function test_se_puede_iniciar_sesion_con_el_formulario(): void
    {
        $respuesta = $this->post('/login', [
            'email' => 'admin@ganaderia.test',
            'password' => 'password',
        ]);

        $respuesta->assertRedirect();
        $this->assertTrue(auth()->check(), 'No se inicio la sesion.');
        $this->assertSame(
            'admin@ganaderia.test',
            auth()->user()->email
        );
    }

    public function test_tras_entrar_se_puede_abrir_el_panel(): void
    {
        $this->post('/login', [
            'email' => 'admin@ganaderia.test',
            'password' => 'password',
        ]);

        // Cada peticion recupera el usuario de la sesion: es el segundo punto
        // donde el guard consulta la tabla `users`.
        $this->get('/dashboard')->assertOk();
        $this->get('/animales')->assertOk();
    }

    public function test_una_contrasena_incorrecta_no_entra(): void
    {
        $this->post('/login', [
            'email' => 'admin@ganaderia.test',
            'password' => 'no-es-la-buena',
        ])->assertSessionHasErrors();

        $this->assertFalse(auth()->check());
    }

    public function test_la_sesion_aisla_las_ganaderias(): void
    {
        // Se entra como la ganaderia Demo y sus animales deben ser los suyos.
        $this->post('/login', [
            'email' => 'admin@ganaderia.test',
            'password' => 'password',
        ]);

        $usuario = User::query()->where('email', 'admin@ganaderia.test')->firstOrFail();
        $ajeno = Animal::withoutTenantScope()
            ->where('inquilino_id', '!=', $usuario->inquilino_id)
            ->firstOrFail();

        // La ganaderia activa ya esta resuelta, asi que el filtro SI limita.
        $this->assertSame(
            0,
            Animal::where('id', $ajeno->id)->count(),
            'Desde la sesion de una ganaderia se alcanzo un animal de otra.'
        );

        $this->actingAs($usuario)->get("/animales/{$ajeno->id}/edit")->assertNotFound();
    }
}
