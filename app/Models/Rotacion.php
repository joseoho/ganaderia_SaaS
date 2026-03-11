<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rotacion extends Model
{
    protected $fillable = [
        'potrero_id',
        'inquilino_id',
        'fecha_entrada',
        'fecha_salida',
        'dias_ocupacion',
        'carga_animal'
    ];

    public function potrero()
    {
        return $this->belongsTo(Potrero::class);
    }
}