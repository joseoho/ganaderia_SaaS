<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Contexto de la ganaderia (tenant) activa durante la peticion.
 *
 * Se registra como singleton en AppServiceProvider. El middleware
 * ResolveTenant lo rellena a partir del usuario autenticado, y ademas este
 * contexto lo resuelve de forma PEREZOSA si nadie lo ha fijado todavia.
 *
 * Esa resolucion perezosa es importante: el route-model binding
 * (SubstituteBindings) se ejecuta antes que los middleware de ruta, asi que
 * en el momento de resolver un parametro {animal} todavia no ha corrido
 * ResolveTenant. Sin este respaldo, un registro de otra ganaderia se
 * encontraria y el filtro no protegeria el acceso por URL.
 *
 * Ningun modelo debe leer el inquilino_id de Auth directamente: asi el
 * aislamiento multi-tenant queda en un unico punto y es testeable.
 */
class TenantContext
{
    private ?int $id = null;

    public function set(?int $id): void
    {
        $this->id = $id;
    }

    public function has(): bool
    {
        return $this->id() !== null;
    }

    public function id(): ?int
    {
        if ($this->id !== null) {
            return $this->id;
        }

        // hasUser() no dispara consultas: solo comprueba si la sesion ya
        // tiene un usuario resuelto (lo hace el middleware auth).
        if (Auth::hasUser()) {
            $id = Auth::user()->inquilino_id ?? null;

            if ($id !== null) {
                $this->id = (int) $id;
            }
        }

        return $this->id;
    }

    /**
     * Devuelve el tenant activo o falla de forma explicita.
     *
     * Se usa cuando una operacion NO puede ejecutarse sin tenant: es
     * preferible un error claro a una consulta que devuelva datos de todas
     * las ganaderias.
     */
    public function idOrFail(): int
    {
        if ($this->id === null) {
            throw new RuntimeException(
                'No hay una ganaderia (tenant) activa en el contexto. '.
                'Falta el middleware ResolveTenant o no hay usuario autenticado.'
            );
        }

        return $this->id;
    }

    public function forget(): void
    {
        $this->id = null;
    }
}
