<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ProduccionCarne extends Model
{
    use BelongsToTenant;

    protected $table = 'produccion_carne';

    protected $fillable = ['inquilino_id', 'animal_id', 'fecha', 'peso', 'peso_anterior', 'ganancia_diaria', 'observaciones'];

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
            'ganancia_diaria' => 'decimal:2',
            'peso' => 'decimal:2',
            'peso_anterior' => 'decimal:2',
        ];
    }
}
