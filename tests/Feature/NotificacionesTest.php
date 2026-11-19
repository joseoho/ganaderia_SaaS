<?php

namespace Tests\Feature;

use App\Models\Inquilino;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los avisos del cobro (vencimiento, pago aprobado o rechazado) tienen que
 * llegar al usuario y poder marcarse como leídos.
 *
 * El módulo existía pero el enlace del menú estaba comentado: el aviso se
 * creaba y nadie lo veía.
 */
class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioConAviso(): array
    {
        $inquilino = Inquilino::factory()->create();
        $usuario = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'administrador',
        ]);

        Notificacion::create([
            'inquilino_id' => $inquilino->id,
            'usuario_id' => $usuario->id,
            'mensaje' => 'Tu suscripción vence en 3 días.',
            'tipo' => 'suscripcion',
            'fecha_envio' => now()->toDateString(),
            'estado' => 'pendiente',
        ]);

        return [$inquilino, $usuario];
    }

    public function test_el_menu_muestra_notificaciones_con_el_contador(): void
    {
        [, $usuario] = $this->usuarioConAviso();

        $respuesta = $this->actingAs($usuario)->get('/dashboard');

        $respuesta->assertOk();
        $respuesta->assertSee(route('notificaciones.index'), false);
        $respuesta->assertSee('Notificaciones');
        // El contador del menú y la tarjeta del panel dicen 1.
        $respuesta->assertSee('rounded-pill">1', false);
    }

    public function test_la_pantalla_lista_los_avisos_del_usuario(): void
    {
        [, $usuario] = $this->usuarioConAviso();

        $this->actingAs($usuario)
            ->get('/notificaciones')
            ->assertOk()
            ->assertSee('Tu suscripción vence en 3 días.')
            ->assertSee('Marcar todas como leídas');
    }

    public function test_marcar_todas_baja_el_contador(): void
    {
        [$inquilino, $usuario] = $this->usuarioConAviso();

        $this->actingAs($usuario)
            ->post(route('notificaciones.leer.todas'))
            ->assertRedirect();

        $this->assertSame(0, Notificacion::withoutTenantScope()
            ->where('inquilino_id', $inquilino->id)
            ->where('usuario_id', $usuario->id)
            ->where('estado', 'pendiente')
            ->count());

        // Y ya no aparece el contador rojo.
        $this->actingAs($usuario)->get('/dashboard')->assertDontSee('rounded-pill">1', false);
    }

    public function test_no_toca_los_avisos_de_otro_usuario(): void
    {
        [$inquilino, $usuario] = $this->usuarioConAviso();

        $otro = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'usuario',
        ]);

        Notificacion::create([
            'inquilino_id' => $inquilino->id,
            'usuario_id' => $otro->id,
            'mensaje' => 'Aviso del compañero.',
            'tipo' => 'suscripcion',
            'fecha_envio' => now()->toDateString(),
            'estado' => 'pendiente',
        ]);

        $this->actingAs($usuario)->post(route('notificaciones.leer.todas'));

        $this->assertSame(1, Notificacion::withoutTenantScope()
            ->where('usuario_id', $otro->id)
            ->where('estado', 'pendiente')
            ->count(), 'Se marcaron avisos de otra persona.');
    }
}
