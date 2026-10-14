<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToTenant;

class Vacuna extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $table = 'vacunas';

    /**
     * `inquilino_id` es obligatorio desde la Fase 1: el catalogo de vacunas
     * era global y permitia que una ganaderia viera las vacunas de otra.
     */
    protected $fillable = ['inquilino_id','nombre','descripcion']; 
    public function registros() 
    { 
        return $this->hasMany(RegistroVacuna::class, 'vacuna_id'); 
    }
}
