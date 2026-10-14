<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToTenant;

class AnimalPotrero extends Model
{
    use BelongsToTenant;

    protected $table = 'animal_potrero';

    protected $fillable = [
        'animal_id',
        'potrero_id',
        'inquilino_id',
        'fecha_entrada',
        'fecha_salida'
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function potrero()
    {
        return $this->belongsTo(Potrero::class);
    }
}