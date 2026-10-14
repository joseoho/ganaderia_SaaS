<?php

namespace Database\Factories;

use App\Models\Inquilino;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Rotacion>
 */
class RotacionFactory extends Factory
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
            'potrero_id' => \App\Models\Potrero::factory(),
            'fecha_entrada' => fake()->dateTimeBetween('-1 year', '-1 month')->format('Y-m-d'),
            'fecha_salida' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'dias_ocupacion' => fake()->numberBetween(5, 60),
            'carga_animal' => fake()->randomFloat(2, 0.5, 4),
        ];
    }
}
