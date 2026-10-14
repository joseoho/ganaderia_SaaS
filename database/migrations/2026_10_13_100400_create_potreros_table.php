<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Potreros (parcelas de pastoreo).
 *
 * Cambios frente al esquema original:
 *  - `nombre` NOT NULL (StorePotreroRequest lo exige) + UNIQUE por tenant.
 *  - `tamano` NOT NULL: la app lo exige ('required|numeric|min:0'). La
 *    columna se llamaba `tamaño`, con enie; se renombro en la Fase 4 porque
 *    un identificador no ASCII complica volcados, herramientas y portabilidad
 *    entre motores.
 *  - `estado` se deja nullable: crear un potrero puede omitirlo (el update
 *    si lo exige), y el esquema nunca debe ser mas estricto que la app.
 *  - Se anade la clave foranea a inquilinos (no existia).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('potreros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->string('nombre', 50);
            $table->decimal('tamano', 8, 2);
            $table->string('tipo_pasto', 50)->nullable();
            $table->string('estado', 50)->nullable();
            $table->date('fecha_ultimo_ingreso')->nullable();
            $table->date('fecha_ultimo_descanso')->nullable();
            $table->timestamps();

            $table->unique(['inquilino_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('potreros');
    }
};
