<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Inquilino;
use App\Models\Notificacion;
use App\Models\Plan;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Fase B del cobro: límites del plan, pantalla de planes y avisos de vencimiento.
 */
class LimitesYPlanesTest extends TestCase
{
    use RefreshDatabase;

    private function fincaConPlan(int $limiteAnimales, int $limiteUsuarios = 2): array
    {
        $plan = Plan::query()->create([
            'nombre' => 'Plan de prueba',
            'precio_usd' => 10,
            'dias' => 30,
            'limite_animales' => $limiteAnimales,
            'limite_usuarios' => $limiteUsuarios,
            'activo' => true,
            'orden' => 99,
        ]);

        $inquilino = Inquilino::factory()->create();
        // La fábrica deja un plan vigente del seeder; aquí manda el de la prueba.
        $inquilino->suscripciones()->delete();
        Suscripcion::create([
            'inquilino_id' => $inquilino->id,
            'plan_id' => $plan->id,
            'inicio' => now()->subDays(2)->toDateString(),
            'fin' => now()->addDays(28)->toDateString(),
        ]);

        $admin = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'administrador',
        ]);

        return [$inquilino, $admin, $plan];
    }

    private function datosAnimal(string $codigo): array
    {
        return [
            'codigo_interno' => $codigo,
            'categoria' => 'bovino',
            'raza' => 'Holstein',
            'sexo' => 'H',
            'fecha_nacimiento' => '2024-01-01',
            'estado' => 'activo',
        ];
    }

    public function test_el_tope_de_animales_del_plan_se_respeta(): void
    {
        [$inquilino, $admin] = $this->fincaConPlan(2);

        $this->actingAs($admin)->post('/animales', $this->datosAnimal('T-1'))->assertRedirect();
        $this->actingAs($admin)->post('/animales', $this->datosAnimal('T-2'))->assertRedirect();

        $this->assertSame(2, Animal::where('inquilino_id', $inquilino->id)->count());

        // El tercero ya no cabe: aviso claro y nada creado.
        $this->actingAs($admin)
            ->post('/animales', $this->datosAnimal('T-3'))
            ->assertSessionHas('warning');

        $this->assertSame(2, Animal::where('inquilino_id', $inquilino->id)->count());
        $this->assertDatabaseMissing('animales', ['codigo_interno' => 'T-3']);
    }

    public function test_el_tope_de_usuarios_del_plan_se_respeta(): void
    {
        [$inquilino, $admin] = $this->fincaConPlan(10, 1);

        // El plan admite 1 usuario y ya existe el administrador.
        $this->actingAs($admin)
            ->post('/usuarios', [
                'name' => 'Segundo',
                'email' => 'segundo@finca.test',
                'rol' => 'usuario',
                'password' => 'secreto-2026',
                'password_confirmation' => 'secreto-2026',
            ])
            ->assertSessionHas('warning');

        $this->assertDatabaseMissing('users', ['email' => 'segundo@finca.test']);
    }

    public function test_un_plan_sin_limites_no_bloquea(): void
    {
        [$inquilino, $admin] = $this->fincaConPlan(0, 0);
        $plan = $inquilino->planActual();
        $plan->update(['limite_animales' => null, 'limite_usuarios' => null]);

        foreach (['S-1', 'S-2', 'S-3'] as $codigo) {
            $this->actingAs($admin)->post('/animales', $this->datosAnimal($codigo))->assertRedirect();
        }

        $this->assertSame(3, Animal::where('inquilino_id', $inquilino->id)->count());
    }

    public function test_el_operador_ve_y_edita_los_planes(): void
    {
        $operador = User::factory()->create(['inquilino_id' => null, 'rol' => 'operador']);
        $plan = Plan::query()->firstOrFail();

        $this->actingAs($operador)->get('/plataforma/planes')->assertOk()->assertSee('Precio (USD)');

        $this->actingAs($operador)
            ->put("/plataforma/planes/{$plan->id}", [
                'nombre' => 'Básico Plus',
                'precio_usd' => 15,
                'dias' => 30,
                'limite_animales' => 80,
                'limite_usuarios' => 3,
                'activo' => '1',
            ])
            ->assertRedirect();

        $plan->refresh();
        $this->assertSame('Básico Plus', $plan->nombre);
        $this->assertSame('15.00', (string) $plan->precio_usd);
        $this->assertSame(80, $plan->limite_animales);
    }

    public function test_un_administrador_de_finca_no_edita_planes(): void
    {
        $plan = Plan::query()->firstOrFail();
        $inquilino = Inquilino::factory()->create();
        $admin = User::factory()->create(['inquilino_id' => $inquilino->id, 'rol' => 'administrador']);

        $this->actingAs($admin)->get('/plataforma/planes')->assertForbidden();
        $this->actingAs($admin)
            ->put("/plataforma/planes/{$plan->id}", [
                'nombre' => 'Gratis', 'precio_usd' => 0, 'dias' => 30,
            ])
            ->assertForbidden();
    }

    public function test_el_precio_no_puede_ser_negativo(): void
    {
        $operador = User::factory()->create(['inquilino_id' => null, 'rol' => 'operador']);
        $plan = Plan::query()->firstOrFail();
        $precioOriginal = $plan->precio_usd;

        $this->actingAs($operador)
            ->put("/plataforma/planes/{$plan->id}", [
                'nombre' => $plan->nombre,
                'precio_usd' => -5,
                'dias' => 30,
            ])
            ->assertSessionHasErrors('precio_usd');

        $this->assertSame((string) $precioOriginal, (string) $plan->fresh()->precio_usd);
    }

    public function test_el_comando_avisa_del_vencimiento_y_no_duplica(): void
    {
        $inquilino = Inquilino::factory()->vencida()->create();
        $admin = User::factory()->create(['inquilino_id' => $inquilino->id, 'rol' => 'administrador']);

        // Vence en 3 días.
        $inquilino->suscripciones()->update([
            'inicio' => now()->subDays(27)->toDateString(),
            'fin' => now()->addDays(3)->toDateString(),
        ]);

        Artisan::call('suscripciones:avisar');

        $avisos = Notificacion::withoutTenantScope()
            ->where('inquilino_id', $inquilino->id)
            ->where('tipo', 'suscripcion')
            ->get();

        $this->assertCount(1, $avisos, 'Debe crear un aviso por administrador.');
        $this->assertSame($admin->id, $avisos->first()->usuario_id);
        $this->assertStringContainsString('3 días', $avisos->first()->mensaje);

        // Correrlo otra vez el mismo día no duplica.
        Artisan::call('suscripciones:avisar');

        $this->assertSame(1, Notificacion::withoutTenantScope()
            ->where('inquilino_id', $inquilino->id)
            ->where('tipo', 'suscripcion')
            ->count());
    }
}
