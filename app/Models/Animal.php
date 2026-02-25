<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;
    protected $table ='animales';
    protected $fillable = ['inquilino_id','codigo_interno','categoria','raza','tipo','fecha_nacimiento','sexo','peso_entrada','estado'];
    

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
}
