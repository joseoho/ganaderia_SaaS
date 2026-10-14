<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use BelongsToTenant, HasFactory, Notifiable;

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
    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class, 'inquilino_id');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
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
