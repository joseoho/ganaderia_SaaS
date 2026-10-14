<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aislamiento automatico por ganaderia para cualquier modelo con
 * `inquilino_id`.
 *
 * Al usar este trait, un modelo obtiene:
 *
 *  - FILTRO AUTOMATICO: agregarlo a un modelo hace que todas las consultas
 *    queden limitadas al tenant activo, tambien en las relaciones, los
 *    `find()`, los `update()` y los borrados. Un registro de otra
 *    ganaderia deja de existir para el codigo: el route-model binding
 *    devuelve 404 en lugar de exponerlo.
 *
 *  - ASIGNACION AUTOMATICA: al crear, si no se indica inquilino_id se toma
 *    el tenant activo. Esto elimina la necesidad de repetir
 *    `'inquilino_id' => Auth::user()->inquilino_id` en cada controlador y
 *    cubre tambien los modelos cuyo $fillable no incluia la columna.
 *
 *  - ESCAPE EXPLICITO: `withoutTenantScope()` para los pocos casos
 *    justificados (tareas administrativas, comandos de mantenimiento).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (self $model): void {
            if ($model->inquilino_id !== null) {
                return;
            }

            $context = app(TenantContext::class);

            if ($context->has()) {
                $model->inquilino_id = $context->id();
            }
        });
    }

    /**
     * Consulta sin el filtro de ganaderia. Uso deliberado y excepcional.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
