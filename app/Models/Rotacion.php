<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToTenant;

class Rotacion extends Model
{
    use BelongsToTenant;

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