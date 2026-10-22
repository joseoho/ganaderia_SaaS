<?php

namespace App\Http\Middleware;

use App\Enums\Permiso;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Borrar registros exige el permiso `registros.eliminar`.
 *
 * Se decide por el NOMBRE DE LA RUTA y el verbo en lugar de tocar los catorce
 * controladores: un resource que anada un `destroy` en el futuro queda
 * protegido sin que nadie tenga que acordarse.
 *
 * El "quien puede" NO se decide aqui: se pregunta a la matriz, igual que el
 * resto de la aplicacion. Este middleware solo traduce un borrado a un permiso.
 *
 * Se aplica DESPUES de `auth`, asi que cuando llega aqui ya se sabe quien es el
 * usuario; si no lo hay, no se decide nada y el middleware de autenticacion
 * responde con la redireccion al login.
 */
class SoloAdministradorPuedeBorrar
{
    public function handle(Request $request, Closure $next): Response
    {
        $ruta = $request->route();
        $nombre = $ruta?->getName();

        if ($request->isMethod('DELETE') && $nombre !== null && str_ends_with($nombre, '.destroy')) {
            $usuario = $request->user();

            if ($usuario !== null && ! $usuario->puede(Permiso::RegistrosEliminar)) {
                abort(403, 'Solo el administrador de la ganadería puede eliminar registros.');
            }
        }

        return $next($request);
    }
}
