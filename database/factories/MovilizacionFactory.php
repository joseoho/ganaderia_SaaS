<?php

namespace Database\Factories;

use App\Models\Inquilino;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Movilizacion>
 */
class MovilizacionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contexto = app(TenantContext::class);

        return [
            // Se usa la ganaderia ACTIVA cuando la hay, para que los datos de
            // prueba de un registro y los de sus dependencias caigan en la
            // misma ganaderia. Sin contexto (comandos, pruebas sueltas) se crea
            // una, porque la columna es NOT NULL. El trait solo rellena
            // inquilino_id si viene vacio, asi que fijarlo aqui manda.
            'inquilino_id' => $contexto->has() ? $contexto->id() : Inquilino::factory(),
            'origen' => fake()->randomElement(['Potrero Norte', 'Finca La Esperanza', 'Feria']),
            'destino' => fake()->randomElement(['Potrero Sur', 'Matadero', 'Feria']),
            'fecha' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'tipo' => fake()->randomElement(['entrada', 'salida', 'interna']),
            'motivo' => fake()->sentence(4),
        ];
    }
}
