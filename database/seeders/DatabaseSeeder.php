<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Animal;
use App\Models\Inquilino;
use App\Models\User;
use App\Support\DatosDeEjemplo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostracion.
 *
 * Caracteristicas:
 *  - IDEMPOTENTE: usa firstOrCreate para tenants y usuarios, y no vuelve a
 *    sembrar una ganaderia que ya tiene animales. Es seguro ejecutarlo dos
 *    veces y, por tanto, seguro ejecutarlo sobre la base real.
 *  - MULTI-TENANT: cada registro lleva su inquilino_id y los registros
 *    relacionados comparten el mismo tenant (nunca se cruzan datos).
 *  - Credenciales de acceso deterministas (ver README/entrega):
 *      admin@ganaderia.test / password   -> Ganaderia Demo
 *      admin@esperanza.test / password   -> Ganaderia La Esperanza
 *      usuario@ganaderia.test / usuario2026 -> Ganaderia Demo (rol usuario)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $ganaderias = [
            [
                'nombre' => 'Ganaderia Demo',
                'plan_suscripcion' => 'profesional',
                'email' => 'admin@ganaderia.test',
                'prefijo' => 'DEMO',
            ],
            [
                'nombre' => 'Ganaderia La Esperanza',
                'plan_suscripcion' => 'basico',
                'email' => 'admin@esperanza.test',
                'prefijo' => 'ESP',
            ],
        ];

        foreach ($ganaderias as $g) {
            $inquilino = Inquilino::firstOrCreate(
                ['nombre' => $g['nombre']],
                [
                    'plan_suscripcion' => $g['plan_suscripcion'],
                    'fecha_registro' => now()->subMonths(6)->toDateString(),
                ]
            );

            $user = User::firstOrCreate(
                ['email' => $g['email']],
                [
                    'inquilino_id' => $inquilino->id,
                    'name' => 'Administrador '.$g['nombre'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    // El usuario que se crea con la ganaderia es su administrador.
                    'rol' => User::ROL_ADMINISTRADOR,
                ]
            );

            // Una cuenta de rol `usuario` en la ganaderia Demo, para poder
            // probar (o enseñar) los dos roles sin tener que crearla a mano.
            if ($g['nombre'] === 'Ganaderia Demo') {
                User::firstOrCreate(
                    ['email' => 'usuario@ganaderia.test'],
                    [
                        'inquilino_id' => $inquilino->id,
                        'name' => 'Usuario de Pruebas',
                        'password' => Hash::make('usuario2026'),
                        'email_verified_at' => now(),
                        'rol' => Rol::Usuario->value,
                        'activo' => true,
                    ]
                );
            }

            // Si esta ganaderia ya tiene animales, no se vuelve a sembrar.
            if (Animal::where('inquilino_id', $inquilino->id)->exists()) {
                continue;
            }

            DatosDeEjemplo::sembrar($inquilino, $user, $g['prefijo']);
        }
    }
}
