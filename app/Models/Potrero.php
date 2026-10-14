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

    /**
     * Tipos de dato, derivados del esquema real (Fase 4).
     *
     * Sin esto Eloquent devuelve las fechas como cadena, lo que hace
     * fragiles las comparaciones y el formateo. Ojo: las vistas que pintan
     * una fecha dentro de un <input type="date"> deben formatearla con
     * ->format('Y-m-d'), o el navegador mostrara el campo vacio.
     */
    protected function casts(): array
    {
        return [
            'fecha_ultimo_descanso' => 'date',
            'fecha_ultimo_ingreso' => 'date',
            'tamano' => 'decimal:2',
        ];
    }
}
