<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Endurecimiento de la importacion de Excel/CSV.
 *
 * Antes: no habia lista blanca de formato (un PDF de 9 MB se aceptaba y
 * acababa en un mensaje generico) ni tope de filas, y las excepciones de
 * lectura se silenciaban devolviendo una coleccion vacia.
 *
 * Ahora: se valida el formato, se acota el numero de filas y los errores de
 * validacion llegan al usuario en lugar de perderse.
 */
class ImportacionTest extends TestCase
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

    private function csv(int $filas): string
    {
        $csv = "codigo_interno,categoria,raza,fecha_nacimiento,sexo,estado\n";

        for ($i = 1; $i <= $filas; $i++) {
            $csv .= "IMP-{$i},bovino,Holstein,2025-01-01,M,activo\n";
        }

        return $csv;
    }

    public function test_rechaza_un_formato_no_permitido(): void
    {
        $this->actingAs($this->usuario)
            ->post('/importar/animales/upload', [
                'archivo' => UploadedFile::fake()->create('informe.pdf', 50, 'application/pdf'),
            ])
            ->assertSessionHasErrors('archivo');
    }

    public function test_rechaza_un_archivo_mayor_de_10_mb(): void
    {
        $this->actingAs($this->usuario)
            ->post('/importar/animales/upload', [
                'archivo' => UploadedFile::fake()->create('enorme.csv', 11000, 'text/csv'),
            ])
            ->assertSessionHasErrors('archivo');
    }

    public function test_rechaza_un_archivo_con_mas_filas_del_maximo(): void
    {
        $this->actingAs($this->usuario)
            ->post('/importar/animales/upload', [
                'archivo' => UploadedFile::fake()->createWithContent(
                    'enorme.csv',
                    $this->csv(ImportService::MAX_FILAS + 5)
                ),
            ])
            ->assertSessionHasErrors('archivo');
    }

    public function test_acepta_un_csv_valido_y_todavia_no_escribe_en_base_de_datos(): void
    {
        $respuesta = $this->actingAs($this->usuario)
            ->post('/importar/animales/upload', [
                'archivo' => UploadedFile::fake()->createWithContent('animales.csv', $this->csv(3)),
            ]);

        $respuesta->assertSessionHasNoErrors();
        $respuesta->assertOk();

        // El primer paso solo mapea: nada se escribe hasta confirmar.
        $this->assertSame(
            0,
            Animal::withoutTenantScope()->where('codigo_interno', 'like', 'IMP-%')->count()
        );
    }

    public function test_la_importacion_confirmada_crea_los_animales_en_la_ganaderia_activa(): void
    {
        $codigo = 'IMP-CONFIRMADO';

        $this->actingAs($this->usuario)
            ->withSession([
                'rows_animales' => collect([
                    ['codigo_interno' => $codigo, 'categoria' => 'bovino', 'raza' => 'Holstein',
                        'fecha_nacimiento' => '2025-01-01', 'sexo' => 'M', 'estado' => 'activo'],
                ]),
                'map_animales' => [
                    'codigo_interno' => 'codigo_interno',
                    'categoria' => 'categoria',
                    'raza' => 'raza',
                    'fecha_nacimiento' => 'fecha_nacimiento',
                    'sexo' => 'sexo',
                    'estado' => 'estado',
                ],
            ])
            ->post('/importar/animales/import')
            ->assertOk();

        $animal = Animal::withoutTenantScope()->where('codigo_interno', $codigo)->first();

        $this->assertNotNull($animal, 'La importacion confirmada no creo el animal.');
        $this->assertSame($this->usuario->inquilino_id, $animal->inquilino_id);
    }
}
