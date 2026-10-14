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
            'fecha_generado' => 'date',
        ];
    }
}
