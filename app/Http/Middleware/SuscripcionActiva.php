<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sin suscripción vigente, la finca queda en SOLO LECTURA.
 *
 * La decisión de producto que hay detrás: cuando vence el pago no se bloquea el
 * acceso ni se retienen los datos. El cliente sigue entrando, ve todo lo suyo,
 * puede exportarlo… pero no puede crear ni modificar nada hasta que se le
 * apruebe un pago. Un secuestro de datos cobra igual de mal y encima enfada.
 *
 * Se aplica después de `auth`, `verified` y `tenant`. El operador de la
 * plataforma no tiene ganadería y no pasa por aquí.
 */
class SuscripcionActiva
{
    /**
     * Rutas que siguen funcionando con la suscripción vencida: las de cobro
     * (para poder subir el comprobante) y las de sesión.
     *
     * @var array<int, string>
     */
    private const PERMITIDAS = [
        'suscripcion.index',
        'suscripcion.pagos.store',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null || $usuario->esOperador()) {
            return $next($request);
        }

        $inquilino = $usuario->inquilino;

        // Sin ganadería no hay nada que cobrar (no debería pasar).
        if ($inquilino === null || $inquilino->tieneSuscripcionVigente()) {
            return $next($request);
        }

        // Vencida: leer sí, escribir no.
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $nombre = $request->route()?->getName();

        if ($nombre !== null && in_array($nombre, self::PERMITIDAS, true)) {
            return $next($request);
        }

        return redirect()
            ->route('suscripcion.index')
            ->with('warning', 'La suscripción de esta ganadería venció: ahora está en modo lectura. Sube el comprobante de pago para reactivarla.');
    }
}
