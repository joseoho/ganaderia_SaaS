<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite desactivar una cuenta sin borrarla.
 *
 * Borrar usuarios no es una opcion: los registros historicos (quien registro una
 * venta, quien hizo un pesaje) apuntan a esa cuenta. Se desactiva, deja de poder
 * entrar, y el historico sigue teniendo nombre.
 *
 * Por defecto `true`: las cuentas que ya existen siguen funcionando.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'activo')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('rol');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'activo')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
