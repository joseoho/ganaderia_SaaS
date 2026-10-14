<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'animales';

    protected $fillable = ['inquilino_id', 'codigo_interno', 'categoria', 'raza', 'tipo', 'fecha_nacimiento', 'sexo', 'peso_entrada', 'estado'];

    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class);
    }

    public function genealogia()
    {
        return $this->hasOne(Genealogia::class);
    }

    public function movilizaciones()
    {
        return $this->hasMany(Movilizacion::class);
    }

    public function reproducciones()
    {
        return $this->hasMany(Reproduccion::class);
    }

    public function produccionLeche()
    {
        return $this->hasMany(ProduccionLeche::class);
    }

    public function produccionCarne()
    {
        return $this->hasMany(ProduccionCarne::class);
    }

    public function tratamientos()
    {
        return $this->hasMany(Tratamiento::class);
    }

    public function registrosVacunas()
    {
        return $this->hasMany(RegistroVacuna::class);
    }

    public function animalPotreros()
    {
        return $this->hasMany(AnimalPotrero::class);
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
            'fecha_nacimiento' => 'date',
            'peso_entrada' => 'decimal:2',
        ];
    }
}
