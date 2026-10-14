<?php

namespace Tests\Feature;

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
}
