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
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user !== null && $user->inquilino_id !== null) {
            $this->context->set((int) $user->inquilino_id);
        }

        try {
            return $next($request);
        } finally {
            $this->context->forget();
        }
    }
}
