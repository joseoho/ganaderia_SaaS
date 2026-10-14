<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Genealogia: padre, madre y observaciones de cada animal.
 *
 * Cambios frente al esquema original:
 *  - `observaciones` era NOT NULL mientras la app la trata como opcional
 *    (StoreGenealogiaRequest: 'nullable|string') -> el alta de genealogia
 *    sin observaciones fallaba. Ahora es nullable.
 *  - Se anade la FK a inquilinos (no existia).
 *  - animal_id conserva el borrado en cascada (la ficha genealogica no
 *    tiene sentido sin su animal); padre_id y madre_id pasan a NULL al
 *    eliminar el animal referenciado, para no perder el registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genealogia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animales')->cascadeOnDelete();
            $table->foreignId('padre_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->foreignId('madre_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->string('observaciones', 250)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'animal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genealogia');
    }
};
