<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\InventarioInsumo;
use App\Models\Movilizacion;
use App\Models\Notificacion;
use App\Models\Potrero;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ExtraeCamposDeFormulario;
use Tests\TestCase;

/**
 * Lo que el usuario escribe en un formulario de edicion se guarda de verdad.
 *
 * El test de reenvio de formularios comprueba que el formulario se ACEPTA (302 y
 * sin errores de validacion), pero no que los datos lleguen a la base. Ese hueco
 * dejo pasar un fallo real: MovilizacionController@update validaba solo animal,
 * tipo y fecha, asi que editar el origen, el destino o el texto se descartaba sin
 * avisar. Aqui se escribe una marca en cada campo de texto, se envia y se
 * comprueba que la marca quedo guardada.
 *
 * El mapa campo-del-formulario => columna-de-la-tabla cubre los nombres que no
 * coinciden (el formulario de movilizaciones llama `descripcion` a `motivo`).
 */
class EdicionGuardaCambiosTest extends TestCase
{
    use ExtraeCamposDeFormulario, RefreshDatabase;

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
     * @return array<string, array{0: class-string, 1: string, 2: array<string, string>}>
     */
    public static function formularios(): array
    {
        return [
            'animales' => [Animal::class, '/animales/%d/edit', [
                'codigo_interno' => 'codigo_interno',
                'raza' => 'raza',
            ]],
            'potreros' => [Potrero::class, '/potreros/%d/edit', [
                'nombre' => 'nombre',
                'tipo_pasto' => 'tipo_pasto',
            ]],
            'movilizaciones' => [Movilizacion::class, '/movilizaciones/%d/edit', [
                'origen' => 'origen',
                'destino' => 'destino',
                'descripcion' => 'motivo',
            ]],
            'compras' => [Compra::class, '/compras/%d/edit', [
                'proveedor' => 'proveedor',
                'descripcion' => 'descripcion',
            ]],
            'ventas' => [Venta::class, '/ventas/%d/edit', [
                'cliente' => 'cliente',
                'descripcion' => 'descripcion',
            ]],
            'inventario' => [InventarioInsumo::class, '/inventario/%d/edit', [
                'nombre' => 'nombre',
                'unidad' => 'unidad',
                'descripcion' => 'descripcion',
            ]],
            'tratamientos' => [Tratamiento::class, '/tratamientos/%d/edit', [
                'motivo' => 'motivo',
                'medicamento' => 'medicamento',
            ]],
            // notificaciones esta aqui por un fallo concreto: su FormRequest
            // tenia rules() vacio, asi que update($request->validated())
            // actualizaba [] y la interfaz decia "actualizada correctamente".
            'notificaciones' => [Notificacion::class, '/notificaciones/%d/edit', [
                'mensaje' => 'mensaje',
            ]],
        ];
    }

    /**
     * @param  class-string  $modelo
     * @param  array<string, string>  $mapa
     */
    #[DataProvider('formularios')]
    public function test_lo_que_se_escribe_en_el_formulario_se_guarda(string $modelo, string $ruta, array $mapa): void
    {
        $registro = $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->first();

        if ($registro === null) {
            $this->markTestSkipped('Sin registros de este modulo para la ganaderia de prueba.');
        }

        $urlEdicion = sprintf($ruta, $registro->id);
        $html = $this->actingAs($this->usuario)->get($urlEdicion)->getContent();
        $campos = $this->camposDelFormulario($html);

        $marca = 'MARCA'.random_int(10000, 99999);
        $enviados = $campos;

        foreach (array_keys($mapa) as $campo) {
            $this->assertArrayHasKey(
                $campo,
                $campos,
                'El formulario de '.$urlEdicion.' ya no tiene el campo "'.$campo.'".'
            );
            $enviados[$campo] = $marca.' '.$campo;
        }

        $respuesta = $this->actingAs($this->usuario)
            ->put(preg_replace('#/edit$#', '', $urlEdicion), $enviados);

        $respuesta->assertSessionHasNoErrors();

        $fresco = $registro->fresh();

        foreach ($mapa as $campo => $columna) {
            $this->assertSame(
                $marca.' '.$campo,
                $fresco->{$columna},
                sprintf(
                    'Se escribio "%s" en el campo %s de %s y la columna %s quedo en "%s": el controlador descarta ese campo al actualizar.',
                    $marca.' '.$campo,
                    $campo,
                    $urlEdicion,
                    $columna,
                    $fresco->{$columna}
                )
            );
        }
    }
}
