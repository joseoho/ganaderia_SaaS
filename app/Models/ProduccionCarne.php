<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduccionCarne extends Model
{
    protected $table ='produccion_carne';
    protected $fillable = ['animal_id','fecha','peso','ganancia_peso']; 
    public function animal() 
    { 
        return $this->belongsTo(Animal::class, 'animal_id');
     }
}
