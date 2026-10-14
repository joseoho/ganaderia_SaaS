<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de vacunas aplicadas.
 *
 * Cambios frente al esquema original:
 *  - `nombre` pasa de varchar(50) a varchar(100): 50 caracteres se quedan
 *    cortos para el nombre comercial de una vacuna.
 *  - Se anade la FK a inquilinos (no existia).
 *  - animal_id y vacuna_id pasan a NULL al eliminar el registro
 *    referenciado, para no perder el historico de vacunacion.
 *  - Indice compuesto (inquilino_id, fecha_aplicacion) para los informes
 *    sanitarios por rango de fechas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_vacunas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->foreignId('vacuna_id')->nullable()->constrained('vacunas')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('lote', 50)->nullable();
            $table->string('proveedor', 50)->nullable();
            $table->string('via', 50)->nullable();
            $table->date('fecha_aplicacion');
            $table->decimal('dosis', 10, 2)->nullable();
            $table->date('proxima_dosis')->nullable();
            $table->string('observaciones', 250)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha_aplicacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_vacunas');
    }
};
