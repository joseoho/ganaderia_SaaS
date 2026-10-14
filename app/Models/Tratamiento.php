<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Tratamiento extends Model
{
    use BelongsToTenant;

    protected $table = 'tratamientos';

    protected $fillable = [
        'inquilino_id',
        'animal_id',
        'motivo',
        'medicamento',
        'via',
        'dosis',
        'fecha',
        'fecha_fin',
        'observaciones',
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
