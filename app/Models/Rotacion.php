<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Rotacion extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'potrero_id',
        'inquilino_id',
        'fecha_entrada',
        'fecha_salida',
        'dias_ocupacion',
        'carga_animal',
    ];

    public function potrero()
    {
        return $this->belongsTo(Potrero::class);
    }
}
