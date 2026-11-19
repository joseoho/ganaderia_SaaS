<?php

namespace App\Support;

use App\Enums\Rol;
use App\Mail\AvisoParaOperador;
use App\Models\Inquilino;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Avisos del cobro: al cliente cuando se le revisa el pago y al operador cuando
 * entra uno nuevo.
 *
 * Sin esto, el cliente que sube un comprobante se queda esperando sin saber si
 * lo aprobaron o por qué lo rechazaron, y el operador solo se entera si abre el
 * panel por casualidad.
 */
final class Avisos
{
    /**
     * Notificación dentro de la app para cada administrador de la finca.
     *
     * Se crea con `withoutTenantScope()` porque el que la escribe puede ser el
     * operador, que no tiene ganadería activa.
     */
    public static function alAdministrador(Inquilino $inquilino, string $mensaje, string $tipo = 'suscripcion'): int
    {
        $admins = User::query()
            ->where('inquilino_id', $inquilino->id)
            ->where('rol', Rol::Administrador->value)
            ->get();

        foreach ($admins as $admin) {
            Notificacion::withoutTenantScope()->create([
                'inquilino_id' => $inquilino->id,
                'usuario_id' => $admin->id,
                'mensaje' => $mensaje,
                'tipo' => $tipo,
                'fecha_envio' => now()->toDateString(),
                'estado' => 'pendiente',
            ]);
        }

        return $admins->count();
    }

    /**
     * Correo a los operadores. Nunca lanza: si el correo no está configurado, el
     * comprobante ya está guardado y el pago sigue su curso.
     */
    public static function alOperador(string $asunto, string $texto): int
    {
        $emails = User::query()
            ->whereNull('inquilino_id')
            ->where('rol', Rol::Operador->value)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        if ($emails === []) {
            return 0;
        }

        try {
            Mail::to($emails)->send(new AvisoParaOperador($texto, $asunto));
        } catch (Throwable $e) {
            Log::warning('No se pudo avisar al operador del comprobante: '.$e->getMessage());

            return 0;
        }

        return count($emails);
    }
}
