<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Potrero extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'inquilino_id',
        'nombre',
        'tamano',
        'tipo_pasto',
        'estado',
        'fecha_ultimo_ingreso',
        'fecha_ultimo_descanso',
    ];

    public function animales()
    {
        return $this->hasMany(AnimalPotrero::class);
    }

    public function rotaciones()
    {
        return $this->hasMany(Rotacion::class);
    }
}
