<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioInsumo extends Model
{
    protected $table ='inventario_insumos';
    protected $fillable = ['inquilino_id','nombre','cantidad','unidad'];
    protected static function booted() 
    { 
        static::addGlobalScope('inquilino', function ($query) 
        { if (app()->has('inquilino_id')) 
            { $query->where('inquilino_id', app('inquilino_id')); 
            } }); 
    }
    public function inquilino() 
    { 
        return $this->belongsTo(Inquilino::class); 
    }
}
