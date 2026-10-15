<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalPotrero;
use App\Models\Compra;
use App\Models\Gastos;
use App\Models\Genealogia;
use App\Models\Inquilino;
use App\Models\InventarioInsumo;
use App\Models\Movilizacion;
use App\Models\Notificacion;
use App\Models\Potrero;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\RegistroVacuna;
use App\Models\Reporte;
use App\Models\Reproduccion;
use App\Models\Rotacion;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Vacuna;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Todas las fabricas producen registros validos y de la ganaderia activa.
 *
 * Sin esto, el fallo tipico es una fabrica que crea un registro valido pero de
 * OTRA ganaderia (porque creo su propio inquilino), y entonces las pruebas de
 * aislamiento dan resultados enganosos. La segunda prueba es la importante.
 */
class FabricasTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::query()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();

        // Sesion iniciada: la ganaderia activa queda resuelta.
        $this->actingAs($this->usuario);
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function modelos(): array
    {
        return [
            'Animal' => [Animal::class],
            'AnimalPotrero' => [AnimalPotrero::class],
            'Compra' => [Compra::class],
            'Gastos' => [Gastos::class],
            'Genealogia' => [Genealogia::class],
            'InventarioInsumo' => [InventarioInsumo::class],
            'Movilizacion' => [Movilizacion::class],
            'Notificacion' => [Notificacion::class],
            'Potrero' => [Potrero::class],
            'ProduccionCarne' => [ProduccionCarne::class],
            'ProduccionLeche' => [ProduccionLeche::class],
            'RegistroVacuna' => [RegistroVacuna::class],
            'Reporte' => [Reporte::class],
            'Reproduccion' => [Reproduccion::class],
            'Rotacion' => [Rotacion::class],
            'Tratamiento' => [Tratamiento::class],
            'Vacuna' => [Vacuna::class],
            'Venta' => [Venta::class],
        ];
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('modelos')]
    public function test_la_fabrica_crea_un_registro_valido(string $modelo): void
    {
        $registro = $modelo::factory()->create();

        $this->assertNotNull($registro->getKey(), 'La fabrica de '.$modelo.' no creo el registro.');
        $this->assertNotNull($registro->inquilino_id, 'La fabrica de '.$modelo.' dejo inquilino_id vacio.');
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('modelos')]
    public function test_la_fabrica_usa_la_ganaderia_activa(string $modelo): void
    {
        $registro = $modelo::factory()->create();

        $this->assertSame(
            (int) $this->usuario->inquilino_id,
            (int) $registro->inquilino_id,
            'La fabrica de '.$modelo.' creo el registro en otra ganaderia: los datos de prueba cruzarian inquilinos.'
        );
    }

    public function test_la_fabrica_de_inquilino_crea_una_ganaderia_nueva(): void
    {
        $antes = Inquilino::count();

        Inquilino::factory()->create();

        $this->assertSame($antes + 1, Inquilino::count());
    }

    public function test_las_fabricas_sin_sesion_tambien_crean_registros_validos(): void
    {
        // Sin ganaderia activa la columna es NOT NULL, asi que la fabrica tiene
        // que crear una: es el caso de un comando de consola o de una prueba
        // que prepare datos por su cuenta.
        auth()->logout();
        $this->app->forgetInstance(\App\Support\TenantContext::class);

        $animal = Animal::factory()->create();

        $this->assertNotNull($animal->getKey());
        $this->assertNotNull($animal->inquilino_id);
    }
}
