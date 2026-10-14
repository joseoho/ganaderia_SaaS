<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AnimalPotrero extends Model
{
    use BelongsToTenant;

    protected $table = 'animal_potrero';

    protected $fillable = [
        'animal_id',
        'potrero_id',
        'inquilino_id',
        'fecha_entrada',
        'fecha_salida',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function potrero()
    {
        return $this->belongsTo(Potrero::class);
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
            'fecha_entrada' => 'date',
            'fecha_salida' => 'date',
        ];
    }
}
