<?php

namespace App\Providers;

use App\Enums\Permiso;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El contexto de tenant debe ser un singleton: es el unico punto
        // donde vive la ganaderia activa durante la peticion.
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cada permiso de la matriz es una puerta de Laravel, para poder
        // escribir `can:dinero.ver` en las rutas y `@can('dinero.ver')` en las
        // vistas sin repetir condiciones por toda la aplicacion.
        //
        // Se declaran TODAS las puertas, conceda o no el rol: asi una vista que
        // pregunte por un permiso nunca revienta, y un permiso que no se
        // conceda se comporta como negado, que es lo correcto.
        foreach (Permiso::cases() as $permiso) {
            Gate::define(
                $permiso->value,
                fn (User $usuario): bool => $usuario->puede($permiso)
            );
        }
    }
}
