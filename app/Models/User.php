<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $table = 'users';
    protected $fillable = [
        'name',
        'email',
        'password',
        'inquilino_id',
    ];

    protected static function booted()
    {
        static::addGlobalScope('inquilino', function ($query) {
            if (app()->has('inquilino_id')) {
                $query->where('inquilino_id', app('inquilino_id'));
            }
        });
    }

    // Relación con Inquilino
    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class, 'inquilino_id'); // 👈 clave foránea explícita
    }

    // Relación con Notificaciones
    public function notificaciones()
    {
         return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}