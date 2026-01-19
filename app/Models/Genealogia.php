<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genealogia extends Model
{
    protected $table ='genealogias';
    protected $fillable = ['animal_id','padre_id','madre_id']; 
    
    public function animal()
     { 
        return $this->belongsTo(Animal::class, 'id');
     } 
     public function padre() 
     { 
        return $this->belongsTo(Animal::class, 'padre_id');
     } 
     public function madre() 
     { 
        return $this->belongsTo(Animal::class, 'madre_id'); 
    }
}
