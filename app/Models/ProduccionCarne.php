<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduccionCarne extends Model
{
    protected $table ='produccion_carne';
    protected $fillable = ['inquilino_id','animal_id','fecha','peso','peso_anterior','ganancia_diaria','observaciones']; 
    public function animal() 
    { 
        return $this->belongsTo(Animal::class, 'animal_id');
     }
    public function inquilino() 
    { 
        return $this->belongsTo(Inquilino::class, 'inquilino_id');      
    }
}
