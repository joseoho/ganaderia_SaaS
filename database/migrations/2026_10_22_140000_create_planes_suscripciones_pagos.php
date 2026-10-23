<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cobro por comprobante: planes, suscripciones y pagos.
 *
 * - Un plan fija precio, duracion y limites. Sustituye al texto muerto
 *   `inquilinos.plan_suscripcion`.
 * - Una suscripcion es el periodo pagado: mientras `fin` no pase, la finca
 *   trabaja normal; cuando pasa, queda en solo lectura hasta que se apruebe un
 *   pago.
 * - Un pago es lo que el cliente sube (comprobante) y el operador aprueba o
 *   rechaza. La referencia del banco es unica: es la unica defensa real contra
 *   subir dos veces el mismo comprobante.
 *
 * Ademas `users.inquilino_id` pasa a admitir nulos: el operador de la
 * plataforma no pertenece a ninguna ganaderia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->decimal('precio_usd', 10, 2);
            $table->unsignedSmallInteger('dias')->default(30);
            $table->unsignedInteger('limite_animales')->nullable();
            $table->unsignedSmallInteger('limite_usuarios')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('planes');
            $table->date('inicio');
            $table->date('fin');
            $table->boolean('es_prueba')->default(false);
            $table->timestamps();

            $table->index(['inquilino_id', 'fin']);
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('suscripcion_id')->nullable()->constrained('suscripciones')->nullOnDelete();
            $table->foreignId('plan_id')->constrained('planes');

            $table->decimal('monto', 12, 2);
            $table->string('moneda', 3)->default('USD');
            $table->decimal('tasa', 14, 4)->nullable();
            $table->string('metodo', 30);
            // La referencia del banco, unica: el mismo comprobante no vale dos veces.
            $table->string('referencia', 60)->unique();
            $table->string('comprobante', 255);
            $table->date('fecha_pago');

            $table->string('estado', 20)->default('pendiente');
            $table->text('notas')->nullable();
            $table->string('motivo_rechazo', 255)->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'estado']);
            $table->index(['estado', 'created_at']);
        });

        // El operador de la plataforma no tiene ganaderia.
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['inquilino_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('inquilino_id')->nullable()->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('inquilino_id')->references('id')->on('inquilinos')->nullOnDelete();
        });

        // Planes iniciales. El precio y los limites se pueden cambiar despues.
        $ahora = now();
        DB::table('planes')->insert([
            [
                'nombre' => 'Basico', 'precio_usd' => 12.00, 'dias' => 30,
                'limite_animales' => 60, 'limite_usuarios' => 2, 'activo' => true, 'orden' => 1,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Profesional', 'precio_usd' => 25.00, 'dias' => 30,
                'limite_animales' => 300, 'limite_usuarios' => 6, 'activo' => true, 'orden' => 2,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ],
        ]);

        // Las fincas que ya existen reciben un periodo de cortesia de 90 dias:
        // nadie se despierta un dia en solo lectura por un cambio de facturacion.
        $basico = DB::table('planes')->where('nombre', 'Basico')->value('id');

        foreach (DB::table('inquilinos')->pluck('id') as $inquilinoId) {
            DB::table('suscripciones')->insert([
                'inquilino_id' => $inquilinoId,
                'plan_id' => $basico,
                'inicio' => $ahora->toDateString(),
                'fin' => $ahora->copy()->addDays(90)->toDateString(),
                'es_prueba' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('suscripciones');
        Schema::dropIfExists('planes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['inquilino_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('inquilino_id')->nullable(false)->change();
        });
    }
};
