<?php

namespace Tests;

use App\Support\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->guardAgainstRunningTestsOnRealDatabase();

        // Cada test parte sin ganaderia activa: el contexto se fija de forma
        // explicita en cada prueba de aislamiento.
        app(TenantContext::class)->forget();
    }

    /**
     * Los tests usan RefreshDatabase, que ELIMINA todas las tablas de la base
     * conectada. Esta guarda hace imposible ejecutarlos contra la base real.
     */
    private function guardAgainstRunningTestsOnRealDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database !== 'ganaderia_saas_test') {
            throw new RuntimeException(
                'BLOQUEADO: los tests solo pueden ejecutarse contra la base de pruebas '.
                "'ganaderia_saas_test'. Base detectada: '{$database}' ".
                "(conexion '{$connection}'). Revisa .env.testing y phpunit.xml."
            );
        }
    }
}
