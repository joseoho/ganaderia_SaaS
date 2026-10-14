<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reportes generados (registro de archivos emitidos).
 *
 * Cambios frente al esquema original:
 *  - Se anaden created_at / updated_at: el modelo Reporte tiene
 *    $timestamps = true y la tabla original no las tenia.
 *  - Se anade la clave foranea a inquilinos (no existia, solo un indice).
 *
 * Nota: la tabla esta vacia y no se encontro ningun flujo que la escriba;
 * se mantiene porque existe el modelo Reporte. Se revisara en la Fase 3
 * como posible codigo muerto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->string('tipo', 50)->nullable();
            $table->date('fecha_generado')->nullable();
            $table->string('archivo_url', 255)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha_generado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};
