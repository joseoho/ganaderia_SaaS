<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\ProduccionLeche;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Importacion de produccion, compras y ventas: el flujo completo.
 *
 * Solo el de animales se habia ejecutado de principio a fin. Los otros tres
 * pasan por el mismo codigo que se corrigio en la Fase 4 (las filas se guardan
 * en la cache en lugar de la sesion, y los encabezados se leen antes de limpiar
 * las filas), pero nunca se habian recorrido enteros.
 */
class ImportacionOtrosModulosTest extends TestCase
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
    }

    /**
     * tipo, modelo, csv (con {ANIMAL} donde va el id del animal), mapa, campo a
     * comprobar y valor esperado.
     *
     * @return array<string, array{0: string, 1: class-string, 2: string, 3: array<string, string>, 4: string, 5: string}>
     */
    public static function flujos(): array
    {
        return [
            'produccion' => [
                'produccion',
                ProduccionLeche::class,
                "animal_id,fecha,litros\n{ANIMAL},2026-02-01,12.5\n",
                ['animal_id' => 'animal_id', 'fecha' => 'fecha', 'litros' => 'litros'],
                'litros',
                '12.50',
            ],
            'compras' => [
                'compras',
                Compra::class,
                "fecha,descripcion,monto_total,proveedor\n2026-02-01,Compra de sal,150000.50,AgroSur\n",
                ['fecha' => 'fecha', 'descripcion' => 'descripcion', 'monto_total' => 'monto_total', 'proveedor' => 'proveedor'],
                'proveedor',
                'AgroSur',
            ],
            'ventas' => [
                'ventas',
                Venta::class,
                "cliente,fecha,descripcion,monto_total\nFinca El Roble,2026-02-01,Venta de novillos,2500000\n",
                ['cliente' => 'cliente', 'fecha' => 'fecha', 'descripcion' => 'descripcion', 'monto_total' => 'monto_total'],
                'cliente',
                'Finca El Roble',
            ],
        ];
    }

    /**
     * @param  class-string  $modelo
     * @param  array<string, string>  $mapa
     */
    #[DataProvider('flujos')]
    public function test_el_flujo_completo_crea_los_registros(
        string $tipo,
        string $modelo,
        string $csv,
        array $mapa,
        string $campo,
        string $esperado
    ): void {
        $animal = Animal::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();
        $csv = str_replace('{ANIMAL}', (string) $animal->id, $csv);

        $antes = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->count();

        // Paso 1: subir el archivo.
        $subida = $this->actingAs($this->usuario)->post("/importar/{$tipo}/upload", [
            'archivo' => UploadedFile::fake()->createWithContent($tipo.'.csv', $csv),
        ]);
        $subida->assertSessionHasNoErrors();
        $subida->assertOk();

        // Las filas no viajan en la sesion (igual que en animales).
        $subida->assertSessionMissing('rows_'.$tipo);

        // Paso 2: confirmar el mapeo. Si no se pudieran leer las filas de la
        // cache, aqui no habria nada que previsualizar.
        $preview = $this->actingAs($this->usuario)->post("/importar/{$tipo}/preview", ['map' => $mapa]);
        $preview->assertSessionHasNoErrors();
        $preview->assertOk();

        // Paso 3: confirmar la importacion.
        $importacion = $this->actingAs($this->usuario)->post("/importar/{$tipo}/import");
        $this->assertNotSame(500, $importacion->getStatusCode(), 'La importacion de '.$tipo.' da error interno.');

        $despues = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->count();

        $this->assertGreaterThan($antes, $despues, 'La importacion de '.$tipo.' no creo ningun registro.');

        $creado = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->latest('id')
            ->first();

        $this->assertSame(
            $esperado,
            (string) $creado->{$campo},
            'El registro creado por la importacion de '.$tipo.' no tiene el valor esperado en '.$campo.'.'
        );

        $this->assertSame(
            (int) $this->usuario->inquilino_id,
            (int) $creado->inquilino_id,
            'El registro importado quedo en otra ganaderia.'
        );
    }

    /**
     * @param  class-string  $modelo
     * @param  array<string, string>  $mapa
     */
    #[DataProvider('flujos')]
    public function test_la_pantalla_de_mapeo_ofrece_los_nombres_reales_de_columna(
        string $tipo,
        string $modelo,
        string $csv,
        array $mapa
    ): void {
        $animal = Animal::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();
        $csv = str_replace('{ANIMAL}', (string) $animal->id, $csv);

        $respuesta = $this->actingAs($this->usuario)->post("/importar/{$tipo}/upload", [
            'archivo' => UploadedFile::fake()->createWithContent($tipo.'.csv', $csv),
        ]);

        $respuesta->assertOk();

        foreach (array_keys($mapa) as $columna) {
            $respuesta->assertSee($columna);
        }
    }
}
