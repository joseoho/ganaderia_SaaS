<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    use BelongsToTenant;

    protected $table = 'reportes';

    protected $fillable = ['inquilino_id', 'tipo', 'fecha_generado', 'archivo_url'];

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
