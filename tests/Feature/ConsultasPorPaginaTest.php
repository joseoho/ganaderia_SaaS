<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\AnimalPotrero;
use App\Models\Potrero;
use App\Models\Rotacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guarda contra el N+1 y contra las consultas hechas desde las vistas.
 *
 * La ficha del potrero tenia los dos problemas:
 *  - accedia a $a->animal dentro del @foreach, lo que lanzaba una consulta por
 *    cada animal del potrero;
 *  - consultaba Animal y Rotacion DENTRO de la propia vista.
 *
 * La prueba de crecimiento es la importante: no fija un numero de consultas
 * (fragil), sino que comprueba que ese numero NO aumenta al aumentar los datos.
 */
class ConsultasPorPaginaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private Potrero $potrero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::query()
            ->where('email', 'admin@ganaderia.test')
            ->firstOrFail();

        $this->potrero = Potrero::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->firstOrFail();

        $this->asignarAnimalesAlPotrero(3);
    }

    private function asignarAnimalesAlPotrero(int $cantidad): void
    {
        $yaAsignados = AnimalPotrero::withoutTenantScope()
            ->where('potrero_id', $this->potrero->id)
            ->pluck('animal_id');

        $nuevos = Animal::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->whereNotIn('id', $yaAsignados)
            ->limit($cantidad)
            ->pluck('id');

        foreach ($nuevos as $animalId) {
            AnimalPotrero::create([
                'inquilino_id' => $this->usuario->inquilino_id,
                'animal_id' => $animalId,
                'potrero_id' => $this->potrero->id,
                'fecha_entrada' => '2026-01-01',
            ]);
        }
    }

    private function consultasDeLaFicha(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->usuario)
            ->get("/potreros/{$this->potrero->id}")
            ->assertOk();

        $total = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $total;
    }

    public function test_la_ficha_del_potrero_carga_con_sus_tres_bloques(): void
    {
        Rotacion::create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'potrero_id' => $this->potrero->id,
            'fecha_entrada' => '2026-02-01',
            'fecha_salida' => '2026-02-20',
            'dias_ocupacion' => 19,
            'carga_animal' => 1.5,
        ]);

        $respuesta = $this->actingAs($this->usuario)->get("/potreros/{$this->potrero->id}");

        $respuesta->assertOk();
        $respuesta->assertSee('Animales dentro del potrero');
        $respuesta->assertSee('Historial de rotaciones');
        $respuesta->assertSee('Asignar animales a este potrero');
    }

    public function test_el_numero_de_consultas_no_crece_al_anadir_animales(): void
    {
        $consultasCon3 = $this->consultasDeLaFicha();

        // Se doblan los animales del potrero (3 mas).
        $this->asignarAnimalesAlPotrero(3);

        $consultasCon6 = $this->consultasDeLaFicha();

        // Se permite una variacion de +1 (orden de las consultas, cache de
        // vistas). Lo que NO puede pasar es que crezca con los datos: con una
        // consulta por animal, anadir 3 animales sumaria 3 consultas o mas.
        $this->assertLessThanOrEqual(
            $consultasCon3 + 1,
            $consultasCon6,
            sprintf(
                'La ficha del potrero pasa de %d a %d consultas al anadir 3 animales: hay consultas por fila (N+1).',
                $consultasCon3,
                $consultasCon6
            )
        );
    }

    public function test_las_fechas_de_la_ficha_no_muestran_la_hora(): void
    {
        $respuesta = $this->actingAs($this->usuario)->get("/potreros/{$this->potrero->id}");

        $respuesta->assertOk();
        $this->assertDoesNotMatchRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',
            $respuesta->getContent(),
            'La ficha muestra una fecha con hora; deberia usar Formato::fecha().'
        );
    }
}
