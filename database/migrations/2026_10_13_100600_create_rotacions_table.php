<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * rotacions: historico de ocupacion de cada potrero.
 *
 * El nombre de tabla se mantiene como `rotacions` (pluralizacion de Laravel
 * para el modelo Rotacion, que no declara $table). Renombrarlo obligaria a
 * tocar el modelo y su controlador.
 *
 * Cambios frente al esquema original:
 *  - FK a potreros y a inquilinos (no existian; solo indices con el nombre
 *    mal escrito `inquilo_id`).
 *  - `carga_animal` pasa a decimal(10,2) para evitar desbordes al calcular
 *    animales/hectarea.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('potrero_id')->constrained('potreros')->cascadeOnDelete();
            $table->date('fecha_entrada')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->unsignedInteger('dias_ocupacion')->nullable();
            $table->decimal('carga_animal', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha_entrada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotacions');
    }
};
