<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
   protected $table ='compras';
    protected $fillable = ['inquilino_id','fecha','proveedor','descripcion' ,'monto_total']; 
   
   protected static function booted() 
   { 
    static::addGlobalScope('inquilino', function ($query) 
    { 
        if (app()->has('inquilino_id')) 
        { 
            $query->where('inquilino_id', app('inquilino_id')); 
        } 
    }); 
    } 
        public function inquilino() 
        { 
            return $this->belongsTo(Inquilino::class); 
        }
}
