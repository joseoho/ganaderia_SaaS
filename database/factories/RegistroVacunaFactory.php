<?php

namespace Database\Factories;

use App\Models\Inquilino;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\RegistroVacuna>
 */
class RegistroVacunaFactory extends Factory
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
            'animal_id' => \App\Models\Animal::factory(),
            'vacuna_id' => \App\Models\Vacuna::factory(),
            'nombre' => fake()->randomElement(['Fiebre Aftosa', 'Brucelosis', 'Rabia']),
            'fecha_aplicacion' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'proxima_dosis' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
        ];
    }
}
