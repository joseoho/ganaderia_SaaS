<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Inquilino>
 */
class InquilinoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * La tabla `inquilinos` exige `nombre` NOT NULL y UNIQUE, por eso se usa
     * un valor unico.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Ganaderia '.fake()->unique()->lastName(),
            'plan_suscripcion' => fake()->randomElement(['basico', 'profesional', 'premium']),
            'fecha_registro' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
        ];
    }
}
