<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Paginas de error propias.
 *
 * Laravel trae unas genericas; en produccion el usuario debe ver algo coherente
 * con la aplicacion. Ademas estas vistas no usan @vite ni la base de datos, de
 * modo que se renderizan aunque el build o la base fallen.
 */
class PaginasDeErrorTest extends TestCase
{
    private const MENSAJE_SECRETO = 'SECRETO-INTERNO-QUE-NO-DEBE-VERSE';

    private function rutaQueRevienta(): void
    {
        Route::get('/dsh-rompe-a-proposito', function () {
            throw new RuntimeException(self::MENSAJE_SECRETO);
        });
    }

    public function test_una_url_inexistente_muestra_la_pagina_404_propia(): void
    {
        $this->get('/una-ruta-que-no-existe-xyz')
            ->assertNotFound()
            ->assertSee('Página no encontrada')
            ->assertSee('Volver al inicio');
    }

    public function test_las_cinco_paginas_de_error_compilan(): void
    {
        foreach ([403, 404, 419, 500, 503] as $codigo) {
            $html = view("errors.{$codigo}")->render();

            $this->assertStringContainsString((string) $codigo, $html);
            $this->assertStringContainsString('Volver al inicio', $html);
            // No deben depender del manifiesto de Vite.
            $this->assertStringNotContainsString('@vite', $html);
        }
    }

    /**
     * En produccion (APP_DEBUG=false) un error interno no puede contar al
     * visitante que excepcion fue, con que mensaje ni en que archivo.
     *
     * Las pruebas corren con APP_DEBUG=true, asi que este comportamiento no
     * estaba cubierto: es justo la configuracion del servidor y no la de aqui.
     */
    public function test_un_error_interno_no_filtra_detalles_con_la_depuracion_apagada(): void
    {
        config(['app.debug' => false]);
        $this->rutaQueRevienta();

        $respuesta = $this->get('/dsh-rompe-a-proposito');

        $respuesta->assertStatus(500);

        $html = $respuesta->getContent();

        $this->assertStringNotContainsString(self::MENSAJE_SECRETO, $html, 'El mensaje de la excepcion llego al navegador.');
        $this->assertStringNotContainsString('RuntimeException', $html, 'El tipo de excepcion llego al navegador.');
        $this->assertStringNotContainsString('vendor', $html, 'Una ruta del servidor llego al navegador.');
        $this->assertStringNotContainsString('Whoops', $html, 'Se sirvio la pagina de depuracion de Laravel.');
        $this->assertStringContainsString('500', $html);
    }

    /**
     * La comprobacion contraria: con la depuracion ENCENDIDA los detalles si
     * aparecen. Sin esto, la prueba anterior podria estar pasando en vacio (por
     * ejemplo, si la ruta ni siquiera llegara a ejecutarse).
     */
    public function test_con_la_depuracion_encendida_los_detalles_si_aparecen(): void
    {
        config(['app.debug' => true]);
        $this->rutaQueRevienta();

        $html = $this->get('/dsh-rompe-a-proposito')->getContent();

        $this->assertStringContainsString(
            self::MENSAJE_SECRETO,
            $html,
            'La ruta de prueba no se ejecuto: la comprobacion anterior no estaria midiendo nada.'
        );
    }
}
