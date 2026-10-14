<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genealogia extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'genealogia';

    protected $fillable = ['animal_id', 'inquilino_id', 'padre_id', 'madre_id', 'observaciones'];

    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id');
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
