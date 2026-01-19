<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;
    protected $table ='notificaciones';
    protected $fillable = [
        'usuario_id',
        'mensaje',
        'tipo',
        'fecha_envio',
        'estado',
    ];

    /**
     * Relación: Una notificación pertenece a un usuario.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id');
    }
}
