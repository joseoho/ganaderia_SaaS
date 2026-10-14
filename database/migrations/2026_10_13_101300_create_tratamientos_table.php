<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tratamientos veterinarios.
 *
 * Cambios frente al esquema original:
 *  - NOT NULL alineados con el contrato (StoreTratamientoRequest: motivo,
 *    medicamento y fecha son obligatorios).
 *  - FK a inquilinos (no existia).
 *  - animal_id pasa a NULL al eliminar el animal, para conservar el
 *    historico sanitario de la ganaderia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->string('motivo', 50);
            $table->string('medicamento', 250);
            $table->string('via', 50)->nullable();
            $table->string('dosis', 250)->nullable();
            $table->date('fecha');
            $table->date('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tratamientos');
    }
};
