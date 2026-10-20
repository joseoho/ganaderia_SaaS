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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Importacion de archivos REALES: con celdas vacias y filas incompletas.
 *
 * Los datos de una hoja de calculo nunca vienen completos: a un ordeño le falta
 * la fila de la tarde, una compra puede quedarse sin monto, una fecha puede venir
 * escrita a mano mal. Estas pruebas comprueban que esos huecos no tumban la
 * importacion ni dejan dentro la mitad de los datos.
 *
 * Antes de este trabajo: una compra sin monto daba un error 500 ("Column
 * 'monto_total' cannot be null") y una venta sin cliente, lo mismo. Con un
 * archivo de verdad eso pasa en la primera fila que falte.
 */
class ImportacionDatosRealesTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->usuario = User::query()->where('email', 'admin@ganaderia.test')->firstOrFail();
    }

    /**
     * Recorre los tres pasos de la importacion.
     *
     * @param  array<string, string>  $mapa
     */
    private function importar(string $tipo, string $csv, array $mapa): TestResponse
    {
        $this->actingAs($this->usuario)
            ->post("/importar/{$tipo}/upload", [
                'archivo' => UploadedFile::fake()->createWithContent(str_replace('/', '_', $tipo).'.csv', $csv),
            ])
            ->assertOk();

        $this->actingAs($this->usuario)
            ->post("/importar/{$tipo}/preview", ['map' => $mapa])
            ->assertOk();

        return $this->actingAs($this->usuario)->post("/importar/{$tipo}/import");
    }

    public function test_leche_con_una_celda_vacia_omite_solo_esa_fila(): void
    {
        // Animal recien creado: sin produccion previa, para poder contar.
        $animal = Animal::factory()->create(['inquilino_id' => $this->usuario->inquilino_id]);

        $csv = "animal_id,fecha,litros\n"
            ."{$animal->id},2026-02-01,12.5\n"
            ."{$animal->id},2026-02-02,\n"
            ."{$animal->id},2026-02-03,11.0\n";

        $respuesta = $this->importar('produccion/leche', $csv, [
            'animal_id' => 'animal_id',
            'fecha' => 'fecha',
            'litros' => 'litros',
        ]);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Una celda vacia tumbo la importacion.');

        // Entran las dos filas con litros; la del medio se omite y se dice por que.
        $this->assertSame(
            2,
            ProduccionLeche::where('animal_id', $animal->id)->count(),
            'No entraron exactamente los dos ordeños con litros.'
        );

        $this->assertSame(
            '12.50',
            number_format((float) ProduccionLeche::where('animal_id', $animal->id)
                ->whereDate('fecha', '2026-02-01')
                ->value('litros'), 2, '.', '')
        );

        $respuesta->assertSee('Filas omitidas: 1');
        $respuesta->assertSee('la celda esta vacia en litros');
    }

    public function test_carne_con_una_celda_vacia_omite_solo_esa_fila(): void
    {
        $animal = Animal::factory()->create(['inquilino_id' => $this->usuario->inquilino_id]);

        $csv = "animal_id,fecha,peso,ganancia_diaria\n"
            ."{$animal->id},2026-02-01,420.5,0.750\n"
            ."{$animal->id},2026-02-02,,\n"
            ."{$animal->id},2026-02-03,431.75,\n";

        $respuesta = $this->importar('produccion/carne', $csv, [
            'animal_id' => 'animal_id',
            'fecha' => 'fecha',
            'peso' => 'peso',
            'ganancia_diaria' => 'ganancia_diaria',
        ]);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Una celda vacia tumbo la importacion.');

        $this->assertSame(
            2,
            ProduccionCarne::where('animal_id', $animal->id)->count(),
            'No entraron exactamente los dos pesajes con peso.'
        );

        $this->assertSame(
            '420.50',
            number_format((float) ProduccionCarne::where('animal_id', $animal->id)
                ->whereDate('fecha', '2026-02-01')
                ->value('peso'), 2, '.', '')
        );

        $respuesta->assertSee('Filas omitidas: 1');
        $respuesta->assertSee('la celda esta vacia en peso');
    }

    public function test_una_compra_incompleta_se_omite_en_vez_de_reventar(): void
    {
        $csv = "fecha,descripcion,monto_total,proveedor\n"
            ."2026-02-01,Compra correcta,150000,AgroSur\n"
            ."2026-02-02,Compra sin monto,,AgroSur\n"
            ."2026-02-03,Otra compra correcta,90000,AgroSur\n";

        // El seeder ya trae compras: se cuenta la DIFERENCIA.
        $antes = Compra::where('inquilino_id', $this->usuario->inquilino_id)->count();

        $respuesta = $this->importar('compras', $csv, [
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
            'proveedor' => 'proveedor',
        ]);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Una compra sin monto tumbo la importacion.');

        $this->assertSame(
            $antes + 2,
            Compra::where('inquilino_id', $this->usuario->inquilino_id)->count(),
            'No entraron exactamente las compras completas.'
        );

        $respuesta->assertSee('Filas omitidas: 1');
    }

    public function test_una_venta_sin_cliente_se_omite_en_vez_de_reventar(): void
    {
        $csv = "cliente,fecha,descripcion,monto_total\n"
            ."Finca El Roble,2026-02-01,Venta buena,2500000\n"
            .",2026-02-02,Venta sin cliente,1000000\n";

        $antes = Venta::where('inquilino_id', $this->usuario->inquilino_id)->count();

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Una venta sin cliente tumbo la importacion.');

        $this->assertSame($antes + 1, Venta::where('inquilino_id', $this->usuario->inquilino_id)->count());
        $respuesta->assertSee('Filas omitidas: 1');
    }

    public function test_una_fecha_mal_escrita_omite_su_fila_y_no_las_demas(): void
    {
        // Ojo: MariaDB acepta 01/02/2026 y lo interpreta como el 2 de enero, asi que
        // para provocar un fallo de verdad hace falta una fecha imposible.
        $csv = "cliente,fecha,descripcion,monto_total\n"
            ."Finca El Roble,2026-02-01,Buena,1000\n"
            ."Finca El Roble,2026-13-45,Fecha imposible,2000\n"
            ."Finca El Roble,2026-02-03,Otra buena,3000\n";

        $antes = Venta::where('inquilino_id', $this->usuario->inquilino_id)->count();

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Una fecha mal escrita tumbo la importacion entera.');

        // Las dos buenas entran; la mala se cuenta como omitida.
        $this->assertSame(
            $antes + 2,
            Venta::where('inquilino_id', $this->usuario->inquilino_id)->count(),
            'Deberian haber entrado las dos filas correctas.'
        );
        $respuesta->assertSee('Filas omitidas: 1');
    }

    public function test_sin_omitidas_no_se_muestra_el_aviso(): void
    {
        $csv = "cliente,fecha,descripcion,monto_total\n"
            ."Finca El Roble,2026-02-01,Venta buena,2500000\n";

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $respuesta->assertSee('Registros importados: 1');
        $respuesta->assertDontSee('Filas omitidas');
    }
}
