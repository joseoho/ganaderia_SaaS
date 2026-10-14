<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToTenant;

class Potrero extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'inquilino_id',
        'nombre',
        'tamaño',
        'tipo_pasto',
        'estado',
        'fecha_ultimo_ingreso',
        'fecha_ultimo_descanso'
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
