<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Inquilino;
use App\Models\Potrero;
use App\Models\User;
use App\Support\DatosDeEjemplo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Alta de una finca con datos de ejemplo (`--demo`).
 *
 * Una finca recién vendida no puede nacer vacía: el dueño abre el panel, ve
 * ceros y no entiende qué compró. Con `--demo` nace con potreros, animales,
 * producción y finanzas, marcada como demo, y se puede vaciar de un botón
 * cuando cargue los datos reales.
 */
class DatosDeEjemploTest extends TestCase
{
    use RefreshDatabase;

    private function altaDemo(string $email = 'dueno.demo@finca.test'): Inquilino
    {
        $codigo = Artisan::call('ganaderia:ganaderia', [
            'nombre' => 'Finca Demo '.$email,
            'email' => $email,
            '--password' => 'secreto-2026',
            '--demo' => true,
        ]);

        $this->assertSame(0, $codigo, 'El alta con demo falló: '.Artisan::output());

        return Inquilino::query()->where('nombre', 'Finca Demo '.$email)->firstOrFail();
    }

    private function administradorDe(Inquilino $inquilino): User
    {
        return User::query()->where('inquilino_id', $inquilino->id)->firstOrFail();
    }

    public function test_el_alta_con_demo_siembra_datos_y_marca_la_finca(): void
    {
        $inquilino = $this->altaDemo();

        $this->assertTrue($inquilino->esDemo(), 'La finca debe quedar marcada como demo.');
        $this->assertGreaterThan(0, Animal::where('inquilino_id', $inquilino->id)->count(), 'Sin animales no hay nada que enseñar.');
        $this->assertGreaterThan(0, Potrero::where('inquilino_id', $inquilino->id)->count());
        $this->assertDatabaseHas('produccion_leche', ['inquilino_id' => $inquilino->id]);
        $this->assertDatabaseHas('compras', ['inquilino_id' => $inquilino->id]);
        $this->assertDatabaseHas('ventas', ['inquilino_id' => $inquilino->id]);
    }

    public function test_el_alta_sin_demo_deja_la_finca_vacia(): void
    {
        $codigo = Artisan::call('ganaderia:ganaderia', [
            'nombre' => 'Finca Vacia',
            'email' => 'vacia@finca.test',
            '--password' => 'secreto-2026',
        ]);

        $this->assertSame(0, $codigo);

        $inquilino = Inquilino::query()->where('nombre', 'Finca Vacia')->firstOrFail();

        $this->assertFalse($inquilino->esDemo());
        $this->assertSame(0, Animal::where('inquilino_id', $inquilino->id)->count());
    }

    public function test_el_aviso_de_datos_de_ejemplo_solo_aparece_en_una_finca_demo(): void
    {
        $demo = $this->altaDemo('demo.aviso@finca.test');

        $this->actingAs($this->administradorDe($demo))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Esta ganadería tiene datos de ejemplo')
            ->assertSee('Borrar datos de ejemplo');

        // Una finca normal no lleva el aviso.
        $this->seed();
        $normal = User::query()->where('email', 'admin@ganaderia.test')->firstOrFail();

        $this->actingAs($normal)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Esta ganadería tiene datos de ejemplo');
    }

    public function test_vaciar_borra_los_datos_y_quita_el_aviso(): void
    {
        $inquilino = $this->altaDemo('demo.vaciar@finca.test');
        $administrador = $this->administradorDe($inquilino);

        $this->assertGreaterThan(0, Animal::where('inquilino_id', $inquilino->id)->count());

        $respuesta = $this->actingAs($administrador)->post('/demo/vaciar');

        $respuesta->assertRedirect(route('dashboard'));
        $respuesta->assertSessionHas('success');

        $this->assertSame(0, Animal::where('inquilino_id', $inquilino->id)->count(), 'Quedaron animales.');
        $this->assertSame(0, Potrero::where('inquilino_id', $inquilino->id)->count(), 'Quedaron potreros.');
        $this->assertSame(0, \App\Models\ProduccionLeche::where('inquilino_id', $inquilino->id)->count());
        $this->assertSame(0, \App\Models\Compra::where('inquilino_id', $inquilino->id)->count());

        $this->assertFalse($inquilino->fresh()->esDemo(), 'Tras vaciar deja de ser demo.');

        // Las cuentas de usuario no se tocan.
        $this->assertDatabaseHas('users', ['id' => $administrador->id]);

        // Y el aviso desaparece.
        $this->actingAs($administrador)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Esta ganadería tiene datos de ejemplo');
    }

    public function test_el_usuario_normal_no_puede_vaciar(): void
    {
        $inquilino = $this->altaDemo('demo.usuario@finca.test');

        $usuario = User::factory()->create([
            'inquilino_id' => $inquilino->id,
            'rol' => 'usuario',
        ]);

        $this->actingAs($usuario)->post('/demo/vaciar')->assertForbidden();

        // No se borró nada.
        $this->assertGreaterThan(0, Animal::where('inquilino_id', $inquilino->id)->count());
        $this->assertTrue($inquilino->fresh()->esDemo());
    }

    public function test_una_finca_que_no_es_demo_no_se_puede_vaciar(): void
    {
        $this->seed();
        $administrador = User::query()->where('email', 'admin@ganaderia.test')->firstOrFail();

        $this->actingAs($administrador)->post('/demo/vaciar')->assertNotFound();

        // Y los datos de la Demo siguen ahí.
        $this->assertGreaterThan(0, Animal::where('inquilino_id', $administrador->inquilino_id)->count());
    }

    public function test_limpiar_no_toca_los_usuarios_ni_otras_ganaderias(): void
    {
        $this->seed();
        $otra = Inquilino::query()->where('nombre', 'Ganaderia La Esperanza')->firstOrFail();
        $animalesOtra = Animal::where('inquilino_id', $otra->id)->count();

        $inquilino = $this->altaDemo('demo.aislado@finca.test');
        DatosDeEjemplo::limpiar($inquilino);

        $this->assertSame($animalesOtra, Animal::where('inquilino_id', $otra->id)->count(), 'Se tocó otra ganadería.');
        $this->assertGreaterThan(0, User::where('inquilino_id', $inquilino->id)->count(), 'Se borraron los usuarios.');
    }
}
