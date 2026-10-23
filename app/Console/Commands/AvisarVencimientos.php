<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Avisa a los administradores de cada finca de que su suscripción vence.
 *
 * La mora se reduce más avisando que castigando: se generan avisos a 7, 3 y 1
 * día y el mismo día del vencimiento. El aviso es una notificación dentro de la
 * app (aparece en la campana y en el panel); cuando haya correo configurado, el
 * mismo texto se puede mandar por ahí sin cambiar el código.
 *
 * Es idempotente: se puede correr dos veces el mismo día sin duplicar avisos.
 */
class AvisarVencimientos extends Command
{
    protected $signature = 'suscripciones:avisar';

    protected $description = 'Crea los avisos de vencimiento (7, 3, 1 y 0 dias) para cada finca';

    public function handle(): int
    {
        $creados = 0;

        foreach ([7, 3, 1, 0] as $dias) {
            $fecha = now()->startOfDay()->addDays($dias)->toDateString();

            $suscripciones = Suscripcion::query()
                ->whereDate('fin', $fecha)
                ->get();

            foreach ($suscripciones as $suscripcion) {
                $mensaje = $dias === 0
                    ? 'Tu suscripción vence HOY. Si no subes el comprobante, la finca pasa a modo lectura.'
                    : sprintf(
                        'Tu suscripción vence en %d %s (el %s). Sube el comprobante para renovarla a tiempo.',
                        $dias,
                        $dias === 1 ? 'día' : 'días',
                        $suscripcion->fin->format('d/m/Y')
                    );

                $admins = User::query()
                    ->where('inquilino_id', $suscripcion->inquilino_id)
                    ->where('rol', 'administrador')
                    ->get();

                foreach ($admins as $admin) {
                    $yaAvisado = Notificacion::withoutTenantScope()
                        ->where('inquilino_id', $suscripcion->inquilino_id)
                        ->where('usuario_id', $admin->id)
                        ->where('tipo', 'suscripcion')
                        ->whereDate('fecha_envio', now()->toDateString())
                        ->where('mensaje', $mensaje)
                        ->exists();

                    if ($yaAvisado) {
                        continue;
                    }

                    Notificacion::withoutTenantScope()->create([
                        'inquilino_id' => $suscripcion->inquilino_id,
                        'usuario_id' => $admin->id,
                        'mensaje' => $mensaje,
                        'tipo' => 'suscripcion',
                        'fecha_envio' => now()->toDateString(),
                        'estado' => 'pendiente',
                    ]);

                    $creados++;
                }
            }
        }

        $this->info('Avisos de vencimiento creados: '.$creados);

        return self::SUCCESS;
    }
}
