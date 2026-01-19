<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacuna extends Model
{
    protected $table = 'vacunas';
    protected $fillable = ['nombre','descripcion']; 
    public function registros() 
    { 
        return $this->hasMany(RegistroVacuna::class, 'vacuna_id'); 
    }
}
