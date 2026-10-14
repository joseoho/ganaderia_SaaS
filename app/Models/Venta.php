<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use BelongsToTenant;

    protected $table = 'ventas';

    protected $fillable = ['inquilino_id', 'fecha', 'cliente', 'descripcion', 'monto_total'];

    /**
     * Aislamiento por ganaderia y asignacion automatica de inquilino_id:
     * los aporta el trait BelongsToTenant (Fase 2).
     *
     * Se elimino el global scope manual "fail-open" que habia aqui.
     */
    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class);
    }
}
