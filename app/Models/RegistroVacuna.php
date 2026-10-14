<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroVacuna extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'registro_vacunas';

    protected $fillable = [
        'inquilino_id',
        'animal_id',
        'vacuna_id',
        'nombre',
        'lote',
        'proveedor',
        'via',
        'fecha_aplicacion',
        'dosis',
        'proxima_dosis',
        'observaciones'];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function vacuna()
    {
        return $this->belongsTo(Vacuna::class, 'vacuna_id');
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
            'dosis' => 'decimal:2',
            'fecha_aplicacion' => 'date',
            'proxima_dosis' => 'date',
        ];
    }
}
