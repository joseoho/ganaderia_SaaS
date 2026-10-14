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
            'carga_animal' => 'decimal:2',
            'fecha_entrada' => 'date',
            'fecha_salida' => 'date',
        ];
    }
}
