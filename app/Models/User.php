<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use BelongsToTenant, HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'inquilino_id',
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

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
