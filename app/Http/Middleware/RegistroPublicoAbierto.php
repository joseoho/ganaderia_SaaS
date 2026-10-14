<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puerta del registro publico (REGISTRO_PUBLICO).
 *
 * Las rutas de alta existen siempre, pero responden 404 si el registro esta
 * cerrado. Se hace asi, y no dejando de registrar las rutas, para que el
 * interruptor se pueda probar: la decision se toma en cada peticion y no al
 * arrancar la aplicacion.
 *
 * Se responde 404 y no 403 a proposito: a quien llega de fuera no le interesa
 * saber que ahi hay algo cerrado.
 */
class RegistroPublicoAbierto
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('app.registro_publico'), 404);

        return $next($request);
    }
}
