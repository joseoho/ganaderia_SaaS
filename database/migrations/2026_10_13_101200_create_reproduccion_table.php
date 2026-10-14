<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reproduccion (celos, montas, diagnosticos de gestacion, partos).
 *
 * Cambios frente al esquema original:
 *  - DOS CORRECCIONES IMPORTANTES:
 *      1) `toro` era NOT NULL varchar(10) cuando la app lo trata como
 *         opcional (StoreReproduccionRequest: 'nullable|string') -> el alta
 *         sin toro fallaba. Ahora nullable y varchar(100) (10 caracteres es
 *         insuficiente para un nombre o arete).
 *      2) `inquilino_id` NO TENIA INDICE. Es la tabla que consulta el
 *         dashboard para "partos proximos" (whereRaw con DATE_ADD), asi que
 *         cada carga hacia un escaneo completo. Ahora tiene FK (con indice)
 *         y ademas un indice compuesto (inquilino_id, fecha).
 *  - Se anade la FK a inquilinos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reproduccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animales')->nullOnDelete();
            $table->string('tipo', 50);
            $table->date('fecha');
            $table->string('toro', 100)->nullable();
            $table->string('resultado', 100)->nullable();
            $table->string('observaciones', 250)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reproduccion');
    }
};
