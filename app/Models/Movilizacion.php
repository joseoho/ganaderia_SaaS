<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movilizacion extends Model
{
    protected $table ='movilizacion';
    protected $fillable = ['inquilino_id','animal_id','tipo','fecha','origen','destino','motivo']; 
    public function animal() 
    { 
        return $this->belongsTo(Animal::class, 'animal_id');
     }
    public function inquilino() 
    { 
        return $this->belongsTo(Inquilino::class, 'inquilino_id');      
    }
}
