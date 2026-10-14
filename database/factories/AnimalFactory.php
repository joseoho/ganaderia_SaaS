<?php

namespace Database\Factories;

use App\Models\Inquilino;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Animal>
 */
class AnimalFactory extends Factory
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
            'codigo_interno' => 'ANI-'.fake()->unique()->numberBetween(1000, 99999),
            'categoria' => fake()->randomElement(['bovino', 'equino', 'porcino']),
            'raza' => fake()->randomElement(['Holstein', 'Brahman', 'Angus', 'Charolais']),
            'fecha_nacimiento' => fake()->dateTimeBetween('-6 years', '-6 months')->format('Y-m-d'),
            'sexo' => fake()->randomElement(['M', 'H']),
            'estado' => fake()->randomElement(['activo', 'vendido', 'muerto']),
        ];
    }
}
