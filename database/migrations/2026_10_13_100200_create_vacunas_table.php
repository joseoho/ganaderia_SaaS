<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogo de vacunas.
 *
 * Cambios frente al esquema original:
 *  - Se anade inquilino_id: la tabla era GLOBAL, asi que las vacunas
 *    creadas por una ganaderia quedaban visibles para todas. En un SaaS
 *    multi-tenant real esto es una fuga de datos.
 *  - Se anaden created_at / updated_at: el modelo Vacuna tiene
 *    $timestamps = true y la tabla original no las tenia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacunas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->string('nombre', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacunas');
    }
};
