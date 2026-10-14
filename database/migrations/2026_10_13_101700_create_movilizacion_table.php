<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Movilizacion de animales (traslados, ventas, muertes).
 *
 * El nombre de tabla se mantiene en singular (`movilizacion`) porque asi lo
 * declara el modelo Movilizacion con $table.
 *
 * Cambios frente al esquema original:
 *  - NOT NULL en tipo y fecha (MovilizacionController valida animal_id,
 *    tipo y fecha como obligatorios).
 *  - FK a inquilinos (no existia).
 *  - animal_id pasa a NULL al eliminar el animal, para conservar el
 *    historico de movimientos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movilizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->string('tipo', 50);
            $table->date('fecha');
            $table->string('origen', 100)->nullable();
            $table->string('destino', 100)->nullable();
            $table->string('motivo', 100)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movilizacion');
    }
};
