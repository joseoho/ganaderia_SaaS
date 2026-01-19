<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table ='ventas';
    protected $fillable = ['inquilino_id','fecha','cliente','descripcion','monto_total']; 
    protected static function booted() 
    { static::addGlobalScope('inquilino', function ($query) 
        { if (app()->has('inquilino_id')) 
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
