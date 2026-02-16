<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reproduccion extends Model
{
    protected $table ='reproduccion';
    protected $fillable = 
    ['inquilino_id',
    'animal_id',
    'tipo',
    'fecha',
    'toro',
    'resultado',
    'observaciones'
    ]; 
    
    public function animal() 
    {
         return $this->belongsTo(Animal::class, 'animal_id');
    }
    public function inquilino() 
    {
         return $this->belongsTo(Inquilino::class, 'inquilino_id');
    }
}
