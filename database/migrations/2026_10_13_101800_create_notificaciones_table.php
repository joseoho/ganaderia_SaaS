<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notificaciones por usuario.
 *
 * Cambios frente al esquema original:
 *  - SE ANADE `inquilino_id`. La tabla solo tenia `usuario_id`, asi que en
 *    un SaaS multi-tenant no habia forma de aislar las notificaciones por
 *    ganaderia: era el unico modulo de negocio sin pertenencia a tenant.
 *  - `usuario_id` ahora tiene clave foranea real a `users` (antes era un
 *    indice suelto que apuntaba a nada).
 *  - NOT NULL alineados con NotificacionController: usuario_id, mensaje,
 *    tipo, fecha_envio y estado son obligatorios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquilino_id')->constrained('inquilinos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->text('mensaje');
            $table->string('tipo', 50);
            $table->date('fecha_envio');
            $table->string('estado', 20);
            $table->timestamps();

            $table->index(['inquilino_id', 'fecha_envio']);
            $table->index(['usuario_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
