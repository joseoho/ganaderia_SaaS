<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renombra el rol `operario` a `usuario`.
 *
 * Los dos roles de la aplicacion son `administrador` y `usuario`: el operario
 * era el nombre viejo del segundo. Se renombra el VALOR de la columna (no solo
 * la etiqueta) para que el enum `App\Enums\Rol` y la base digan lo mismo.
 *
 * Ojo con el orden de despliegue: en cuanto este el codigo nuevo, leer un `rol`
 * desconocido devuelve el rol de menos privilegio, no administrador. Asi que una
 * fila sin migrar pierde permisos (se nota) en vez de ganarlos (peligroso).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('rol', 'operario')
            ->update(['rol' => 'usuario']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('rol', 'usuario')
            ->update(['rol' => 'operario']);
    }
};
