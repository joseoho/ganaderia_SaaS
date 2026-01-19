<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class RegistroVacuna extends Model
{
  protected $table ='registro_vacunas';
  protected $fillable = [
    'animal_id',
    'vacuna_id',
    'fecha_aplicacion',
    'proxima_dosis']; 
    public function animal() 
    { 
        return $this->belongsTo(Animal::class, 'animal_id');
     } 
     public function vacuna() 
     { 
        return $this->belongsTo(Vacuna::class, 'vacuna_id'); 
    }
}
