<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rol del usuario dentro de su ganaderia.
 *
 * El valor por defecto es 'administrador' a proposito: al aplicar la migracion
 * los usuarios que ya existen conservan el acceso completo que tenian, y a
 * partir de ahi se les puede bajar a 'operario'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'rol')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('administrador');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'rol')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }
};
