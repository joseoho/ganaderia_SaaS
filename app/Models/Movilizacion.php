<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movilizacion extends Model
{
    protected $table ='movilizaciones';
    protected $fillable = ['animal_id','fecha','origen','destino','motivo']; 
    public function animal() 
    { 
        return $this->belongsTo(Animal::class, 'animal_id');
     }
}
