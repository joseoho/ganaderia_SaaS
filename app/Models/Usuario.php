<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table ='usuarios';
    protected $fillable = 
    ['inquilino_id','nombre','email','rol','contraseña_hash'];
     protected static function booted() 
     { static::addGlobalScope('inquilino', function ($query) 
        { if (app()->has('inquilino_id')) 
            { $query->where('inquilino_id', app('inquilino_id')); 
            } 
        }); 
    } public function inquilino() 
    { 
        return $this->belongsTo(Inquilino::class);
     } 
     public function notificaciones() 
     { 
        return $this->hasMany(Notificacion::class); 
    }
}
