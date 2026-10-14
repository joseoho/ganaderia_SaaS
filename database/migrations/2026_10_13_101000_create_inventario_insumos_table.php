<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario de insumos.
 *
 * Cambios frente al esquema original:
 *  - SE ANADE LA COLUMNA `ubicacion`, que el modelo (fillable) y
 *    StoreInventarioInsumoRequest ya usaban pero NO existia en la base de
 *    datos: cualquier envio con ese campo provocaba "Unknown column".
 *  - `minimo` era NOT NULL sin valor por defecto mientras la app lo trata
 *    como opcional -> los insert sin ese campo fallaban. Ahora NOT NULL con
 *    DEFAULT 0.
 *  - NOT NULL alineados con el contrato: nombre, cantidad, unidad,
 *    fecha_ingreso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->string('categoria', 100)->nullable();
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad', 20);
            $table->unsignedInteger('minimo')->default(0);
            $table->string('ubicacion', 100)->nullable();
            $table->timestamp('fecha_ingreso');
            $table->string('descripcion', 250)->nullable();
            $table->timestamps();

            $table->index(['inquilino_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_insumos');
    }
};
