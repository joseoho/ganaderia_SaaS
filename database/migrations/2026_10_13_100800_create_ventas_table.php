<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas.
 *
 * StoreVentaRequest exige cliente, descripcion, fecha y monto_total.
 * El esquema original los permitia NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('cliente', 100);
            $table->string('descripcion', 250);
            $table->decimal('monto_total', 10, 2);
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
