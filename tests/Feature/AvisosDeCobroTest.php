<?php

namespace Tests\Feature;

use App\Mail\AvisoParaOperador;
use App\Models\Inquilino;
use App\Models\Notificacion;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Avisos del cobro: al cliente cuando se revisa su pago y al operador cuando
 * entra un comprobante.
 *
 * Sin esto, el cliente se queda esperando sin saber si lo aprobaron (o por qué
 * no), y el operador solo se entera si abre el panel por casualidad.
 */
class AvisosDeCobroTest extends TestCase
{
    use RefreshDatabase;

    private function preparar(): array
    {
        $inquilino = Inquilino::factory()->vencida()->create();

        $admin = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'administrador',
        ]);

        $operador = User::factory()->create([
            'inquilino_id' => null,
            'rol' => 'operador',
            'email' => 'cobros@plataforma.test',
        ]);

        $plan = Plan::query()->firstOrFail();

        $pago = Pago::create([
            'inquilino_id' => $inquilino->id,
            'plan_id' => $plan->id,
            'monto' => 12,
            'moneda' => 'USD',
            'metodo' => 'pago_movil',
            'referencia' => 'REF-AVISO-1',
            'comprobante' => 'comprobantes/'.$inquilino->id.'/x.jpg',
            'fecha_pago' => now()->toDateString(),
            'estado' => Pago::ESTADO_PENDIENTE,
        ]);

        return [$inquilino, $admin, $operador, $pago];
    }

    public function test_aprobar_avisa_al_cliente(): void
    {
        [$inquilino, $admin, $operador, $pago] = $this->preparar();

        $this->actingAs($operador)->post("/plataforma/pagos/{$pago->id}/aprobar");

        $aviso = Notificacion::withoutTenantScope()
            ->where('inquilino_id', $inquilino->id)
            ->where('usuario_id', $admin->id)
            ->get()
            ->first(fn (Notificacion $n): bool => str_contains($n->mensaje, 'aprobado'));

        $this->assertNotNull($aviso, 'El cliente no recibió aviso de aprobación.');
        $this->assertStringContainsString('REF-AVISO-1', $aviso->mensaje);
        $this->assertStringContainsString('activa hasta el', $aviso->mensaje);
    }

    public function test_rechazar_avisa_al_cliente_con_el_motivo(): void
    {
        [$inquilino, $admin, $operador, $pago] = $this->preparar();

        $this->actingAs($operador)->post("/plataforma/pagos/{$pago->id}/rechazar", [
            'motivo_rechazo' => 'El monto no coincide con el plan.',
        ]);

        $aviso = Notificacion::withoutTenantScope()
            ->where('inquilino_id', $inquilino->id)
            ->where('usuario_id', $admin->id)
            ->get()
            ->first(fn (Notificacion $n): bool => str_contains($n->mensaje, 'rechazado'));

        $this->assertNotNull($aviso, 'El cliente no recibió aviso de rechazo.');
        $this->assertStringContainsString('El monto no coincide con el plan.', $aviso->mensaje);
        $this->assertStringContainsString('Sube otro comprobante', $aviso->mensaje);
    }

    public function test_subir_un_comprobante_avisa_al_operador_por_correo(): void
    {
        Mail::fake();
        Storage::fake('local');

        $inquilino = Inquilino::factory()->create();
        $admin = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'administrador',
        ]);
        User::factory()->create([
            'inquilino_id' => null,
            'rol' => 'operador',
            'email' => 'cobros@plataforma.test',
        ]);
        $plan = Plan::query()->firstOrFail();

        $this->actingAs($admin)->post('/suscripcion/pagos', [
            'plan_id' => $plan->id,
            'monto' => 12,
            'moneda' => 'USD',
            'metodo' => 'transferencia',
            'referencia' => 'REF-CORREO-1',
            'fecha_pago' => now()->toDateString(),
            'comprobante' => UploadedFile::fake()->image('c.jpg'),
        ])->assertRedirect(route('suscripcion.index'));

        Mail::assertSent(AvisoParaOperador::class, function (AvisoParaOperador $correo): bool {
            return $correo->hasTo('cobros@plataforma.test')
                && str_contains($correo->asunto, 'Nuevo comprobante')
                && str_contains($correo->texto, 'REF-CORREO-1');
        });
    }

    public function test_sin_correo_configurado_el_comprobante_se_guarda_igual(): void
    {
        Storage::fake('local');

        $inquilino = Inquilino::factory()->create();
        $admin = User::factory()->create(['inquilino_id' => $inquilino->id, 'rol' => 'administrador']);
        $plan = Plan::query()->firstOrFail();

        // Forzamos que el correo reviente: el pago debe entrar de todos modos.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP caído'));

        $this->actingAs($admin)->post('/suscripcion/pagos', [
            'plan_id' => $plan->id,
            'monto' => 12,
            'moneda' => 'USD',
            'metodo' => 'transferencia',
            'referencia' => 'REF-SMTP-1',
            'fecha_pago' => now()->toDateString(),
            'comprobante' => UploadedFile::fake()->image('c.jpg'),
        ])->assertRedirect(route('suscripcion.index'));

        $this->assertDatabaseHas('pagos', ['referencia' => 'REF-SMTP-1']);
    }
}
