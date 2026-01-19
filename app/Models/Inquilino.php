<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inquilino extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'plan_suscripcion',
        'fecha_registro',
    ];

    // Relación: Un inquilino tiene muchos usuarios
    public function usuarios()
    {
        return $this->hasMany(User::class, 'inquilino_id'); // 👈 corregido
    }

    // Relación: Un inquilino tiene muchos animales
    public function animales()
    {
        return $this->hasMany(Animal::class, 'inquilino_id');
    }

    // Relación: Un inquilino tiene muchas compras
    public function compras()
    {
        return $this->hasMany(Compra::class, 'inquilino_id');
    }

    // Relación: Un inquilino tiene muchas ventas
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'inquilino_id');
    }

    // Relación: Un inquilino tiene muchos insumos
    public function inventarioInsumos()
    {
        return $this->hasMany(InventarioInsumo::class, 'inquilino_id');
    }

    // Relación: Un inquilino tiene muchos reportes
    public function reportes()
    {
        return $this->hasMany(Reporte::class, 'inquilino_id');
    }
}