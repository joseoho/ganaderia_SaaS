<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anade inquilino_id a users.
 *
 * Contexto: la tabla `users` la crea la migracion stock de Laravel
 * (0001_01_01_000000) sin pertenencia a tenant. El esquema original tenia
 * `inquilino_id int NOT NULL` pero SIN clave foranea, asi que se podia
 * asignar un inquilino inexistente.
 *
 * Decision de diseno: NOT NULL + FK con borrado en cascada. Todo usuario
 * pertenece a una ganaderia; si se elimina el tenant, se eliminan sus
 * usuarios. (Un futuro super-admin de plataforma requeriria hacerla
 * nullable; se deja anotado como pendiente.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('inquilino_id')
                ->after('id')
                ->constrained('inquilinos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inquilino_id');
        });
    }
};
