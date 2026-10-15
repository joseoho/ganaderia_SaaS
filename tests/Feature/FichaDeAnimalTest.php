<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalPotrero;
use App\Models\Potrero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ficha tecnica del animal (animales/show).
 *
 * Faltaba por cubrir, y por eso se escapo el fallo que reporto el usuario:
 *
 *     Attempt to read property "potrero" on null
 *
 * La vista cogia la ULTIMA asignacion de potrero y le leia el potrero. Si el
 * animal no habia estado nunca en ninguno, `last()` es null y la ficha entera
 * daba error 500. Y si habia estado, cogia la ultima DA IGUAL si ya habia
 * salido, asi que mostraba un potrero del que el animal ya se habia ido.
 */
class FichaDeAnimalTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::query()->where('email', 'admin@ganaderia.test')->firstOrFail();
    }

    private function animalSinPotrero(): Animal
    {
        $animal = Animal::factory()->create(['inquilino_id' => $this->usuario->inquilino_id]);

        $this->assertSame(0, $animal->animalPotreros()->count());

        return $animal;
    }

    private function potreroPropio(): Potrero
    {
        return Potrero::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();
    }

    public function test_un_animal_que_nunca_estuvo_en_un_potrero_muestra_su_ficha(): void
    {
        $animal = $this->animalSinPotrero();

        $respuesta = $this->actingAs($this->usuario)->get("/animales/{$animal->id}");

        $respuesta->assertOk();
        $respuesta->assertSee($animal->codigo_interno);
        $respuesta->assertSee('Sin potrero asignado');
    }

    public function test_muestra_el_potrero_en_el_que_esta_ahora(): void
    {
        $animal = $this->animalSinPotrero();
        $potrero = $this->potreroPropio();

        AnimalPotrero::create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'animal_id' => $animal->id,
            'potrero_id' => $potrero->id,
            'fecha_entrada' => now()->subDays(5)->toDateString(),
        ]);

        $respuesta = $this->actingAs($this->usuario)->get("/animales/{$animal->id}");

        $respuesta->assertOk();
        $respuesta->assertSee($potrero->nombre);
    }

    public function test_no_muestra_un_potrero_del_que_ya_salio(): void
    {
        $animal = $this->animalSinPotrero();
        $potrero = $this->potreroPropio();

        // Estuvo y ya salio: no es su potrero actual.
        AnimalPotrero::create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'animal_id' => $animal->id,
            'potrero_id' => $potrero->id,
            'fecha_entrada' => now()->subDays(30)->toDateString(),
            'fecha_salida' => now()->subDays(2)->toDateString(),
        ]);

        $respuesta = $this->actingAs($this->usuario)->get("/animales/{$animal->id}");

        $respuesta->assertOk();
        $respuesta->assertSee('Sin potrero asignado');
        $respuesta->assertDontSee($potrero->nombre);
    }

    public function test_la_ficha_tiene_una_tabla_bien_formada(): void
    {
        $animal = $this->animalSinPotrero();

        $html = $this->actingAs($this->usuario)->get("/animales/{$animal->id}")->getContent();

        // La fila del potrero estaba escrita como <tr><p>...</p></tr>, que es
        // HTML invalido: el navegador saca ese parrafo fuera de la tabla. Y la
        // fila del QR tenia su <th> y su <td> fuera de ningun <tr>.
        $this->assertDoesNotMatchRegularExpression(
            '/<tr>\s*<p>/i',
            $html,
            'Hay un <p> suelto dentro de un <tr>: la tabla se rompe al renderizar.'
        );

        $this->assertSame(
            substr_count(strtolower($html), '<tr>'),
            substr_count(strtolower($html), '</tr>'),
            'Hay etiquetas <tr> sin cerrar o de mas en la ficha.'
        );
    }

    public function test_no_se_puede_ver_la_ficha_de_un_animal_de_otra_ganaderia(): void
    {
        $ajeno = Animal::withoutTenantScope()
            ->where('inquilino_id', '!=', $this->usuario->inquilino_id)
            ->firstOrFail();

        $this->actingAs($this->usuario)->get("/animales/{$ajeno->id}")->assertNotFound();
    }
}
