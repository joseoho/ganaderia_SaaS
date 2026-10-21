<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Formatos que Excel produce de verdad y que antes se rechazaban.
 *
 * Lo que reporto el usuario fue "errores de formatos de fecha y montos" con
 * archivos de compras y ventas. Al probarlo con valores reales aparecieron tres
 * cosas distintas:
 *
 *  1. La fecha con hora ("2026-02-01 00:00:00", "01/02/2026 0:00") no se
 *     entendia y la fila se omitia.
 *  2. El año de dos cifras ("01/02/26") se guardaba en el AÑO 26 sin avisar
 *     de nada: peor que el error, porque el dato entra mal y en silencio.
 *  3. La pantalla de compras y ventas enviaba `map[monto]` y el controlador
 *     pedia `monto_total`, asi que no se importaba ni una fila.
 *
 * Aqui queda cada caso con su valor real.
 */
class FormatosRealesDeArchivoTest extends TestCase
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
                'archivo' => UploadedFile::fake()->createWithContent($tipo.'.csv', $csv),
            ])
            ->assertOk();

        $this->actingAs($this->usuario)
            ->post("/importar/{$tipo}/preview", ['map' => $mapa])
            ->assertOk();

        return $this->actingAs($this->usuario)->post("/importar/{$tipo}/import");
    }

    public function test_una_fecha_con_hora_se_entiende_y_entra(): void
    {
        // Las tres formas en que Excel deja una fecha con hora.
        $csv = "cliente;fecha;descripcion;monto_total\n"
            ."Finca El Roble;2026-02-01 00:00:00;Con hora;1000\n"
            ."Finca El Roble;01/02/2026 0:00;Con hora corta;2000\n"
            ."Finca El Roble;2026-02-01T00:00:00;En ISO;3000\n";

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $respuesta->assertDontSee('no se entiende', false);
        $respuesta->assertSee('Registros importados: 3');

        foreach (['Con hora', 'Con hora corta', 'En ISO'] as $descripcion) {
            $venta = Venta::where('inquilino_id', $this->usuario->inquilino_id)
                ->where('descripcion', $descripcion)
                ->first();

            $this->assertNotNull($venta, 'No entro la venta "'.$descripcion.'".');
            $this->assertSame(
                '2026-02-01',
                \App\Support\Formato::fecha($venta->fecha),
                'La fecha de "'.$descripcion.'" no se guardo como el 1 de febrero.'
            );
        }
    }

    public function test_un_ano_de_dos_cifras_no_se_guarda_en_el_ano_26(): void
    {
        $csv = "cliente;fecha;descripcion;monto_total\n"
            ."Finca El Roble;01/02/26;Ano corto;1000\n"
            ."Finca El Roble;1/2/99;Ano corto antiguo;2000\n";

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $respuesta->assertSee('Registros importados: 2');

        $reciente = Venta::where('descripcion', 'Ano corto')->first();
        $antigua = Venta::where('descripcion', 'Ano corto antiguo')->first();

        $this->assertNotNull($reciente);
        $this->assertNotNull($antigua);

        $this->assertSame('2026-02-01', \App\Support\Formato::fecha($reciente->fecha), '01/02/26 no es el 1 de febrero de 2026.');
        $this->assertSame('1999-02-01', \App\Support\Formato::fecha($antigua->fecha), '1/2/99 no es el 1 de febrero de 1999.');
    }

    public function test_los_importes_en_formato_espanol_entran_con_su_valor_exacto(): void
    {
        $csv = "fecha;proveedor;descripcion;monto_total\n"
            ."2026-02-01;AgroSur;Con miles y decimales;1.500.000,50\n"
            ."2026-02-02;AgroSur;Con simbolo;\$ 2.500.000\n"
            ."2026-02-03;AgroSur;Entero;1500\n"
            ."2026-02-04;AgroSur;Solo coma;1500,75\n";

        $respuesta = $this->importar('compras', $csv, [
            'fecha' => 'fecha',
            'proveedor' => 'proveedor',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $respuesta->assertDontSee('no es un numero', false);
        $respuesta->assertSee('Registros importados: 4');

        $esperados = [
            'Con miles y decimales' => '1500000.50',
            'Con simbolo' => '2500000.00',
            'Entero' => '1500.00',
            'Solo coma' => '1500.75',
        ];

        foreach ($esperados as $descripcion => $valor) {
            $compra = Compra::where('inquilino_id', $this->usuario->inquilino_id)
                ->where('descripcion', $descripcion)
                ->first();

            $this->assertNotNull($compra, 'No entro la compra "'.$descripcion.'".');
            $this->assertSame(
                $valor,
                number_format((float) $compra->monto_total, 2, '.', ''),
                'El importe de "'.$descripcion.'" se guardo mal.'
            );
        }
    }

    public function test_una_fecha_imposible_se_omite_diciendo_cual_es(): void
    {
        $csv = "cliente;fecha;descripcion;monto_total\n"
            ."Finca El Roble;32/13/2026;Fecha imposible;1000\n";

        $respuesta = $this->importar('ventas', $csv, [
            'cliente' => 'cliente',
            'fecha' => 'fecha',
            'descripcion' => 'descripcion',
            'monto_total' => 'monto_total',
        ]);

        $respuesta->assertSee('Filas omitidas: 1');
        $respuesta->assertSee('no se entiende', false);
        $respuesta->assertSee('32/13/2026');
    }

    /**
     * El caso mas comun de todos: el archivo es un Excel de verdad.
     *
     * En un .xlsx la fecha no viaja como texto sino como numero de serie (dias
     * desde 1899-12-30) y los importes como numeros. Se genera el archivo con
     * PhpSpreadsheet y se recorre el flujo completo, que es lo unico que prueba
     * de verdad lo que el usuario sube.
     */
    public function test_un_excel_real_con_fecha_y_monto_entra(): void
    {
        $hoja = (new \PhpOffice\PhpSpreadsheet\Spreadsheet)->getActiveSheet();
        $hoja->fromArray([
            ['fecha', 'proveedor', 'descripcion', 'monto_total'],
            [null, 'AgroSur', 'Compra desde Excel', null],
        ]);
        $hoja->setCellValue('A2', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime('2026-02-01')));
        $hoja->getStyle('A2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $hoja->setCellValue('D2', 1500.5);

        $ruta = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($hoja->getParent()))->save($ruta);

        try {
            $this->actingAs($this->usuario)
                ->post('/importar/compras/upload', [
                    'archivo' => new UploadedFile($ruta, 'compras.xlsx', null, null, true),
                ])
                ->assertOk();

            $this->actingAs($this->usuario)
                ->post('/importar/compras/preview', ['map' => [
                    'fecha' => 'fecha',
                    'proveedor' => 'proveedor',
                    'descripcion' => 'descripcion',
                    'monto_total' => 'monto_total',
                ]])
                ->assertOk();

            $respuesta = $this->actingAs($this->usuario)->post('/importar/compras/import');

            $respuesta->assertOk();
            $respuesta->assertSee('Registros importados: 1');
            $respuesta->assertDontSee('Filas omitidas', false);

            $compra = Compra::where('inquilino_id', $this->usuario->inquilino_id)
                ->where('descripcion', 'Compra desde Excel')
                ->first();

            $this->assertNotNull($compra, 'No entro la compra del Excel.');
            $this->assertSame('2026-02-01', \App\Support\Formato::fecha($compra->fecha));
            $this->assertSame('1500.50', number_format((float) $compra->monto_total, 2, '.', ''));
        } finally {
            @unlink($ruta);
        }
    }
}
