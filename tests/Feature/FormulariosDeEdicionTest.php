<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\Gastos;
use App\Models\Genealogia;
use App\Models\InventarioInsumo;
use App\Models\Movilizacion;
use App\Models\Notificacion;
use App\Models\Potrero;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\RegistroVacuna;
use App\Models\Reproduccion;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Formularios de edicion: que se abran, que las fechas salgan en formato ISO y
 * que el formulario se pueda reenviar TAL CUAL se renderiza.
 *
 * El tercer punto es el importante: en lugar de enviar un payload escrito a
 * mano, se extraen los campos del HTML y se reenvian. Asi se detectan solos los
 * desajustes entre lo que el formulario envia y lo que valida el FormRequest.
 * Ese tipo de fallo ya se dio dos veces:
 *   - tratamientos/edit enviaba `fecha_inicio` y el request exige `fecha`.
 *   - registro_vacunas/edit enviaba `fecha` y el request exige `fecha_aplicacion`.
 * En ambos casos editar desde la interfaz era imposible.
 */
class FormulariosDeEdicionTest extends TestCase
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
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function paginasDeEdicion(): array
    {
        return [
            'animales' => [Animal::class, '/animales/%d/edit'],
            'potreros' => [Potrero::class, '/potreros/%d/edit'],
            'compras' => [Compra::class, '/compras/%d/edit'],
            'ventas' => [Venta::class, '/ventas/%d/edit'],
            'gastos' => [Gastos::class, '/gastos/%d/edit'],
            'inventario' => [InventarioInsumo::class, '/inventario/%d/edit'],
            'movilizaciones' => [Movilizacion::class, '/movilizaciones/%d/edit'],
            'produccion_carne' => [ProduccionCarne::class, '/produccion/%d/edit'],
            'produccion_leche' => [ProduccionLeche::class, '/produccion_leche/%d/edit'],
            'reproduccion' => [Reproduccion::class, '/reproduccion/%d/edit'],
            'tratamientos' => [Tratamiento::class, '/tratamientos/%d/edit'],
            'registro_vacunas' => [RegistroVacuna::class, '/registro_vacunas/%d/edit'],
            'genealogia' => [Genealogia::class, '/genealogia/%d/edit'],
            'notificaciones' => [Notificacion::class, '/notificaciones/%d/edit'],
        ];
    }

    /**
     * @param  class-string  $modelo
     */
    private function registroDeLaGanaderia(string $modelo): ?object
    {
        return $modelo::withoutTenantScope()
            ->where('inquilino_id', $this->usuario->inquilino_id)
            ->first();
    }

    /**
     * Extrae del HTML los campos que enviaria el formulario.
     *
     * @return array<string, string>
     */
    private function camposDelFormulario(string $html): array
    {
        $campos = [];

        // <input ...>
        preg_match_all('/<input\b[^>]*>/i', $html, $inputs);
        foreach ($inputs[0] as $tag) {
            if (! preg_match('/name="([^"]+)"/i', $tag, $m)) {
                continue;
            }
            $nombre = $m[1];
            if (in_array($nombre, ['_token', '_method'], true)) {
                continue;
            }
            $tipo = preg_match('/type="([^"]+)"/i', $tag, $t) ? strtolower($t[1]) : 'text';
            if (in_array($tipo, ['submit', 'button', 'file', 'reset'], true)) {
                continue;
            }
            if ($tipo === 'checkbox' && ! str_contains($tag, 'checked')) {
                continue;
            }
            $campos[$nombre] = preg_match('/value="([^"]*)"/i', $tag, $v) ? $v[1] : '';
        }

        // <select ...> con la opcion marcada
        preg_match_all('/<select\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/select>/is', $html, $selects, PREG_SET_ORDER);
        foreach ($selects as $select) {
            $nombre = $select[1];
            if (preg_match('/<option[^>]*value="([^"]*)"[^>]*selected/i', $select[2], $o)) {
                $campos[$nombre] = $o[1];
            }
        }

        // <textarea ...>
        preg_match_all('/<textarea\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/textarea>/is', $html, $areas, PREG_SET_ORDER);
        foreach ($areas as $area) {
            $campos[$area[1]] = trim($area[2]);
        }

        return $campos;
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('paginasDeEdicion')]
    public function test_la_pagina_de_edicion_carga(string $modelo, string $ruta): void
    {
        $registro = $this->registroDeLaGanaderia($modelo);

        if ($registro === null) {
            $this->markTestSkipped('Sin registros de este modulo para la ganaderia de prueba.');
        }

        $this->actingAs($this->usuario)->get(sprintf($ruta, $registro->id))->assertOk();
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('paginasDeEdicion')]
    public function test_los_campos_de_fecha_llevan_formato_iso(string $modelo, string $ruta): void
    {
        $registro = $this->registroDeLaGanaderia($modelo);

        if ($registro === null) {
            $this->markTestSkipped('Sin registros de este modulo para la ganaderia de prueba.');
        }

        $html = $this->actingAs($this->usuario)->get(sprintf($ruta, $registro->id))->getContent();

        preg_match_all('/type="date"[^>]*value="([^"]*)"/', $html, $coincidencias);

        if ($coincidencias[1] === []) {
            $this->assertTrue(true, 'Este formulario no tiene campos de fecha: nada que comprobar.');

            return;
        }

        foreach ($coincidencias[1] as $valor) {
            if ($valor === '') {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}$/',
                $valor,
                sprintf('El campo de fecha de %s se renderiza como "%s"; debe ser Y-m-d o el navegador lo mostrara vacio.', $ruta, $valor)
            );
        }
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('paginasDeEdicion')]
    public function test_el_formulario_se_puede_reenviar_tal_cual(string $modelo, string $ruta): void
    {
        $registro = $this->registroDeLaGanaderia($modelo);

        if ($registro === null) {
            $this->markTestSkipped('Sin registros de este modulo para la ganaderia de prueba.');
        }

        $urlEdicion = sprintf($ruta, $registro->id);

        $html = $this->actingAs($this->usuario)->get($urlEdicion)->getContent();
        $campos = $this->camposDelFormulario($html);

        $this->assertNotEmpty($campos, 'No se extrajo ningun campo del formulario de '.$urlEdicion);

        // OJO: el formulario NO se envia a la URL de edicion (que solo acepta
        // GET y devolveria 405), sino a la de actualizacion, que es la misma
        // ruta sin el sufijo /edit. Este detalle hizo que la primera version de
        // este test pasara siempre sin comprobar nada.
        $urlActualizacion = preg_replace('#/edit$#', '', $urlEdicion);

        $respuesta = $this->actingAs($this->usuario)->put($urlActualizacion, $campos);

        $this->assertSame(
            302,
            $respuesta->getStatusCode(),
            sprintf(
                'Al reenviar el formulario de %s se esperaba una redireccion y llego %d. Campos enviados: %s',
                $urlActualizacion,
                $respuesta->getStatusCode(),
                implode(', ', array_keys($campos))
            )
        );

        // Si el formulario envia nombres de campo distintos a los que valida el
        // FormRequest, aqui aparecen errores de validacion.
        try {
            $respuesta->assertSessionHasNoErrors();
        } catch (AssertionFailedError $e) {
            $this->fail(
                $e->getMessage()."\n".
                'El formulario de '.$urlEdicion.' envia estos campos: '.implode(', ', array_keys($campos))
            );
        }
    }

    /**
     * Campos de lista (select) cuyo valor NO debe cambiar al reenviar el
     * formulario sin tocarlo.
     *
     * Este es el fallo mas silencioso de los encontrados: si un <select> no
     * marca como `selected` el valor guardado, el navegador envia la PRIMERA
     * opcion de la lista. La validacion pasa y el dato se sustituye sin que el
     * usuario se entere. Le pasaba al estado del potrero, al tipo de
     * movilizacion, al nombre de la vacuna y al tipo de notificacion.
     *
     * @return array<string, array{0: class-string, 1: string, 2: string}>
     */
    public static function valoresQueNoDebenCambiar(): array
    {
        return [
            'potreros' => [Potrero::class, '/potreros/%d/edit', 'estado'],
            'movilizaciones' => [Movilizacion::class, '/movilizaciones/%d/edit', 'tipo'],
            'registro_vacunas' => [RegistroVacuna::class, '/registro_vacunas/%d/edit', 'nombre'],
            'notificaciones' => [Notificacion::class, '/notificaciones/%d/edit', 'tipo'],
        ];
    }

    /**
     * @param  class-string  $modelo
     */
    #[DataProvider('valoresQueNoDebenCambiar')]
    public function test_reenviar_el_formulario_no_cambia_el_valor_guardado(string $modelo, string $ruta, string $campo): void
    {
        $registro = $this->registroDeLaGanaderia($modelo);

        if ($registro === null) {
            $this->markTestSkipped('Sin registros de este modulo para la ganaderia de prueba.');
        }

        $valorOriginal = $registro->{$campo};

        if ($valorOriginal === null || $valorOriginal === '') {
            $this->markTestSkipped('El registro de prueba no tiene valor en '.$campo.'.');
        }

        $urlEdicion = sprintf($ruta, $registro->id);
        $html = $this->actingAs($this->usuario)->get($urlEdicion)->getContent();
        $campos = $this->camposDelFormulario($html);

        $this->actingAs($this->usuario)
            ->put(preg_replace('#/edit$#', '', $urlEdicion), $campos)
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $valorOriginal,
            $registro->fresh()->{$campo},
            sprintf(
                'Al reenviar el formulario de %s sin tocarlo, el campo %s ha pasado de "%s" a "%s": el select no marcaba el valor guardado.',
                $urlEdicion,
                $campo,
                $valorOriginal,
                $registro->fresh()->{$campo}
            )
        );
    }
}
