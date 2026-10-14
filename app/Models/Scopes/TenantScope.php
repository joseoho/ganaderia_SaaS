<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use RuntimeException;

/**
 * Filtro global de aislamiento multi-tenant.
 *
 * Comportamiento, por orden de prioridad:
 *
 *  1. Si hay un tenant activo, toda consulta queda limitada a el.
 *  2. Si NO hay tenant activo y estamos en una peticion HTTP, se lanza una
 *     excepcion (FALLA CERRADO). Nunca se devuelven datos de otras
 *     ganaderias por olvidar el filtro: es preferible un error visible.
 *  3. Si NO hay tenant activo y estamos en consola (migraciones, seeders,
 *     comandos de artisan), el filtro no se aplica, para permitir sembrar
 *     y mantener datos. Los tests que comprueban aislamiento fijan un
 *     tenant explicito.
 *
 * Antes de la Fase 2 el filtro era "fail-open": si no habia tenant en el
 * contenedor simplemente no filtraba, con lo que cualquier olvido exponia
 * los datos de todas las ganaderias.
 */
class TenantScope implements Scope
{
    /**
     * Fuerza el comportamiento "falla cerrado" aunque estemos en consola.
     *
     * Existe porque en CLI (y por tanto en PHPUnit, que tambien es CLI) la
     * deteccion automatica no se activa, y sin este interruptor la rama mas
     * importante de seguridad no seria verificable con tests. null = automatico.
     */
    public static ?bool $forceFailClosed = null;

    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->has()) {
            $builder->where($model->getTable().'.inquilino_id', $context->id());

            return;
        }

        $failClosed = self::$forceFailClosed ?? ! app()->runningInConsole();

        if ($failClosed) {
            throw new RuntimeException(sprintf(
                'Consulta a [%s] sin ganaderia activa: se ha bloqueado por seguridad. '.
                'Aplica el middleware ResolveTenant o fija un tenant explicito.',
                $model::class
            ));
        }
    }
}
