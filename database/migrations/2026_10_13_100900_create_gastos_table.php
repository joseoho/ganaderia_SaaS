<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gastos operativos.
 *
 * Cambios frente al esquema original:
 *  - `id` pasa a bigint unsigned (antes bigint) para ser coherente con el
 *    resto del esquema.
 *  - `inquilino_id` pasa a NOT NULL: un gasto huerfano no pertenece a
 *    ninguna ganaderia y rompe el aislamiento multi-tenant.
 *  - created_at / updated_at gestionados por Laravel (el original usaba
 *    DEFAULT current_timestamp y ON UPDATE, que competia con Eloquent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->string('categoria', 255);
            $table->decimal('monto', 15, 2);
            $table->date('fecha');
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
