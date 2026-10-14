<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'inquilino_id',
        'usuario_id',
        'mensaje',
        'tipo',
        'fecha_envio',
        'estado',
    ];

    /**
     * Relación: Una notificación pertenece a un usuario.
     *
     * Corregido: el segundo argumento de belongsTo es la clave foranea en
     * ESTA tabla. Estaba como 'id', con lo que la relacion unia users.id con
     * notificaciones.id en lugar de usar notificaciones.usuario_id.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Relación: Una notificación pertenece a una ganadería (tenant).
     */
    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class, 'inquilino_id');
    }
}
