<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use BelongsToTenant;

    protected $table = 'compras';

    protected $fillable = ['inquilino_id', 'fecha', 'proveedor', 'descripcion', 'monto_total'];

    /**
     * Aislamiento por ganaderia y asignacion automatica de inquilino_id:
     * los aporta el trait BelongsToTenant (Fase 2).
     *
     * Se elimino el global scope manual que habia aqui: era "fail-open"
     * (si no habia tenant en el contenedor simplemente no filtraba) y
     * ademas estaba duplicado en varios modelos con criterios distintos.
     */
    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class);
    }
}
