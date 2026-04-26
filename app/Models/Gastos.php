<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gastos extends Model
{
    protected $fillable = ['inquilino_id', 'categoria', 'monto', 'fecha', 'descripcion'];

    public function inquilino()
    {
        return $this->belongsTo(Inquilino::class);
    }
}
