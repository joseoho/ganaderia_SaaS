<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija la ganaderia activa a partir del usuario autenticado.
 *
 * IMPORTANTE (orden): debe ejecutarse DESPUES del middleware `auth`, por eso
 * se registra como alias 'tenant' y se anade al grupo de rutas protegidas, y
 * no al grupo `web` (que corre antes de autenticar y no tendria usuario).
 *
 * El TenantContext se limpia al finalizar la peticion para no arrastrar el
 * tenant entre peticiones en procesos de larga vida.
 */
class ResolveTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Una cuenta SIN ganadería no puede entrar al panel de una finca.
        //
        // Antes se dejaba pasar sin fijar el tenant y el filtro global, que falla
        // cerrado, reventaba con un 500 en la primera consulta («Consulta a
        // [App\Models\Tratamiento] sin ganaderia activa»). Al operador se le
        // manda a su panel; a cualquier otra cuenta, un 403 que se entiende.
        if ($user !== null && $user->getAttribute('inquilino_id') === null) {
            if ($user->esOperador()) {
                return redirect()->route('plataforma.pagos');
            }

            abort(403, 'Tu cuenta no está vinculada a ninguna ganadería.');
        }

        // getAttribute() y no la propiedad: el esquema declara inquilino_id como
        // NOT NULL, asi que la inferencia estatica da por hecho que nunca es
        // null, pero un usuario mal creado si puede llegar sin ganaderia y ese
        // caso hay que seguir cubriendolo.
        if ($user !== null && $user->getAttribute('inquilino_id') !== null) {
            $this->context->set((int) $user->inquilino_id);
        }

        try {
            return $next($request);
        } finally {
            $this->context->forget();
        }
    }
}
