<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    protected $table ='tratamientos';
   protected $fillable = [
    'animal_id',
    'tipo',
    'fecha',
    'detalle']; 
   
   public function animal() 
   { 
    return $this->belongsTo(Animal::class, 'animal_id');
    }
}
