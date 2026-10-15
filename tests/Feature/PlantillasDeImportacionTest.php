<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Las plantillas de docs/plantillas/ se importan de verdad.
 *
 * Es la comprobacion de que la documentacion no miente: si manana cambia un
 * nombre de columna en el codigo, estas pruebas fallan y avisan de que la
 * plantilla que recibe el usuario ya no sirve.
 *
 * Las cabeceras de las plantillas son EXACTAMENTE los nombres de campo del
 * sistema, asi que el mapeo es el mismo nombre a los dos lados: es lo que hace
 * que la pantalla de mapeo lo proponga sola.
 */
class PlantillasDeImportacionTest extends TestCase
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
     * @param  array<string, string>  $mapa
     */
    private function importar(string $tipo, string $csv, array $mapa): void
    {
        $this->actingAs($this->usuario)
            ->post("/importar/{$tipo}/upload", [
                'archivo' => UploadedFile::fake()->createWithContent($tipo.'.csv', $csv),
            ])
            ->assertSessionHasNoErrors()
            ->assertOk();

        $this->actingAs($this->usuario)
            ->post("/importar/{$tipo}/preview", ['map' => $mapa])
            ->assertSessionHasNoErrors()
            ->assertOk();

        $this->actingAs($this->usuario)
            ->post("/importar/{$tipo}/import")
            ->assertOk()
            ->assertDontSee('Filas omitidas', false);
    }

    public function test_la_plantilla_de_animales_importa_los_dos_ejemplos(): void
    {
        $this->importar('animales', $this->plantilla('plantilla_animales.csv'), [
            'codigo_interno' => 'codigo_interno',
            'categoria' => 'categoria',
            'raza' => 'raza',
            'tipo' => 'tipo',
            'fecha_nacimiento' => 'fecha_nacimiento',
            'sexo' => 'sexo',
            'peso_entrada' => 'peso_entrada',
            'estado' => 'estado',
        ]);

        $vaca = Animal::where('inquilino_id', $this->usuario->inquilino_id)
            ->where('codigo_interno', 'VACA-001')
            ->first();

        $this->assertNotNull($vaca, 'No se importo el primer animal de la plantilla.');
        $this->assertSame('Holstein', $vaca->raza);
        $this->assertSame('2023-04-15', \App\Support\Formato::fecha($vaca->fecha_nacimiento));

        $this->assertDatabaseHas('animales', [
            'inquilino_id' => $this->usuario->inquilino_id,
            'codigo_interno' => 'VACA-002',
        ]);
    }

    public function test_volver_a_importar_la_plantilla_de_animales_actualiza_y_no_duplica(): void
    {
        $mapa = [
            'codigo_interno' => 'codigo_interno',
            'categoria' => 'categoria',
            'raza' => 'raza',
            'tipo' => 'tipo',
            'fecha_nacimiento' => 'fecha_nacimiento',
            'sexo' => 'sexo',
            'peso_entrada' => 'peso_entrada',
            'estado' => 'estado',
        ];

        $this->importar('animales', $this->plantilla('plantilla_animales.csv'), $mapa);
        $this->importar('animales', $this->plantilla('plantilla_animales.csv'), $mapa);

        $this->assertSame(
            1,
            Animal::where('inquilino_id', $this->usuario->inquilino_id)->where('codigo_interno', 'VACA-001')->count(),
            'Reimportar duplico el animal en vez de actualizarlo.'
        );
    }

    public function test_la_plantilla_de_produccion_crea_leche_y_carne(): void
    {
        // La plantilla usa animal_id 1 y 2: se comprueba que existan.
        $animales = Animal::where('inquilino_id', $this->usuario->inquilino_id)->limit(2)->pluck('id');

        if ($animales->count() < 2) {
            $this->markTestSkipped('Hacen falta dos animales para la plantilla de produccion.');
        }

        $csv = str_replace(
            ['1,2026-02-01', '1,2026-02-02'],
            [$animales[0].',2026-02-01', $animales[0].',2026-02-02'],
            $this->plantilla('plantilla_produccion.csv')
        );

        $antesLeche = ProduccionLeche::where('animal_id', $animales[0])->count();
        $antesCarne = ProduccionCarne::where('animal_id', $animales[0])->count();

        $this->importar('produccion', $csv, [
            'animal_id' => 'animal_id',
            'fecha' => 'fecha',
            'litros' => 'litros',
            'turno' => 'turno',
            'ganancia_diaria' => 'ganancia_diaria',
            'peso' => 'peso',
            'observaciones' => 'observaciones',
        ]);

        $this->assertSame($antesLeche + 1, ProduccionLeche::where('animal_id', $animales[0])->count(), 'No entro la leche.');
        $this->assertSame($antesCarne + 1, ProduccionCarne::where('animal_id', $animales[0])->count(), 'No entro la carne.');
    }

    public function test_la_plantilla_de_compras_importa_los_dos_ejemplos(): void
    {
        $antes = Compra::where('inquilino_id', $this->usuario->inquilino_id)->count();

        $this->importar('compras', $this->plantilla('plantilla_compras.csv'), [
            'fecha' => 'fecha',
            'proveedor' => 'proveedor',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $this->assertSame($antes + 2, Compra::where('inquilino_id', $this->usuario->inquilino_id)->count());
        $this->assertDatabaseHas('compras', [
            'inquilino_id' => $this->usuario->inquilino_id,
            'proveedor' => 'AgroSur',
        ]);
    }

    public function test_la_plantilla_de_ventas_importa_los_dos_ejemplos(): void
    {
        $antes = Venta::where('inquilino_id', $this->usuario->inquilino_id)->count();

        $this->importar('ventas', $this->plantilla('plantilla_ventas.csv'), [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $this->assertSame($antes + 2, Venta::where('inquilino_id', $this->usuario->inquilino_id)->count());
        $this->assertDatabaseHas('ventas', [
            'inquilino_id' => $this->usuario->inquilino_id,
            'cliente' => 'Matadero Central',
        ]);
    }

    public function test_la_pantalla_de_mapeo_propone_sola_las_columnas_de_la_plantilla(): void
    {
        // Este es el motivo de que las plantillas usen los nombres exactos: el
        // usuario solo tiene que confirmar, sin mapear columna a columna.
        $respuesta = $this->actingAs($this->usuario)->post('/importar/animales/upload', [
            'archivo' => UploadedFile::fake()->createWithContent('animales.csv', $this->plantilla('plantilla_animales.csv')),
        ]);

        $respuesta->assertOk();
        $respuesta->assertSee('codigo_interno');
        $respuesta->assertSee('fecha_nacimiento');
        $respuesta->assertSee('selected', false);
    }
}
