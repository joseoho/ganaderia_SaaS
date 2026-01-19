<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reproduccion extends Model
{
    protected $table ='reproducciones';
    protected $fillable = ['animal_id','tipo_evento','fecha_evento','resultado']; 
    
    public function animal() 
    {
         return $this->belongsTo(Animal::class, 'animal_id');
    }
}
