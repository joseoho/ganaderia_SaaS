<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Gastos extends Model
{
    use BelongsToTenant;

    protected $fillable = ['inquilino_id', 'categoria', 'monto', 'fecha', 'descripcion'];

    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class);
    }
}
