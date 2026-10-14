<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToTenant;

class Reproduccion extends Model
{
    use BelongsToTenant;

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
