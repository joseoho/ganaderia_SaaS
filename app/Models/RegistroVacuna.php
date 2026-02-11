<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class RegistroVacuna extends Model
{
  protected $table ='registro_vacunas';
  protected $fillable = [
    'inquilino_id',
    'animal_id',
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
}
