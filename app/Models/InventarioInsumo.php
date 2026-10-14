<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class InventarioInsumo extends Model
{
    use BelongsToTenant;

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
}
