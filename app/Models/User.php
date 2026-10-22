<?php

namespace App\Models;

use App\Enums\Permiso;
use App\Enums\Rol;
use App\Support\MatrizDePermisos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * ATENCION: este modelo NO lleva el trait BelongsToTenant, y es a proposito.
 *
 * Lo llevaba, y rompia la aplicacion entera: con el filtro global puesto en la
 * tabla `users`, cualquier consulta sobre usuarios lanzaba
 *
 *     Consulta a [App\Models\User] sin ganaderia activa: se ha bloqueado por
 *     seguridad.
 *
 * La razon es que la ganaderia se deduce DEL usuario (de su sesion), asi que
 * cuando hay que encontrarlo —al entrar con el formulario, o al recuperarlo de
 * la sesion en cada peticion— todavia no hay ninguna ganaderia activa. El
 * filtro, que falla cerrado, abortaba antes de poder autenticar a nadie.
 *
 * Los usuarios siguen teniendo `inquilino_id` con su clave foranea, y el
 * aislamiento de los DATOS DE NEGOCIO no cambia: los 18 modelos de negocio
 * mantienen el filtro. Lo que no puede filtrarse es la tabla de identidades.
 *
 * Hay dos pruebas que lo vigilan: `SesionRealTest` (entra por el formulario, no
 * con actingAs) y `AislamientoMultiTenantTest` (exige que User NO tenga el
 * filtro, para que a nadie se le ocurra volver a ponerselo).
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Rol del dueño de la ganaderia: puede con todo dentro de su ganaderia. */
    public const ROL_ADMINISTRADOR = 'administrador';

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'inquilino_id',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Aislamiento por ganaderia y asignacion automatica de inquilino_id:
     * los aporta el trait BelongsToTenant (Fase 2).
     *
     * Se elimino el global scope manual "fail-open" que habia aqui.
     */
    /** @return BelongsTo<Inquilino, $this> */
    public function inquilino(): BelongsTo
    {
        return $this->belongsto(Inquilino::class, 'inquilino_id');
    }

    /** @return HasMany<Notificacion, $this> */
    public function notificaciones(): HasMany
    {
        return $this->hasmany(Notificacion::class, 'usuario_id');
    }

    /**
     * El rol, siempre como enum y nunca como cadena suelta.
     *
     * Se llama `rolActual` y no `rol` a proposito: `rol` es la columna, y tener
     * un metodo con el mismo nombre que un atributo se confunde al leer.
     *
     * Si la base trae un valor desconocido, `Rol::desde()` devuelve el rol de
     * menos privilegio: un error de dedo en la tabla no puede dar administrador.
     */
    public function rolActual(): Rol
    {
        return Rol::desde($this->rol);
    }

    /**
     * Un rol desconocido (o vacio) NO da permisos de administrador: ante la
     * duda, se deniega.
     */
    public function esAdministrador(): bool
    {
        return $this->rolActual() === Rol::Administrador;
    }

    /**
     * La unica pregunta que hace el resto de la aplicacion.
     *
     * Todo el control de acceso pasa por aqui, y de aqui a la matriz, que es la
     * unica tabla donde se decide quien puede que.
     */
    public function puede(Permiso $permiso): bool
    {
        return MatrizDePermisos::concede($this->rolActual(), $permiso);
    }

    public function nombreDelRol(): string
    {
        return $this->rolActual()->etiqueta();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
