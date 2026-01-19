<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    protected $table ='reportes';
    protected $fillable = ['inquilino_id','tipo','fecha_generado','archivo_url']; 
    protected static function booted() 
    { static::addGlobalScope('inquilino', function ($query) 
        { if (app()->has('inquilino_id')) 
            {
                 $query->where('inquilino_id', app('inquilino_id')); 
            } });
         } 
            public function inquilino() 
            { 
                return $this->belongsTo(Inquilino::class);
             }
}
