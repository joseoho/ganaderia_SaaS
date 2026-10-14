<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Genealogia;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\RegistroVacuna;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QR de edicion completa del animal: la pantalla mas ambiciosa de la aplicacion.
 *
 * Actualiza el animal y, ademas, la ultima produccion de leche y de carne, el
 * ultimo tratamiento y la ultima vacuna, creando el registro si no existia.
 * Era la ultima accion sin ninguna prueba.
 */
class QrEdicionCompletaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::withoutTenantScope()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();
    }

    /**
     * Animal recien creado: sin produccion, tratamientos ni vacunas previas, de
     * modo que se ejercita la rama que CREA los registros (la que falla).
     */
    private function animalSinHistorial(): Animal
    {
        $animal = Animal::factory()->create([
            'inquilino_id' => $this->usuario->inquilino_id,
        ]);

        $this->assertSame(0, $animal->produccionLeche()->count());
        $this->assertSame(0, $animal->produccionCarne()->count());

        return $animal;
    }

    /**
     * @return array<string, string>
     */
    private function camposDelFormulario(Animal $animal, array $extra = []): array
    {
        return array_merge([
            'codigo_interno' => $animal->codigo_interno,
            'raza' => 'Raza actualizada',
            'sexo' => 'M',
            'peso_actual' => '321.50',
            'fecha_nacimiento' => '2024-03-01',
            'estado' => 'activo',
        ], $extra);
    }

    public function test_actualiza_los_datos_basicos_del_animal(): void
    {
        $animal = $this->animalSinHistorial();

        $respuesta = $this->actingAs($this->usuario)
            ->post("/animal/qr/{$animal->id}/actualizar-todo", $this->camposDelFormulario($animal));

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Guardar el QR de edicion completa da error interno.');

        $fresco = $animal->fresh();

        $this->assertSame('Raza actualizada', $fresco->raza);
        $this->assertSame('M', $fresco->sexo);
        $this->assertSame('2024-03-01', \App\Support\Formato::fecha($fresco->fecha_nacimiento));
    }

    public function test_crea_los_registros_de_produccion_tratamiento_y_vacuna(): void
    {
        $animal = $this->animalSinHistorial();

        $respuesta = $this->actingAs($this->usuario)->post(
            "/animal/qr/{$animal->id}/actualizar-todo",
            $this->camposDelFormulario($animal, [
                'ultima_leche' => '18.5',
                'ultima_ganancia' => '0.750',
                'ultimo_tratamiento' => 'Mastitis',
                'ultima_vacuna' => 'Fiebre Aftosa',
            ])
        );

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Crear los registros desde el QR da error interno.');

        $this->assertSame(
            1,
            ProduccionLeche::where('animal_id', $animal->id)->count(),
            'No se creo la produccion de leche.'
        );
        $this->assertSame(
            '18.50',
            number_format((float) ProduccionLeche::where('animal_id', $animal->id)->value('litros'), 2, '.', '')
        );

        $this->assertSame(
            1,
            ProduccionCarne::where('animal_id', $animal->id)->count(),
            'No se creo la produccion de carne.'
        );

        $this->assertSame(
            1,
            Tratamiento::where('animal_id', $animal->id)->count(),
            'No se creo el tratamiento.'
        );
        $this->assertSame(
            'Mastitis',
            Tratamiento::where('animal_id', $animal->id)->value('motivo')
        );

        $this->assertSame(
            1,
            RegistroVacuna::where('animal_id', $animal->id)->count(),
            'No se creo el registro de vacuna.'
        );
        $this->assertSame(
            'Fiebre Aftosa',
            RegistroVacuna::where('animal_id', $animal->id)->value('nombre')
        );
    }

    public function test_actualiza_el_ultimo_registro_si_ya_existe(): void
    {
        $animal = $this->animalSinHistorial();

        ProduccionLeche::factory()->create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'animal_id' => $animal->id,
            'litros' => 5,
        ]);

        $this->actingAs($this->usuario)->post(
            "/animal/qr/{$animal->id}/actualizar-todo",
            $this->camposDelFormulario($animal, ['ultima_leche' => '22.0'])
        )->assertRedirect();

        $this->assertSame(
            1,
            ProduccionLeche::where('animal_id', $animal->id)->count(),
            'Se creo un registro nuevo en lugar de actualizar el ultimo.'
        );
        $this->assertSame(
            '22.00',
            number_format((float) ProduccionLeche::where('animal_id', $animal->id)->value('litros'), 2, '.', '')
        );
    }

    public function test_la_pantalla_carga_para_un_animal_con_genealogia(): void
    {
        // La vista muestra el padre y la madre, que son RELACIONES (devuelven
        // modelos) y no columnas: imprimirlas revienta. Este animal si tiene
        // genealogia, el caso que la prueba anterior no cubria.
        $genealogia = Genealogia::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->whereNotNull('padre_id')
            ->firstOrFail();

        $this->actingAs($this->usuario)
            ->get("/animal/qr/{$genealogia->animal_id}/editar-todo")
            ->assertOk();
    }

    public function test_la_pantalla_no_vuelca_los_modelos_relacionados_en_json(): void
    {
        // Los campos de padre y madre mostraban `$genealogia->padre`, que es una
        // RELACION: Eloquent es Stringable y devuelve su toJson(), asi que la
        // pantalla acababa pintando el registro entero del animal relacionado
        // (con inquilino_id, created_at...) dentro de un campo de texto. Ademas
        // de ilegible, exponia columnas internas en una pantalla que se abre
        // escaneando un QR.
        $genealogia = Genealogia::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->whereNotNull('padre_id')
            ->firstOrFail();

        $respuesta = $this->actingAs($this->usuario)
            ->get("/animal/qr/{$genealogia->animal_id}/editar-todo");

        $respuesta->assertOk();
        $respuesta->assertDontSee('&quot;inquilino_id&quot;', false);
        $respuesta->assertDontSee('created_at', false);
    }

    public function test_no_se_puede_editar_el_qr_de_un_animal_de_otra_ganaderia(): void
    {
        $ajeno = Animal::withoutTenantScope()
            ->where('inquilino_id', '!=', $this->usuario->inquilino_id)
            ->firstOrFail();

        $this->actingAs($this->usuario)
            ->get("/animal/qr/{$ajeno->id}/editar-todo")
            ->assertNotFound();

        $this->actingAs($this->usuario)
            ->post("/animal/qr/{$ajeno->id}/actualizar-todo", $this->camposDelFormulario($ajeno))
            ->assertNotFound();
    }
}
