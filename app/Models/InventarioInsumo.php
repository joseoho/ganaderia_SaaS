<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioInsumo extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'inventario_insumos';

    protected $fillable = ['inquilino_id', 'nombre', 'categoria', 'cantidad', 'unidad', 'minimo', 'ubicacion', 'fecha_ingreso', 'descripcion'];

    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class);
    }

    public function estaBajo()
    {
        return $this->cantidad <= $this->minimo;
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
            'cantidad' => 'decimal:2',
            'fecha_ingreso' => 'datetime',
        ];
    }
}
