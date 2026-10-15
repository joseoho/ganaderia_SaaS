<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Animal extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'animales';

    protected $fillable = ['inquilino_id', 'codigo_interno', 'categoria', 'raza', 'tipo', 'fecha_nacimiento', 'sexo', 'peso_entrada', 'estado'];

    /** @return BelongsTo<Inquilino, $this> */
    public function inquilino(): BelongsTo
    {
        return $this->belongsto(Inquilino::class);
    }

    /** @return HasOne<Genealogia, $this> */
    public function genealogia(): HasOne
    {
        return $this->hasone(Genealogia::class);
    }

    /** @return HasMany<Movilizacion, $this> */
    public function movilizaciones(): HasMany
    {
        return $this->hasmany(Movilizacion::class);
    }

    /** @return HasMany<Reproduccion, $this> */
    public function reproducciones(): HasMany
    {
        return $this->hasmany(Reproduccion::class);
    }

    /** @return HasMany<ProduccionLeche, $this> */
    public function produccionLeche(): HasMany
    {
        return $this->hasmany(ProduccionLeche::class);
    }

    /** @return HasMany<ProduccionCarne, $this> */
    public function produccionCarne(): HasMany
    {
        return $this->hasmany(ProduccionCarne::class);
    }

    /** @return HasMany<Tratamiento, $this> */
    public function tratamientos(): HasMany
    {
        return $this->hasmany(Tratamiento::class);
    }

    /** @return HasMany<RegistroVacuna, $this> */
    public function registrosVacunas(): HasMany
    {
        return $this->hasmany(RegistroVacuna::class);
    }

    /** @return HasMany<AnimalPotrero, $this> */
    public function animalPotreros(): HasMany
    {
        return $this->hasmany(AnimalPotrero::class);
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
