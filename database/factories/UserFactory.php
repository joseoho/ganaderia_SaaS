<?php

namespace Database\Factories;

use App\Models\Inquilino;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * `users.inquilino_id` es NOT NULL con clave foranea a `inquilinos`, asi
     * que por defecto cada usuario crea su propia ganaderia. Los tests de
     * aislamiento pasaran un inquilino_id explicito para agrupar usuarios.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inquilino_id' => Inquilino::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
