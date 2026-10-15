<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalPotrero;
use App\Models\Compra;
use App\Models\Gastos;
use App\Models\Inquilino;
use App\Models\InventarioInsumo;
use App\Models\Movilizacion;
use App\Models\Notificacion;
use App\Models\Potrero;
use App\Models\Rotacion;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Acciones de modulo que no son formularios: asignar y sacar animales de un
 * potrero, generar etiquetas y borrar registros.
 *
 * Era la ultima superficie sin cubrir, y el mismo patron se repite: lo que no
 * estaba probado escondia fallos.
 */
class AccionesDeModuloTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private int $otraGanaderia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::query()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();

        $this->otraGanaderia = (int) Inquilino::where('id', '!=', $this->usuario->inquilino_id)
            ->value('id');
    }

    private function potreroPropio(): Potrero
    {
        return Potrero::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();
    }

    private function animalPropio(): Animal
    {
        return Animal::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();
    }

    private function animalAjeno(): Animal
    {
        return Animal::withoutTenantScope()
            ->where('inquilino_id', $this->otraGanaderia)
            ->firstOrFail();
    }

    public function test_asignar_animales_propios_marca_el_potrero_ocupado(): void
    {
        $potrero = $this->potreroPropio();
        $animal = $this->animalPropio();

        $this->actingAs($this->usuario)
            ->post("/potreros/{$potrero->id}/asignar", ['animales' => [$animal->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('animal_potrero', [
            'animal_id' => $animal->id,
            'potrero_id' => $potrero->id,
            'inquilino_id' => $this->usuario->inquilino_id,
        ]);

        $this->assertSame('ocupado', $potrero->fresh()->estado);
    }

    public function test_no_se_puede_meter_en_el_potrero_un_animal_de_otra_ganaderia(): void
    {
        // El controlador creaba la asignacion con el inquilino del usuario pero
        // con el animal_id que viniera en la peticion, sin comprobar de quien
        // era el animal: cualquiera podia meter el ganado de otra ganaderia en
        // su potrero enviando su id.
        $potrero = $this->potreroPropio();
        $ajeno = $this->animalAjeno();

        $this->actingAs($this->usuario)
            ->post("/potreros/{$potrero->id}/asignar", ['animales' => [$ajeno->id]]);

        $this->assertDatabaseMissing('animal_potrero', [
            'animal_id' => $ajeno->id,
            'potrero_id' => $potrero->id,
        ]);
    }

    public function test_no_se_puede_meter_ganado_en_el_potrero_de_otra_ganaderia(): void
    {
        $potreroAjeno = Potrero::withoutTenantScope()
            ->where('inquilino_id', $this->otraGanaderia)
            ->firstOrFail();

        $this->actingAs($this->usuario)
            ->post("/potreros/{$potreroAjeno->id}/asignar", ['animales' => [$this->animalPropio()->id]])
            ->assertNotFound();
    }

    public function test_la_salida_cierra_la_ocupacion_y_deja_la_rotacion(): void
    {
        $potrero = $this->potreroPropio();
        $animal = $this->animalPropio();

        AnimalPotrero::create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'animal_id' => $animal->id,
            'potrero_id' => $potrero->id,
            'fecha_entrada' => now()->subDays(10)->toDateString(),
        ]);

        $potrero->update([
            'estado' => 'ocupado',
            'fecha_ultimo_ingreso' => now()->subDays(10)->toDateString(),
            'tamano' => 10,
        ]);

        $this->actingAs($this->usuario)
            ->post("/potreros/{$potrero->id}/salida")
            ->assertRedirect();

        $this->assertNotNull(
            AnimalPotrero::where('potrero_id', $potrero->id)->where('animal_id', $animal->id)->value('fecha_salida'),
            'La salida no cerro la ocupacion del animal.'
        );

        $this->assertSame(1, Rotacion::where('potrero_id', $potrero->id)->count(), 'No se registro la rotacion.');
    }

    public function test_la_salida_no_revienta_si_el_potrero_nunca_tuvo_ingreso(): void
    {
        // Un potrero recien creado no tiene fecha_ultimo_ingreso. El controlador
        // calcula now()->diffInDays($potrero->fecha_ultimo_ingreso), asi que hay
        // que comprobar que un nulo no lo tumba.
        $potrero = Potrero::factory()->create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'fecha_ultimo_ingreso' => null,
            'tamano' => 5,
        ]);

        $respuesta = $this->actingAs($this->usuario)->post("/potreros/{$potrero->id}/salida");

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'La salida de un potrero sin ingreso previo da error 500.');
    }

    public function test_generar_etiquetas_solo_incluye_animales_de_la_ganaderia_activa(): void
    {
        $propio = $this->animalPropio();
        $ajeno = $this->animalAjeno();

        $respuesta = $this->actingAs($this->usuario)->post('/animales/etiquetas/generar', [
            'animales' => [$propio->id, $ajeno->id],
        ]);

        $respuesta->assertOk();
        $respuesta->assertSee($propio->codigo_interno);
        $respuesta->assertDontSee($ajeno->codigo_interno);
    }

    public function test_generar_etiquetas_exige_seleccionar_animales(): void
    {
        $this->actingAs($this->usuario)
            ->post('/animales/etiquetas/generar', [])
            ->assertSessionHasErrors('animales');
    }

    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function modulosBorrables(): array
    {
        return [
            'potreros' => [Potrero::class, '/potreros/%d'],
            'compras' => [Compra::class, '/compras/%d'],
            'ventas' => [Venta::class, '/ventas/%d'],
            'gastos' => [Gastos::class, '/gastos/%d'],
            'inventario' => [InventarioInsumo::class, '/inventario/%d'],
            'movilizaciones' => [Movilizacion::class, '/movilizaciones/%d'],
            'notificaciones' => [Notificacion::class, '/notificaciones/%d'],
            'tratamientos' => [Tratamiento::class, '/tratamientos/%d'],
        ];
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('modulosBorrables')]
    public function test_el_dueno_puede_borrar_su_registro(string $modelo, string $ruta): void
    {
        // Se crea con la fabrica: un registro nuevo, sin dependencias, para
        // probar el borrado y no la integridad referencial de los datos sembrados.
        $atributos = ['inquilino_id' => $this->usuario->inquilino_id];

        // Una notificacion pertenece a un usuario y el controlador solo deja
        // borrarla a su dueño, asi que se crea a nombre de quien actua.
        if ($modelo === Notificacion::class) {
            $atributos['usuario_id'] = $this->usuario->id;
        }

        $registro = $modelo::factory()->create($atributos);

        $respuesta = $this->actingAs($this->usuario)->delete(sprintf($ruta, $registro->id));

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Error interno al borrar en '.$ruta);
        $this->assertDatabaseMissing($registro->getTable(), ['id' => $registro->id]);
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('modulosBorrables')]
    public function test_no_se_puede_borrar_el_registro_de_otra_ganaderia(string $modelo, string $ruta): void
    {
        $ajeno = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->otraGanaderia)
            ->first();

        if ($ajeno === null) {
            $this->markTestSkipped('La otra ganaderia no tiene registros de este modulo.');
        }

        $this->actingAs($this->usuario)
            ->delete(sprintf($ruta, $ajeno->id))
            ->assertNotFound();

        $this->assertDatabaseHas($ajeno->getTable(), ['id' => $ajeno->id]);
    }
}
