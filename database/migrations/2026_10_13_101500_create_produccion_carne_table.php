<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produccion de carne: pesajes y ganancia diaria por animal.
 *
 * Cambios frente al esquema original:
 *  - NOT NULL en fecha y peso (StoreProduccionCarneRequest los exige); el
 *    original los permitia NULL, asi que los informes podian sumar filas
 *    sin datos.
 *  - FK a inquilinos (no existia).
 *  - Indice compuesto (inquilino_id, fecha) para los reportes por rango.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produccion_carne', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->date('fecha');
            $table->decimal('peso', 6, 2);
            $table->decimal('peso_anterior', 6, 2)->nullable();
            $table->decimal('ganancia_diaria', 6, 2)->nullable();
            $table->string('observaciones', 250)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_carne');
    }
};
