<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias de middleware. NO se anade al grupo 'web': ese grupo corre
        // antes de 'auth', asi que todavia no habria usuario del que sacar
        // la ganaderia activa. Se aplica en routes/web.php tras 'auth'.
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
        ]);

        // Detras de un proxy inverso (balanceador, Cloudflare, nginx delante)
        // hay que confiar en las cabeceras X-Forwarded-* para que Laravel
        // detecte HTTPS y genere URLs y cookies seguras correctamente.
        //
        // Es OPT-IN a proposito: si la aplicacion esta expuesta directamente,
        // confiar en cualquier proxy permitiria falsear la IP y el esquema.
        // Se activa definiendo TRUSTED_PROXIES en el .env del servidor.
        $proxies = env('TRUSTED_PROXIES');

        if (! empty($proxies)) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
