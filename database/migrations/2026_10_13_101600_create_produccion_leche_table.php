<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produccion de leche: litros por turno.
 *
 * Cambios frente al esquema original:
 *  - NOT NULL en fecha y litros (StoreProduccionLecheRequest los exige).
 *  - FK a inquilinos (no existia).
 *  - Indice compuesto (inquilino_id, fecha) para los reportes por rango.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion_leche', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->date('fecha');
            $table->string('turno', 50)->nullable();
            $table->decimal('litros', 10, 2);
            $table->decimal('litros_anteriores', 10, 2)->nullable();
            $table->decimal('variacion', 10, 2)->nullable();
            $table->string('observaciones', 250)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_leche');
    }
};
