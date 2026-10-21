<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * El formulario de mapeo manda EXACTAMENTE los nombres de campo que el
 * controlador espera.
 *
 * Esto no es un detalle de estilo: compras y ventas tenian sus selects escritos
 * a mano y enviaban `map[monto]`, mientras el controlador exigia `monto_total`.
 * El mapeo no cuadraba nunca, la importacion se paraba entera y el informe decia
 * "falta asignar monto_total" con la columna delante del usuario.
 *
 * La prueba lee el HTML del paso de mapeo y compara los nombres enviados con la
 * lista de campos del sistema. Si manana alguien escribe un select a mano y se
 * equivoca en una letra, salta aqui y no en produccion.
 */
class MapeoDeColumnasTest extends TestCase
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
     * Ruta del modulo, CSV con sus cabeceras y los campos que debe ofrecer.
     *
     * @return array<string, array{0: string, 1: string, 2: array<int, string>}>
     */
    public static function modulos(): array
    {
        return [
            'animales' => [
                'animales',
                "codigo_interno,categoria,raza,tipo,fecha_nacimiento,sexo,peso_entrada,estado\nVACA-900,bovino,Holstein,leche,2023-04-15,H,480.50,activo\n",
                ['codigo_interno', 'categoria', 'raza', 'tipo', 'fecha_nacimiento', 'sexo', 'peso_entrada', 'estado'],
            ],
            'compras' => [
                'compras',
                "fecha,proveedor,descripcion,monto_total\n2026-02-01,AgroSur,Sal mineral,150000\n",
                ['fecha', 'proveedor', 'descripcion', 'monto_total'],
            ],
            'ventas' => [
                'ventas',
                "cliente,fecha,descripcion,monto_total\nFinca El Roble,2026-02-01,Novillos,2500000\n",
                ['cliente', 'fecha', 'descripcion', 'monto_total'],
            ],
            'produccion de leche' => [
                'produccion/leche',
                "animal_id,fecha,litros,turno,observaciones\n1,2026-02-01,12.5,manana,ok\n",
                ['animal_id', 'fecha', 'litros', 'turno', 'observaciones'],
            ],
            'produccion de carne' => [
                'produccion/carne',
                "animal_id,fecha,peso,ganancia_diaria,observaciones\n1,2026-02-01,420.5,0.750,ok\n",
                ['animal_id', 'fecha', 'peso', 'ganancia_diaria', 'observaciones'],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $campos
     */
    #[DataProvider('modulos')]
    public function test_el_mapeo_envia_los_nombres_que_el_controlador_espera(string $ruta, string $csv, array $campos): void
    {
        $respuesta = $this->actingAs($this->usuario)->post("/importar/{$ruta}/upload", [
            'archivo' => UploadedFile::fake()->createWithContent(str_replace('/', '_', $ruta).'.csv', $csv),
        ]);

        $respuesta->assertOk();

        preg_match_all('/name="map\[([^\]]+)\]"/', (string) $respuesta->getContent(), $coincidencias);

        $enviados = array_values(array_unique($coincidencias[1]));
        $esperados = $campos;

        sort($enviados);
        sort($esperados);

        $this->assertSame(
            $esperados,
            $enviados,
            'La pantalla de mapeo de '.$ruta.' envia campos que el controlador no conoce (o le faltan).'
        );
    }

    /**
     * @param  array<int, string>  $campos
     */
    #[DataProvider('modulos')]
    public function test_los_campos_obligatorios_se_marcan_en_el_formulario(string $ruta, string $csv, array $campos): void
    {
        $respuesta = $this->actingAs($this->usuario)->post("/importar/{$ruta}/upload", [
            'archivo' => UploadedFile::fake()->createWithContent(str_replace('/', '_', $ruta).'.csv', $csv),
        ]);

        $contenido = (string) $respuesta->getContent();

        // Al menos un campo obligatorio va marcado: el que no lo esta no frena
        // al usuario antes de importar, y la fila se omite despues sin avisar.
        $this->assertStringContainsString('text-danger">*</span>', $contenido, 'Ningun campo aparece como obligatorio en '.$ruta.'.');
    }
}
