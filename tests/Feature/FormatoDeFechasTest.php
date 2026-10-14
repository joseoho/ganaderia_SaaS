<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Genealogia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Las fechas que ve el usuario salen en formato Y-m-d, sin hora.
 *
 * Motivo: al declarar los casts de tipo `date` en los modelos (Fase 4), una
 * fecha sin formatear pasa a renderizarse como "2026-09-19 00:00:00". Se
 * limpiaron 53 interpolaciones en 32 vistas; esta prueba evita que vuelvan,
 * recorriendo las paginas reales en lugar de revisar el codigo.
 *
 * Solo se mira el texto VISIBLE: dentro de <script> hay datos JSON para los
 * graficos (por ejemplo created_at) donde la hora es legitima.
 */
class FormatoDeFechasTest extends TestCase
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
     * Solo el TEXTO visible: se quitan scripts, estilos y todas las etiquetas
     * (con sus atributos). Dentro de los atributos hay datos de maquina —por
     * ejemplo el JSON de un grafico en un data-* o el value de un input— donde
     * una fecha con hora es legitima. El formato de los input type="date" lo
     * cubre FormulariosDeEdicionTest.
     */
    private function contenidoVisible(TestResponse $respuesta): string
    {
        $html = (string) preg_replace(
            ['/<script\b[^>]*>.*?<\/script>/is', '/<style\b[^>]*>.*?<\/style>/is'],
            '',
            $respuesta->getContent()
        );

        return strip_tags($html);
    }

    private function assertSinFechasConHora(TestResponse $respuesta, string $donde): void
    {
        // Se busca el formato que renderiza PHP ("2026-09-19 00:00:00", con
        // ESPACIO). Los datos de maquina de la pagina (JSON en un atributo o en
        // un contenedor oculto) usan ISO-8601 con "T", que no es lo que ve el
        // usuario y por eso no debe contar.
        $this->assertDoesNotMatchRegularExpression(
            '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}/',
            $this->contenidoVisible($respuesta),
            'En '.$donde.' se muestra una fecha con hora. Deberia formatearse con Formato::fecha().'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function paginas(): array
    {
        return [
            'animales' => ['/animales'],
            'etiquetas' => ['/animales/etiquetas'],
            'compras' => ['/compras'],
            'ventas' => ['/ventas'],
            'dashboard' => ['/dashboard'],
            'genealogia' => ['/genealogia'],
            'inventario' => ['/inventario'],
            'movilizaciones' => ['/movilizaciones'],
            'produccion_carne' => ['/produccion'],
            'produccion_leche' => ['/produccion_leche'],
            'registro_vacunas' => ['/registro_vacunas'],
            'reproduccion' => ['/reproduccion'],
            'tratamientos' => ['/tratamientos'],
            'potreros' => ['/potreros'],
            'gastos' => ['/gastos'],
            'notificaciones' => ['/notificaciones'],
            'reportes' => ['/reportes'],
            'dashboard_reportes' => ['/dashboard-reportes'],
            'reporte_general' => ['/reporte-general'],
        ];
    }

    #[DataProvider('paginas')]
    public function test_las_fechas_visibles_se_muestran_sin_hora(string $ruta): void
    {
        $respuesta = $this->actingAs($this->usuario)->get($ruta);

        $respuesta->assertOk();
        $this->assertSinFechasConHora($respuesta, 'la pagina '.$ruta);
    }

    public function test_la_ficha_qr_del_animal_carga_y_no_muestra_fechas_con_hora(): void
    {
        // Se usa el animal que TIENE genealogia: era el caso que reventaba,
        // porque la vista imprimia $genealogia->padre, que es un modelo y no se
        // puede convertir a texto.
        $genealogia = Genealogia::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->whereNotNull('animal_id')
            ->firstOrFail();

        $respuesta = $this->actingAs($this->usuario)->get("/animal/qr/{$genealogia->animal_id}");

        $respuesta->assertOk();
        $respuesta->assertSee('Ficha del Animal');
        $this->assertSinFechasConHora($respuesta, 'la ficha QR del animal');
    }

    public function test_la_pantalla_de_edicion_completa_del_qr_carga(): void
    {
        $animal = Animal::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->firstOrFail();

        $this->actingAs($this->usuario)
            ->get("/animal/qr/{$animal->id}/editar-todo")
            ->assertOk();
    }
}
