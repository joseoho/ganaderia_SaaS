<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToTenant;

class ProduccionLeche extends Model
{
    use BelongsToTenant;

    protected $table ='produccion_leche';
    protected $fillable = [
        'inquilino_id',
        'animal_id',
        'fecha',
        'turno',
        'litros',
        'litros_anteriores',
        'variacion',
        'observaciones',
    ];

     public function animal() 
     { 
        return $this->belongsTo(Animal::class, 'animal_id');
     }
     public function inquilino() 
     { 
        return $this->belongsTo(Inquilino::class, 'inquilino_id'  );
        }
}
