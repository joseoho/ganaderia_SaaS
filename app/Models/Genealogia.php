<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Genealogia extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'genealogia';

    protected $fillable = ['animal_id', 'inquilino_id', 'padre_id', 'madre_id', 'observaciones'];

    /** @return BelongsTo<Animal, $this> */
    public function animal(): BelongsTo
    {
        return $this->belongsto(Animal::class, 'animal_id');
    }

    /** @return BelongsTo<Animal, $this> */
    public function padre(): BelongsTo
    {
        return $this->belongsto(Animal::class, 'padre_id');
    }

    /** @return BelongsTo<Animal, $this> */
    public function madre(): BelongsTo
    {
        return $this->belongsto(Animal::class, 'madre_id');
    }
}
