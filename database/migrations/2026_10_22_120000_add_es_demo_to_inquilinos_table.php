<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca las ganaderias que se crearon con datos de ejemplo (`--demo`).
 *
 * Sirve para dos cosas: enseñar el aviso en el panel ("estos son datos de
 * ejemplo") y permitir vaciarlos de un boton cuando el dueño cargue los suyos.
 * Se apaga al vaciar, asi que una finca real no vuelve a mostrar el aviso.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inquilinos', 'es_demo')) {
            return;
        }

        Schema::table('inquilinos', function (Blueprint $table) {
            $table->boolean('es_demo')->default(false)->after('plan_suscripcion');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('inquilinos', 'es_demo')) {
            return;
        }

        Schema::table('inquilinos', function (Blueprint $table) {
            $table->dropColumn('es_demo');
        });
    }
};
