<?php

namespace Database\Factories;

use App\Models\Inquilino;
use App\Models\Plan;
use App\Models\Suscripcion;
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

    /**
     * Toda finca nace con un periodo vigente.
     *
     * Sin esto, cualquier finca creada por una fábrica quedaría en solo lectura
     * (el middleware falla cerrado) y las pruebas existentes no podrían crear
     * nada. En producción el periodo lo crea el alta (14 días de prueba).
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Inquilino $inquilino): void {
            $plan = Plan::query()->where('activo', true)->orderBy('orden')->first();

            if ($plan === null) {
                return;
            }

            Suscripcion::create([
                'inquilino_id' => $inquilino->id,
                'plan_id' => $plan->id,
                'inicio' => now()->subDays(5)->toDateString(),
                'fin' => now()->addDays(25)->toDateString(),
                'es_prueba' => false,
            ]);
        });
    }

    /** Finca con la suscripción vencida (para probar el modo lectura). */
    public function vencida(): static
    {
        return $this->afterCreating(function (Inquilino $inquilino): void {
            $inquilino->suscripciones()->update([
                'inicio' => now()->subDays(45)->toDateString(),
                'fin' => now()->subDays(10)->toDateString(),
            ]);
        });
    }
}
