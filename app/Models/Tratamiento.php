<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    use BelongsToTenant;

    protected $table = 'tratamientos';

    protected $fillable = [
        'inquilino_id',
        'animal_id',
        'motivo',
        'medicamento',
        'via',
        'dosis',
        'fecha',
        'fecha_fin',
        'observaciones',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class, 'inquilino_id');
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
            'fecha' => 'date',
            'fecha_fin' => 'date',
        ];
    }
}
