<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compras.
 *
 * La validacion se hace en el controlador
 * inline: fecha, proveedor, descripcion y monto_total son obligatorios.
 * El esquema original los permitia NULL, asi que la base aceptaba compras
 * sin fecha ni importe (y los reportes sumaban filas invalidas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('proveedor', 100);
            $table->string('descripcion', 250);
            $table->decimal('monto_total', 10, 2);
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
