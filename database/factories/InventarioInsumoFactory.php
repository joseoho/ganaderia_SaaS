<?php

namespace Database\Factories;

use App\Models\Inquilino;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InventarioInsumo>
 */
class InventarioInsumoFactory extends Factory
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
            'nombre' => fake()->randomElement(['Sal mineral', 'Concentrado', 'Vacuna', 'Desparasitante', 'Alambre']),
            'cantidad' => fake()->randomFloat(2, 1, 500),
            'unidad' => fake()->randomElement(['kg', 'litros', 'unidades', 'bultos']),
            'minimo' => fake()->numberBetween(1, 50),
            'fecha_ingreso' => now(),
            'descripcion' => fake()->sentence(4),
        ];
    }
}
