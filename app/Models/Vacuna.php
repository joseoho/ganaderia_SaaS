<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacuna extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'vacunas';

    /**
     * `inquilino_id` es obligatorio desde la Fase 1: el catalogo de vacunas
     * era global y permitia que una ganaderia viera las vacunas de otra.
     */
    protected $fillable = ['inquilino_id', 'nombre', 'descripcion'];

    /** @return HasMany<RegistroVacuna, $this> */
    public function registros(): HasMany
    {
        return $this->hasmany(RegistroVacuna::class, 'vacuna_id');
    }
}
