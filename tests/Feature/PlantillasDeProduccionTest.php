<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Las plantillas de produccion (leche y carne) se importan de verdad.
 *
 * Estaban dentro de PlantillasDeImportacionTest, que juntaba los cinco modulos.
 * Al separarse la importacion de produccion en dos pantallas, sus pruebas se
 * separan con ella: aqui se comprueba lo propio de cada tipo, y sobre todo que
 * una plantilla de leche no crea carne ni al reves.
 */
class PlantillasDeProduccionTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::query()->where('email', 'admin@ganaderia.test')->firstOrFail();
    }

    private function plantilla(string $nombre): string
    {
        $ruta = base_path('docs/plantillas/'.$nombre);

        $this->assertFileExists($ruta, 'Falta la plantilla '.$nombre);

        return (string) file_get_contents($ruta);
    }

    /**
     * Recorre los tres pasos de la importacion de produccion.
     *
     * @param  array<string, string>  $mapa
     */
    private function importar(string $tipo, string $csv, array $mapa): void
    {
        $this->actingAs($this->usuario)
            ->post("/importar/produccion/{$tipo}/upload", [
                'archivo' => UploadedFile::fake()->createWithContent($tipo.'.csv', $csv),
            ])
            ->assertSessionHasNoErrors()
            ->assertOk();

        $this->actingAs($this->usuario)
            ->post("/importar/produccion/{$tipo}/preview", ['map' => $mapa])
            ->assertSessionHasNoErrors()
            ->assertOk();

        $this->actingAs($this->usuario)
            ->post("/importar/produccion/{$tipo}/import")
            ->assertOk()
            ->assertDontSee('Filas omitidas', false);
    }

    /**
     * La plantilla con el animal de ejemplo cambiado por uno que exista.
     *
     * Las plantillas traen `animal_id` 1 y 2 a proposito (un numero que se
     * entiende sin explicacion). En las pruebas ese id se sustituye por uno real,
     * porque con base de datos limpia los animales se crean con otro id.
     */
    private function plantillaConAnimalReal(string $nombre, int $animalId): string
    {
        return (string) preg_replace('/^1;/m', $animalId.';', $this->plantilla($nombre));
    }

    public function test_la_plantilla_de_leche_importa_sus_dos_ejemplos(): void
    {
        $animal = Animal::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();

        $this->assertStringContainsString(
            'animal_id;fecha;litros',
            $this->plantilla('plantilla_leche.csv'),
            'La plantilla de leche cambio de columnas: actualiza esta prueba y docs/FORMATO_IMPORTACION.md.'
        );

        $antes = ProduccionLeche::where('animal_id', $animal->id)->count();

        // El seeder ya trae produccion de carne para algunos animales: lo que se
        // comprueba es que esta importacion no anade ninguna.
        $antesCarne = ProduccionCarne::where('animal_id', $animal->id)->count();

        $this->importar('leche', $this->plantillaConAnimalReal('plantilla_leche.csv', $animal->id), [
            'animal_id' => 'animal_id',
            'fecha' => 'fecha',
            'litros' => 'litros',
            'turno' => 'turno',
            'observaciones' => 'observaciones',
        ]);

        $this->assertSame(
            $antes + 2,
            ProduccionLeche::where('animal_id', $animal->id)->count(),
            'No entraron los dos ordeños de la plantilla de leche.'
        );

        $this->assertSame(
            '12.50',
            number_format((float) ProduccionLeche::where('animal_id', $animal->id)
                ->whereDate('fecha', '2026-02-01')
                ->value('litros'), 2, '.', '')
        );

        // La plantilla de leche NO debe crear carne: son pantallas distintas.
        $this->assertSame(
            $antesCarne,
            ProduccionCarne::where('animal_id', $animal->id)->count(),
            'Importar la plantilla de leche creo produccion de carne.'
        );
    }

    public function test_la_plantilla_de_carne_importa_sus_dos_ejemplos(): void
    {
        $animal = Animal::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();

        $this->assertStringContainsString(
            'animal_id;fecha;peso',
            $this->plantilla('plantilla_carne.csv'),
            'La plantilla de carne cambio de columnas: actualiza esta prueba y docs/FORMATO_IMPORTACION.md.'
        );

        $antes = ProduccionCarne::where('animal_id', $animal->id)->count();

        // Igual que arriba: el seeder ya trae ordeños de este animal.
        $antesLeche = ProduccionLeche::where('animal_id', $animal->id)->count();

        $this->importar('carne', $this->plantillaConAnimalReal('plantilla_carne.csv', $animal->id), [
            'animal_id' => 'animal_id',
            'fecha' => 'fecha',
            'peso' => 'peso',
            'ganancia_diaria' => 'ganancia_diaria',
            'observaciones' => 'observaciones',
        ]);

        $this->assertSame(
            $antes + 2,
            ProduccionCarne::where('animal_id', $animal->id)->count(),
            'No entraron los dos pesajes de la plantilla de carne.'
        );

        $this->assertSame(
            '420.50',
            number_format((float) ProduccionCarne::where('animal_id', $animal->id)
                ->whereDate('fecha', '2026-02-01')
                ->value('peso'), 2, '.', '')
        );

        // Y al reves: la plantilla de carne no debe crear leche.
        $this->assertSame(
            $antesLeche,
            ProduccionLeche::where('animal_id', $animal->id)->count(),
            'Importar la plantilla de carne creo produccion de leche.'
        );
    }

    public function test_la_pantalla_de_mapeo_propone_sola_las_columnas_de_cada_plantilla(): void
    {
        // Este es el motivo de que las plantillas usen los nombres exactos: el
        // usuario solo tiene que confirmar, sin mapear columna a columna.
        foreach (['leche', 'carne'] as $tipo) {
            $respuesta = $this->actingAs($this->usuario)->post("/importar/produccion/{$tipo}/upload", [
                'archivo' => UploadedFile::fake()->createWithContent(
                    $tipo.'.csv',
                    $this->plantilla('plantilla_'.$tipo.'.csv')
                ),
            ]);

            $respuesta->assertOk();
            $respuesta->assertSee('animal_id');
            $respuesta->assertSee('fecha');

            // La columna propia de cada tipo solo aparece en su pantalla.
            $respuesta->assertSee($tipo === 'leche' ? 'litros' : 'peso');
            $respuesta->assertSee('selected', false);
        }
    }
}
