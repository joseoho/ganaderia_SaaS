<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\Genealogia;
use App\Models\Inquilino;
use App\Models\InventarioInsumo;
use App\Models\Movilizacion;
use App\Models\Notificacion;
use App\Models\Potrero;
use App\Models\RegistroVacuna;
use App\Models\Reproduccion;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regresion del P0 detectado en la Fase 3 y de los modulos de edicion.
 *
 * 1) Siete FormRequest comprobaban la propiedad con
 *    `Auth::user()->inquilino->inquilino_id`, pero el PK de `inquilinos` es
 *    `id`: devolvia null, la comparacion era false y authorize() denegaba
 *    SIEMPRE. El dueno legitimo recibia 403 al editar potreros, ventas,
 *    reproduccion, tratamientos, vacunas, genealogia e inventario.
 *
 * 2) Tres controladores usaban `$request->validated()` sobre un
 *    Illuminate\Http\Request normal (que no tiene ese metodo) y devolvian 500:
 *    potreros, compras y movilizaciones. Se incluyen aqui para que no vuelva
 *    a pasar.
 *
 * El valor ':animal' del payload se sustituye por un animal real de la
 * ganaderia del usuario: los formularios de edicion SI envian ese campo.
 */
class EdicionModulosTest extends TestCase
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
     * @return array<string, array{0: class-string, 1: string, 2: array<string, mixed>}>
     */
    public static function modulosAfectados(): array
    {
        return [
            'potreros' => [Potrero::class, '/potreros/%d', [
                'nombre' => 'Potrero Editado', 'tamano' => 9.75, 'tipo_pasto' => 'Brachiaria', 'estado' => 'disponible',
            ]],
            'compras' => [Compra::class, '/compras/%d', [
                'fecha' => '2026-03-02', 'proveedor' => 'Proveedor Editado',
                'descripcion' => 'Compra editada', 'monto_total' => 999.99,
            ]],
            'ventas' => [Venta::class, '/ventas/%d', [
                'cliente' => 'Cliente Editado', 'descripcion' => 'Venta editada',
                'fecha' => '2026-03-01', 'monto_total' => 1234.50,
            ]],
            'movilizaciones' => [Movilizacion::class, '/movilizaciones/%d', [
                'animal_id' => ':animal', 'tipo' => 'traslado', 'fecha' => '2026-02-05',
            ]],
            'reproduccion' => [Reproduccion::class, '/reproduccion/%d', [
                'animal_id' => ':animal', 'tipo' => 'monta', 'fecha' => '2026-02-01', 'toro' => 'TORO-X',
            ]],
            'tratamientos' => [Tratamiento::class, '/tratamientos/%d', [
                'animal_id' => ':animal', 'motivo' => 'Cojera',
                'medicamento' => 'Antibiotico', 'fecha' => '2026-02-10',
            ]],
            'registro_vacunas' => [RegistroVacuna::class, '/registro_vacunas/%d', [
                'animal_id' => ':animal', 'nombre' => 'Aftosa', 'fecha_aplicacion' => '2026-02-15',
            ]],
            'genealogia' => [Genealogia::class, '/genealogia/%d', [
                'animal_id' => ':animal', 'observaciones' => 'Genealogia editada',
            ]],
            'inventario' => [InventarioInsumo::class, '/inventario/%d', [
                'nombre' => 'Sal mineralizada', 'cantidad' => 30, 'unidad' => 'bulto',
                'fecha_ingreso' => '2026-02-20 08:00:00',
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolverPayload(array $payload): array
    {
        $animalId = Animal::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->value('id');

        return array_map(fn ($valor) => $valor === ':animal' ? $animalId : $valor, $payload);
    }

    /**
     * @param  class-string  $modelo
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('modulosAfectados')]
    public function test_el_dueno_puede_editar_su_registro(string $modelo, string $ruta, array $payload): void
    {
        $registro = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->firstOrFail();

        $respuesta = $this->actingAs($this->usuario)
            ->put(sprintf($ruta, $registro->id), $this->resolverPayload($payload));

        $this->assertNotSame(403, $respuesta->getStatusCode(), 'El dueno legitimo recibe 403 en '.$ruta);
        $this->assertNotSame(500, $respuesta->getStatusCode(), 'Error interno al editar en '.$ruta);
        $respuesta->assertSessionHasNoErrors();
        $respuesta->assertRedirect();
    }

    /**
     * @param  class-string  $modelo
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('modulosAfectados')]
    public function test_no_se_puede_editar_un_registro_de_otra_ganaderia(string $modelo, string $ruta, array $payload): void
    {
        $ajeno = $modelo::withoutTenantScope()
            ->where('inquilino_id', '!=', $this->usuario->inquilino_id)
            ->first();

        if ($ajeno === null) {
            $this->markTestSkipped('El seeder no creo registros de la otra ganaderia para '.$modelo);
        }

        $this->actingAs($this->usuario)
            ->put(sprintf($ruta, $ajeno->id), $this->resolverPayload($payload))
            ->assertNotFound();
    }

    public function test_el_listado_de_cada_modulo_carga_sin_errores(): void
    {
        $rutas = [
            '/animales', '/compras', '/ventas', '/genealogia', '/inventario',
            '/movilizaciones', '/produccion', '/produccion_leche', '/registro_vacunas',
            '/reproduccion', '/tratamientos', '/potreros', '/gastos', '/notificaciones',
            '/reportes', '/dashboard', '/home',
        ];

        foreach ($rutas as $ruta) {
            $this->actingAs($this->usuario)->get($ruta)->assertOk();
        }
    }

    public function test_un_usuario_no_puede_editar_la_notificacion_de_otro(): void
    {
        $otro = User::query()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->where('id', '!=', $this->usuario->id)
            ->first();

        if ($otro === null) {
            // El seeder crea un usuario por ganaderia: se crea el segundo aqui.
            $otro = User::factory()->create(['inquilino_id' => $this->usuario->inquilino_id]);
        }

        $notificacion = Notificacion::create([
            'inquilino_id' => $this->usuario->inquilino_id,
            'usuario_id' => $otro->id,
            'mensaje' => 'Notificacion de otro usuario',
            'tipo' => 'prueba',
            'fecha_envio' => '2026-03-01',
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->usuario)->get("/notificaciones/{$notificacion->id}/edit")->assertForbidden();
        $this->actingAs($this->usuario)->put("/notificaciones/{$notificacion->id}", [
            'mensaje' => 'Intento de edicion ajena', 'tipo' => 'prueba',
            'fecha_envio' => '2026-03-02', 'estado' => 'leida',
        ])->assertForbidden();
    }

    public function test_editar_no_permite_cambiar_de_ganaderia(): void
    {
        $registro = Animal::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->firstOrFail();

        $otraGanaderia = Inquilino::where('id', '!=', $this->usuario->inquilino_id)->firstOrFail();

        $this->actingAs($this->usuario)->put("/animales/{$registro->id}", [
            'codigo_interno' => $registro->codigo_interno,
            'categoria' => 'bovino',
            'raza' => 'Test',
            'fecha_nacimiento' => '2025-01-01',
            'sexo' => 'M',
            'estado' => 'activo',
            'inquilino_id' => $otraGanaderia->id,
        ]);

        $this->assertSame(
            $this->usuario->inquilino_id,
            $registro->fresh()->inquilino_id,
            'Un usuario ha podido mover su registro a otra ganaderia (salto de tenant).'
        );
    }
}
