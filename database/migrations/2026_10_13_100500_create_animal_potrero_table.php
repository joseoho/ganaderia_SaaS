<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * animal_potrero: que animales estan (o estuvieron) en cada potrero.
 *
 * Es una tabla de ESTADO ACTUAL, no de historico: si se elimina un animal
 * o un potrero, la asignacion deja de tener sentido y se elimina en cascada.
 *
 * Cambios frente al esquema original:
 *  - Se anaden las claves foraneas de animal_id y potrero_id (solo habia
 *    indices sueltos, con el nombre mal escrito `inquilo_id`).
 *  - Se anade la FK a inquilinos.
 *  - Se conserva el nombre de tabla `animal_potrero` (singular) tal como
 *    lo espera el modelo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_potrero', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animales')->cascadeOnDelete();
            $table->foreignId('potrero_id')->constrained('potreros')->cascadeOnDelete();
            $table->date('fecha_entrada')->nullable();
            $table->date('fecha_salida')->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'potrero_id', 'fecha_salida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_potrero');
    }
};
