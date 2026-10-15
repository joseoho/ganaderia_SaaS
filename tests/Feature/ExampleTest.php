<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Portada de la aplicacion.
 *
 * Sustituye al test de ejemplo de Laravel, que exigia que `/` devolviera 200
 * con la vista `welcome`. Esa vista pide `resources/css/app.css` (Tailwind v4),
 * que no esta entre los inputs de Vite, asi que en produccion daba error 500:
 * era el unico test en rojo.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_portada_redirige_al_login_sin_sesion(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_la_portada_redirige_al_panel_con_sesion(): void
    {
        $this->seed();

        $usuario = User::query()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();

        $this->actingAs($usuario)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_el_panel_responde_con_sesion(): void
    {
        $this->seed();

        $usuario = User::query()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();

        $this->actingAs($usuario)->get('/dashboard')->assertOk();
    }
}
