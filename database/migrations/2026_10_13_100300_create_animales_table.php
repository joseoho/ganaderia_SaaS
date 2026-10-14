<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Animales: entidad central del sistema.
 *
 * Cambios frente al esquema original (donde 11 de 12 columnas aceptaban NULL):
 *  - Se alinean los NOT NULL con el contrato real de la app
 *    (StoreAnimalRequest: categoria, raza, fecha_nacimiento, sexo, estado).
 *    `tipo` y `peso_entrada` siguen siendo opcionales.
 *  - UNIQUE (inquilino_id, codigo_interno): el codigo identifica al animal
 *    dentro de la ganaderia. Al ser nullable, MySQL permite varios NULL.
 *  - `estado` con valor por defecto 'activo' como red de seguridad.
 *
 * Nota: `codigo_interno` es opcional para la app, por eso no es NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->string('codigo_interno', 50)->nullable();
            $table->string('categoria', 50);
            $table->string('raza', 50);
            $table->string('tipo', 50)->nullable();
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'H']);
            $table->decimal('peso_entrada', 6, 2)->nullable();
            $table->string('estado', 50)->default('activo');
            $table->timestamps();

            $table->unique(['inquilino_id', 'codigo_interno']);
            $table->index(['inquilino_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animales');
    }
};
