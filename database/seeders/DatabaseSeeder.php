<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Compra;
use App\Models\Gastos;
use App\Models\Genealogia;
use App\Models\Inquilino;
use App\Models\InventarioInsumo;
use App\Models\Movilizacion;
use App\Models\Notificacion;
use App\Models\Potrero;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\RegistroVacuna;
use App\Models\Reproduccion;
use App\Models\Tratamiento;
use App\Models\User;
use App\Models\Vacuna;
use App\Models\Venta;
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
 *      usuario@ganaderia.test / usuario2026   -> Ganaderia La Esperanza
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

            // Si esta ganaderia ya tiene animales, no se vuelve a sembrar.
            if (Animal::where('inquilino_id', $inquilino->id)->exists()) {
                continue;
            }

            $this->sembrarGanaderia($inquilino, $user, $g['prefijo']);
        }
    }

    private function sembrarGanaderia(Inquilino $inquilino, User $user, string $prefijo): void
    {
        $tenant = $inquilino->id;

        // --- Potreros ---
        $potreros = collect([
            ['nombre' => 'Potrero Norte', 'tamano' => 12.50, 'tipo_pasto' => 'Brachiaria', 'estado' => 'ocupado'],
            ['nombre' => 'Potrero Sur', 'tamano' => 8.75, 'tipo_pasto' => 'Estrella', 'estado' => 'descanso'],
            ['nombre' => 'Potrero El Alto', 'tamano' => 20.00, 'tipo_pasto' => 'Kikuyo', 'estado' => 'disponible'],
        ])->map(fn (array $p) => Potrero::create($p + [
            'inquilino_id' => $tenant,
            'fecha_ultimo_ingreso' => now()->subDays(30)->toDateString(),
            'fecha_ultimo_descanso' => now()->subDays(5)->toDateString(),
        ]));

        // --- Animales ---
        $animales = collect([
            ['codigo_interno' => $prefijo.'-001', 'categoria' => 'bovino', 'raza' => 'Holstein',  'tipo' => 'Madre', 'sexo' => 'H', 'peso_entrada' => 385.00, 'estado' => 'activo'],
            ['codigo_interno' => $prefijo.'-002', 'categoria' => 'bovino', 'raza' => 'Brahman',   'tipo' => 'Padre', 'sexo' => 'M', 'peso_entrada' => 520.00, 'estado' => 'activo'],
            ['codigo_interno' => $prefijo.'-003', 'categoria' => 'bovino', 'raza' => 'Simmental', 'tipo' => 'Maute', 'sexo' => 'M', 'peso_entrada' => 240.00, 'estado' => 'activo'],
            ['codigo_interno' => $prefijo.'-004', 'categoria' => 'bovino', 'raza' => 'Nelore',    'tipo' => 'Madre', 'sexo' => 'H', 'peso_entrada' => 410.00, 'estado' => 'activo'],
            ['codigo_interno' => $prefijo.'-005', 'categoria' => 'ovino',  'raza' => 'Dorper',    'tipo' => null,    'sexo' => 'H', 'peso_entrada' => 62.00,  'estado' => 'activo'],
            ['codigo_interno' => $prefijo.'-006', 'categoria' => 'bovino', 'raza' => 'Cebu',      'tipo' => 'Madre', 'sexo' => 'H', 'peso_entrada' => 395.00, 'estado' => 'vendido'],
        ])->map(fn (array $a, int $i) => Animal::create($a + [
            'inquilino_id' => $tenant,
            'fecha_nacimiento' => now()->subYears(3)->addMonths($i)->toDateString(),
        ]));

        $madre = $animales->firstWhere('sexo', 'H');

        // --- Catalogo de vacunas del tenant ---
        $vacuna = Vacuna::create([
            'inquilino_id' => $tenant,
            'nombre' => 'Aftosa bivalente',
            'descripcion' => 'Vacuna contra la fiebre aftosa, dosis de 2 ml.',
        ]);

        // --- Sanidad ---
        RegistroVacuna::create([
            'inquilino_id' => $tenant,
            'animal_id' => $madre->id,
            'nombre' => $vacuna->nombre,
            'lote' => 'L-2026-014',
            'proveedor' => 'Veterinaria Central',
            'via' => 'intramuscular',
            'fecha_aplicacion' => now()->subDays(20)->toDateString(),
            'dosis' => 2.00,
            'proxima_dosis' => now()->addMonths(6)->toDateString(),
            'observaciones' => 'Sin reacciones adversas.',
        ]);

        Tratamiento::create([
            'inquilino_id' => $tenant,
            'animal_id' => $madre->id,
            'motivo' => 'Mastitis',
            'medicamento' => 'Ceftiofur',
            'via' => 'intramuscular',
            'dosis' => '1 ml / 50 kg',
            'fecha' => now()->subDays(12)->toDateString(),
            'fecha_fin' => now()->subDays(8)->toDateString(),
            'observaciones' => 'Mejoria completa al quinto dia.',
        ]);

        // --- Reproduccion ---
        Reproduccion::create([
            'inquilino_id' => $tenant,
            'animal_id' => $madre->id,
            'tipo' => 'monta',
            'fecha' => now()->subDays(283)->toDateString(),
            'toro' => $prefijo.'-002',
            'resultado' => 'preñada',
            'observaciones' => 'Parto previsto en los proximos dias.',
        ]);

        // --- Produccion ---
        foreach ([7, 5, 3, 1] as $dias) {
            ProduccionLeche::create([
                'inquilino_id' => $tenant,
                'animal_id' => $madre->id,
                'fecha' => now()->subDays($dias)->toDateString(),
                'turno' => $dias % 2 === 0 ? 'tarde' : 'mañana',
                'litros' => 18.50 - $dias,
                'litros_anteriores' => 19.00 - $dias,
                'variacion' => -0.50,
                'observaciones' => null,
            ]);
        }

        $carne = $animales->firstWhere('tipo', 'Maute');
        foreach ([60, 30, 5] as $i => $dias) {
            ProduccionCarne::create([
                'inquilino_id' => $tenant,
                'animal_id' => $carne->id,
                'fecha' => now()->subDays($dias)->toDateString(),
                'peso' => 240.00 + ($i * 22.50),
                'peso_anterior' => $i === 0 ? 240.00 : 240.00 + (($i - 1) * 22.50),
                'ganancia_diaria' => 0.75,
                'observaciones' => 'Pesaje mensual.',
            ]);
        }

        // --- Genealogia ---
        Genealogia::create([
            'inquilino_id' => $tenant,
            'animal_id' => $madre->id,
            'padre_id' => $animales->firstWhere('tipo', 'Padre')?->id,
            'madre_id' => null,
            'observaciones' => 'Madre no registrada en el sistema.',
        ]);

        // --- Movilizacion ---
        Movilizacion::create([
            'inquilino_id' => $tenant,
            'animal_id' => $madre->id,
            'tipo' => 'traslado',
            'fecha' => now()->subDays(15)->toDateString(),
            'origen' => $potreros[0]->nombre,
            'destino' => $potreros[1]->nombre,
            'motivo' => 'Rotacion de pastoreo.',
        ]);

        // --- Inventario de insumos ---
        InventarioInsumo::create([
            'inquilino_id' => $tenant,
            'nombre' => 'Sal mineralizada',
            'categoria' => 'alimentacion',
            'cantidad' => 25.00,
            'unidad' => 'bulto',
            'minimo' => 5,
            'ubicacion' => 'Bodega principal',
            'fecha_ingreso' => now()->subDays(40),
            'descripcion' => 'Bultos de 50 kg.',
        ]);
        InventarioInsumo::create([
            'inquilino_id' => $tenant,
            'nombre' => 'Guantes de palpacion',
            'categoria' => 'veterinaria',
            'cantidad' => 3.00,
            'unidad' => 'caja',
            'minimo' => 4,
            'ubicacion' => 'Bodega principal',
            'fecha_ingreso' => now()->subDays(25),
            'descripcion' => 'Stock por debajo del minimo.',
        ]);

        // --- Finanzas ---
        Compra::create([
            'inquilino_id' => $tenant,
            'fecha' => now()->subDays(35)->toDateString(),
            'proveedor' => 'Agroinsumos del Valle',
            'descripcion' => 'Compra de sal mineralizada y cercas.',
            'monto_total' => 1850.00,
        ]);
        Venta::create([
            'inquilino_id' => $tenant,
            'fecha' => now()->subDays(10)->toDateString(),
            'cliente' => 'Frigorifico La Region',
            'descripcion' => 'Venta de 2 novillos.',
            'monto_total' => 6400.00,
        ]);
        Gastos::create([
            'inquilino_id' => $tenant,
            'categoria' => 'nomina',
            'monto' => 1200.00,
            'fecha' => now()->subDays(5)->toDateString(),
            'descripcion' => 'Pago de jornales.',
        ]);
        Gastos::create([
            'inquilino_id' => $tenant,
            'categoria' => 'mantenimiento',
            'monto' => 340.50,
            'fecha' => now()->subDays(3)->toDateString(),
            'descripcion' => 'Reparacion de cercas.',
        ]);

        // --- Notificaciones ---
        Notificacion::create([
            'inquilino_id' => $tenant,
            'usuario_id' => $user->id,
            'mensaje' => 'El stock de guantes de palpacion esta por debajo del minimo.',
            'tipo' => 'inventario',
            'fecha_envio' => now()->toDateString(),
            'estado' => 'pendiente',
        ]);
        Notificacion::create([
            'inquilino_id' => $tenant,
            'usuario_id' => $user->id,
            'mensaje' => 'Hay un parto previsto en los proximos 7 dias.',
            'tipo' => 'reproduccion',
            'fecha_envio' => now()->toDateString(),
            'estado' => 'leida',
        ]);
    }
}
