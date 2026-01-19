<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduccionLeche extends Model
{
    protected $table ='produccion_leche';
    protected $fillable = ['animal_id','fecha','litros'];
     public function animal() 
     { 
        return $this->belongsTo(Animal::class, 'animal_id');
     }
}
