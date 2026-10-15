<?php

namespace App\Models;

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

    /** Puede con todo dentro de su ganaderia, incluido borrar y gestionar el equipo. */
    public const ROL_ADMINISTRADOR = 'administrador';

    /**
     * Trabajo del dia a dia: registrar, editar e importar. No borra.
     *
     * Se eligieron dos roles a proposito. Un sistema de permisos por accion
     * habria que mantenerlo y hoy no hay ninguna necesidad real que lo pida;
     * agregar un tercer rol es anadir una constante y una condicion.
     */
    public const ROL_OPERARIO = 'operario';

    /** @var array<string, string> */
    public const ROLES = [
        self::ROL_ADMINISTRADOR => 'Administrador',
        self::ROL_OPERARIO => 'Operario',
    ];

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
     * Un rol desconocido (o vacio) NO da permisos de administrador: ante la
     * duda, se deniega.
     */
    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esOperario(): bool
    {
        return $this->rol === self::ROL_OPERARIO;
    }

    public function nombreDelRol(): string
    {
        return self::ROLES[$this->rol] ?? ucfirst((string) $this->rol);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
