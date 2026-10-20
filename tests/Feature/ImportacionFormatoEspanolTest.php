<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\ProduccionLeche;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Importacion con CSV tal y como los guarda Excel en español.
 *
 * El usuario reporto que se le omitian TODAS las filas al subir compras, ventas
 * y produccion. La causa: Excel, con el sistema en español, guarda los CSV con
 * PUNTO Y COMA como separador (y con coma decimal: "1.500,50"). Leidos con la
 * coma como separador, el archivo entero queda en UNA sola columna, de modo que
 * no aparece ningun campo y todas las filas se omiten. Ademas el aviso no decia
 * por que.
 *
 * Ahora el separador se detecta del propio archivo, las fechas y los importes se
 * traducen desde el formato español, y el informe dice el motivo de cada fila.
 */
class ImportacionFormatoEspanolTest extends TestCase
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

    public function test_un_csv_con_punto_y_coma_se_lee_por_columnas(): void
    {
        // Asi lo guarda Excel en español: separador ; y coma decimal.
        $csv = "cliente;fecha;descripcion;monto_total\n"
            ."Finca El Roble;01/02/2026;Venta con punto y coma;1.500,50\n";

        $antes = Venta::where('inquilino_id', $this->usuario->inquilino_id)->count();

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $this->assertSame(
            $antes + 1,
            Venta::where('inquilino_id', $this->usuario->inquilino_id)->count(),
            'No entro la venta: el CSV con punto y coma se esta leyendo como una sola columna.'
        );
        $respuesta->assertDontSee('Filas omitidas', false);

        $venta = Venta::where('descripcion', 'Venta con punto y coma')->first();

        $this->assertNotNull($venta);
        $this->assertSame('1500.50', number_format((float) $venta->monto_total, 2, '.', ''));
        $this->assertSame('2026-02-01', \App\Support\Formato::fecha($venta->fecha));
    }

    public function test_los_importes_con_punto_de_miles_y_coma_decimal_entran(): void
    {
        $csv = "fecha;proveedor;descripcion;monto_total\n"
            ."2026-02-01;AgroSur;Compra con miles;1.500,50\n"
            ."2026-02-02;AgroSur;Compra con simbolo;\$ 2.500.000\n";

        $antes = Compra::where('inquilino_id', $this->usuario->inquilino_id)->count();

        $respuesta = $this->importar('compras', $csv, [
            'fecha' => 'fecha',
            'proveedor' => 'proveedor',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $this->assertSame(
            $antes + 2,
            Compra::where('inquilino_id', $this->usuario->inquilino_id)->count(),
            'No entraron las dos compras: el formato español no se esta traduciendo.'
        );

        $respuesta->assertDontSee('Filas omitidas', false);

        $this->assertSame(
            '1500.50',
            number_format((float) Compra::where('descripcion', 'Compra con miles')->value('monto_total'), 2, '.', '')
        );
        $this->assertSame(
            '2500000.00',
            number_format((float) Compra::where('descripcion', 'Compra con simbolo')->value('monto_total'), 2, '.', '')
        );
    }

    public function test_un_csv_con_coma_y_decimales_con_punto_sigue_funcionando(): void
    {
        // El formato internacional tiene que seguir entrando.
        $csv = "cliente,fecha,descripcion,monto_total\n"
            ."Finca El Roble,2026-02-01,Venta internacional,1500.50\n";

        $antes = Venta::where('inquilino_id', $this->usuario->inquilino_id)->count();

        $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $this->assertSame(
            $antes + 1,
            Venta::where('inquilino_id', $this->usuario->inquilino_id)->count(),
            'El formato internacional dejo de funcionar.'
        );
    }

    public function test_las_fechas_en_formato_dia_mes_ano_se_entienden_como_tales(): void
    {
        // "01/02/2026" es el 1 de febrero en español, pero la base de datos lo
        // lee como el 2 de enero. Se comprueba que se normaliza ANTES de guardar.
        $csv = "cliente;fecha;descripcion;monto_total\n"
            ."Finca El Roble;01/02/2026;Venta en febrero;1000000\n";

        $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $venta = Venta::where('inquilino_id', $this->usuario->inquilino_id)
            ->where('descripcion', 'Venta en febrero')
            ->first();

        $this->assertNotNull($venta, 'No entro la venta.');
        $this->assertSame(
            '2026-02-01',
            \App\Support\Formato::fecha($venta->fecha),
            'La fecha se guardo como 2 de enero: no se esta interpretando dia/mes/año.'
        );
    }

    public function test_la_produccion_con_punto_y_coma_y_coma_decimal_entra(): void
    {
        $animal = \App\Models\Animal::where('inquilino_id', $this->usuario->inquilino_id)->firstOrFail();

        $csv = "animal_id;fecha;litros\n"
            ."{$animal->id};01/02/2026;12,5\n";

        $antes = ProduccionLeche::where('animal_id', $animal->id)->count();

        $this->importar('produccion/leche', $csv, [
            'animal_id' => 'animal_id',
            'fecha' => 'fecha',
            'litros' => 'litros',
        ]);

        $this->assertSame(
            $antes + 1,
            ProduccionLeche::where('animal_id', $animal->id)->count(),
            'No entro la produccion con coma decimal.'
        );
    }

    public function test_el_informe_dice_que_fila_falla_y_por_que(): void
    {
        $csv = "cliente;fecha;descripcion;monto_total\n"
            ."Finca El Roble;2026-02-01;Buena;1000\n"
            ."Finca El Roble;;Sin fecha;2000\n";

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        // Tiene que decir la FILA (la 3: cabecera + 1 buena + esta) y el MOTIVO.
        $respuesta->assertSee('Filas omitidas: 1');
        $respuesta->assertSee('Fila 3');
        $respuesta->assertSee('la celda esta vacia en fecha');
    }

    public function test_una_fila_sin_importe_dice_que_falta_el_importe(): void
    {
        $csv = "fecha;proveedor;descripcion;monto_total\n"
            ."2026-02-01;AgroSur;Sin importe;\n";

        $respuesta = $this->importar('compras', $csv, [
            'fecha' => 'fecha',
            'proveedor' => 'proveedor',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $respuesta->assertSee('Filas omitidas: 1');
        $respuesta->assertSee('monto_total');
    }

    public function test_si_no_se_mapeo_el_importe_lo_dice_y_nombra_la_columna_del_archivo(): void
    {
        // El caso del usuario: el archivo trae "Total" y el campo monto_total se
        // quedo sin asignar. Antes decia "le falta monto_total" y no habia forma
        // de saber que habia que volver al paso de mapeo.
        $csv = "fecha;proveedor;descripcion;Total\n"
            ."2026-02-01;AgroSur;Compra uno;150000\n";

        $respuesta = $this->importar('compras', $csv, [
            'fecha' => 'fecha',
            'proveedor' => 'proveedor',
            'descripcion' => 'descripcion',
        ]);

        $respuesta->assertSee('falta asignar monto_total');
        $respuesta->assertSee('Total');
        $respuesta->assertSee('Columnas que tiene tu archivo');
    }

    public function test_un_importe_ilegible_dice_el_valor_concreto(): void
    {
        $csv = "fecha;proveedor;descripcion;monto_total\n"
            ."2026-02-01;AgroSur;Compra rara;sin dato\n";

        $respuesta = $this->importar('compras', $csv, [
            'fecha' => 'fecha',
            'proveedor' => 'proveedor',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $respuesta->assertSee('no es un numero');
        $respuesta->assertSee('sin dato');
    }

    public function test_una_fecha_ilegible_dice_el_valor_concreto(): void
    {
        $csv = "cliente;fecha;descripcion;monto_total\n"
            ."Finca El Roble;ayer;Venta rara;1000\n";

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $respuesta->assertSee('no se entiende');
        $respuesta->assertSee('ayer');
    }
}
