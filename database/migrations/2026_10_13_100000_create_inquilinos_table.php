<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inquilinos (ganaderias / tenants del SaaS).
 *
 * Cambios frente al esquema original:
 *  - `nombre` pasa a NOT NULL + UNIQUE: es la identidad del tenant.
 *  - Se anaden created_at / updated_at. El modelo Inquilino tiene
 *    $timestamps = true y la tabla original no las tenia, por lo que
 *    cualquier Inquilino::create() fallaba con "Unknown column".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquilinos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('plan_suscripcion', 50)->nullable();
            $table->date('fecha_registro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquilinos');
    }
};
