<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Inquilino;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cobro por comprobante (Fase A).
 *
 * Lo que se prueba, que es donde se rompen estos sistemas:
 *
 *  - Sin suscripción vigente, la finca queda en SOLO LECTURA: consulta sí,
 *    registrar no, y la pantalla de cobro siempre accesible.
 *  - El comprobante se guarda en el disco privado y la referencia no se repite.
 *  - Solo el operador entra al panel de pagos; un administrador de finca, no.
 *  - Aprobar extiende el periodo y deja quién lo revisó; rechazar exige motivo.
 */
class SuscripcionTest extends TestCase
{
    use RefreshDatabase;

    private function fincaVigente(): User
    {
        $inquilino = Inquilino::factory()->create();
        $admin = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'administrador',
        ]);

        return $admin;
    }

    private function operador(): User
    {
        return User::factory()->create([
            'inquilino_id' => null,
            'rol' => 'operador',
        ]);
    }

    public function test_una_finca_al_dia_puede_trabajar_normal(): void
    {
        $admin = $this->fincaVigente();

        $this->actingAs($admin)->get('/animales')->assertOk();
        $this->actingAs($admin)->get('/animales/create')->assertOk();
    }

    public function test_una_finca_vencida_queda_en_solo_lectura(): void
    {
        $inquilino = Inquilino::factory()->vencida()->create();
        $admin = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'administrador',
        ]);

        // Leer: sí.
        $this->actingAs($admin)->get('/animales')->assertOk();
        $this->actingAs($admin)->get('/dashboard')->assertOk();

        // Escribir: no; va a la pantalla de cobro con un aviso.
        $this->actingAs($admin)
            ->post('/animales', [
                'codigo_interno' => 'VENCIDA-1',
                'categoria' => 'bovino',
                'raza' => 'Holstein',
                'sexo' => 'H',
                'fecha_nacimiento' => '2024-01-01',
                'estado' => 'activo',
            ])
            ->assertRedirect(route('suscripcion.index'))
            ->assertSessionHas('warning');

        $this->assertDatabaseMissing('animales', ['codigo_interno' => 'VENCIDA-1']);
    }

    public function test_la_pantalla_de_cobro_funciona_con_la_suscripcion_vencida(): void
    {
        $inquilino = Inquilino::factory()->vencida()->create();
        $admin = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'administrador',
        ]);

        $this->actingAs($admin)
            ->get('/suscripcion')
            ->assertOk()
            ->assertSee('Vencida')
            ->assertSee('modo lectura');
    }

    public function test_el_cliente_sube_el_comprobante_y_queda_pendiente(): void
    {
        Storage::fake('local');

        $admin = $this->fincaVigente();
        $plan = Plan::query()->firstOrFail();

        $respuesta = $this->actingAs($admin)->post('/suscripcion/pagos', [
            'plan_id' => $plan->id,
            'monto' => 12,
            'moneda' => 'USD',
            'metodo' => 'pago_movil',
            'referencia' => 'REF-123456',
            'fecha_pago' => now()->toDateString(),
            'comprobante' => UploadedFile::fake()->image('comprobante.jpg'),
        ]);

        $respuesta->assertRedirect(route('suscripcion.index'));

        $pago = Pago::query()->where('referencia', 'REF-123456')->firstOrFail();

        $this->assertTrue($pago->estaPendiente());
        $this->assertSame((int) $admin->inquilino_id, (int) $pago->inquilino_id);
        $this->assertSame(12.0, (float) $pago->monto);

        // El archivo va al disco privado, no a public/.
        Storage::disk('local')->assertExists($pago->comprobante);
    }

    public function test_no_se_puede_repetir_la_referencia_de_un_pago(): void
    {
        Storage::fake('local');

        $admin = $this->fincaVigente();
        $plan = Plan::query()->firstOrFail();

        $datos = [
            'plan_id' => $plan->id,
            'monto' => 12,
            'moneda' => 'USD',
            'metodo' => 'transferencia',
            'referencia' => 'REF-REPETIDA',
            'fecha_pago' => now()->toDateString(),
            'comprobante' => UploadedFile::fake()->image('c.jpg'),
        ];

        $this->actingAs($admin)->post('/suscripcion/pagos', $datos);
        $this->actingAs($admin)
            ->post('/suscripcion/pagos', $datos)
            ->assertSessionHasErrors('referencia');

        $this->assertSame(1, Pago::query()->where('referencia', 'REF-REPETIDA')->count());
    }

    public function test_solo_el_operador_entra_al_panel_de_pagos(): void
    {
        $admin = $this->fincaVigente();
        $operador = $this->operador();

        $this->actingAs($admin)->get('/plataforma/pagos')->assertForbidden();
        $this->actingAs($operador)->get('/plataforma/pagos')->assertOk();
    }

    public function test_aprobar_extiende_la_suscripcion_y_deja_el_revisor(): void
    {
        Storage::fake('local');

        $inquilino = Inquilino::factory()->vencida()->create();
        $admin = User::factory()->create(['inquilino_id' => $inquilino->id, 'rol' => 'administrador']);
        $operador = $this->operador();
        $plan = Plan::query()->firstOrFail();

        $pago = Pago::create([
            'inquilino_id' => $inquilino->id,
            'plan_id' => $plan->id,
            'monto' => 12,
            'moneda' => 'USD',
            'metodo' => 'pago_movil',
            'referencia' => 'REF-OK',
            'comprobante' => 'comprobantes/'.$inquilino->id.'/x.jpg',
            'fecha_pago' => now()->toDateString(),
            'estado' => Pago::ESTADO_PENDIENTE,
        ]);

        $antes = $inquilino->suscripcionActual()->fin->copy();

        $this->actingAs($operador)->post("/plataforma/pagos/{$pago->id}/aprobar")->assertRedirect();

        $pago->refresh();
        $this->assertSame(Pago::ESTADO_APROBADO, $pago->estado);
        $this->assertSame($operador->id, $pago->revisado_por);
        $this->assertNotNull($pago->revisado_en);

        $nueva = $inquilino->fresh()->suscripcionActual();
        $this->assertTrue($nueva->fin->greaterThan($antes), 'El periodo no se extendió.');
        $this->assertTrue($inquilino->fresh()->tieneSuscripcionVigente());

        // Y ya puede volver a registrar.
        $this->actingAs($admin)->get('/animales/create')->assertOk();
    }

    public function test_rechazar_exige_motivo_y_lo_deja_visible(): void
    {
        $inquilino = Inquilino::factory()->vencida()->create();
        $operador = $this->operador();
        $plan = Plan::query()->firstOrFail();

        $pago = Pago::create([
            'inquilino_id' => $inquilino->id,
            'plan_id' => $plan->id,
            'monto' => 5,
            'moneda' => 'USD',
            'metodo' => 'otro',
            'referencia' => 'REF-MAL',
            'comprobante' => 'x.jpg',
            'fecha_pago' => now()->toDateString(),
            'estado' => Pago::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($operador)
            ->post("/plataforma/pagos/{$pago->id}/rechazar", ['motivo_rechazo' => ''])
            ->assertSessionHasErrors('motivo_rechazo');

        $this->actingAs($operador)
            ->post("/plataforma/pagos/{$pago->id}/rechazar", ['motivo_rechazo' => 'El monto no coincide con el plan.'])
            ->assertRedirect();

        $pago->refresh();
        $this->assertSame(Pago::ESTADO_RECHAZADO, $pago->estado);
        $this->assertSame('El monto no coincide con el plan.', $pago->motivo_rechazo);
    }

    public function test_un_admin_de_finca_no_puede_aprobar_pagos(): void
    {
        $admin = $this->fincaVigente();
        $plan = Plan::query()->firstOrFail();

        $pago = Pago::create([
            'inquilino_id' => $admin->inquilino_id,
            'plan_id' => $plan->id,
            'monto' => 12,
            'moneda' => 'USD',
            'metodo' => 'otro',
            'referencia' => 'REF-ADMIN',
            'comprobante' => 'x.jpg',
            'fecha_pago' => now()->toDateString(),
            'estado' => Pago::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($admin)->post("/plataforma/pagos/{$pago->id}/aprobar")->assertForbidden();
        $this->assertTrue($pago->fresh()->estaPendiente());
    }

    public function test_el_alta_por_consola_da_catorce_dias_de_prueba(): void
    {
        \Illuminate\Support\Facades\Artisan::call('ganaderia:ganaderia', [
            'nombre' => 'Finca Nueva',
            'email' => 'nueva@finca.test',
            '--password' => 'secreto-2026',
        ]);

        $inquilino = Inquilino::query()->where('nombre', 'Finca Nueva')->firstOrFail();
        $suscripcion = $inquilino->suscripcionActual();

        $this->assertNotNull($suscripcion);
        $this->assertTrue($suscripcion->es_prueba);
        $this->assertTrue($inquilino->tieneSuscripcionVigente());
        $this->assertSame(0, Animal::where('inquilino_id', $inquilino->id)->count());
    }

    /**
     * Regresión: el operador no tiene ganadería, así que si entra en una
     * pantalla de finca el filtro de tenant (que falla cerrado) reventaba con un
     * 500 («Consulta a [App\Models\Tratamiento] sin ganaderia activa»).
     */
    public function test_el_operador_va_a_su_panel_y_no_revienta_en_las_pantallas_de_finca(): void
    {
        $operador = $this->operador();

        // Al entrar, directo a pagos (no a /home de una finca).
        $this->post('/login', ['email' => $operador->email, 'password' => 'password'])
            ->assertRedirect(route('plataforma.pagos'));

        // Y si abre una pantalla de finca, se le devuelve a su panel.
        foreach (['/home', '/dashboard', '/reportes', '/tratamientos'] as $ruta) {
            $this->actingAs($operador)->get($ruta)->assertRedirect(route('plataforma.pagos'));
        }
    }

    public function test_una_cuenta_sin_ganaderia_que_no_es_operador_recibe_un_403_claro(): void
    {
        $suelto = User::factory()->create(['inquilino_id' => null, 'rol' => 'usuario']);

        $this->actingAs($suelto)->get('/tratamientos')->assertForbidden();
    }
}
