<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BelongsToTenant;

class Gastos extends Model
{
    use BelongsToTenant;

    protected $fillable = ['inquilino_id', 'categoria', 'monto', 'fecha', 'descripcion'];

    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class);
    }
}
