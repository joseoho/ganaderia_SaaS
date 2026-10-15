<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquilino extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'plan_suscripcion',
        'fecha_registro',
    ];

    // Relación: Un inquilino tiene muchos usuarios
    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasmany(User::class, 'inquilino_id'); // 👈 corregido
    }

    // Relación: Un inquilino tiene muchos animales
    /** @return HasMany<Animal, $this> */
    public function animales(): HasMany
    {
        return $this->hasmany(Animal::class, 'inquilino_id');
    }

    // Relación: Un inquilino tiene muchas compras
    /** @return HasMany<Compra, $this> */
    public function compras(): HasMany
    {
        return $this->hasmany(Compra::class, 'inquilino_id');
    }

    // Relación: Un inquilino tiene muchas ventas
    /** @return HasMany<Venta, $this> */
    public function ventas(): HasMany
    {
        return $this->hasmany(Venta::class, 'inquilino_id');
    }

    // Relación: Un inquilino tiene muchos insumos
    /** @return HasMany<InventarioInsumo, $this> */
    public function inventarioInsumos(): HasMany
    {
        return $this->hasmany(InventarioInsumo::class, 'inquilino_id');
    }

    // Relación: Un inquilino tiene muchos reportes
    /** @return HasMany<Reporte, $this> */
    public function reportes(): HasMany
    {
        return $this->hasmany(Reporte::class, 'inquilino_id');
    }

    /**
     * Tipos de dato, derivados del esquema real (Fase 4).
     *
     * Sin esto Eloquent devuelve las fechas como cadena, lo que hace
     * fragiles las comparaciones y el formateo. Ojo: las vistas que pintan
     * una fecha dentro de un <input type="date"> deben formatearla con
     * ->format('Y-m-d'), o el navegador mostrara el campo vacio.
     */
    protected function casts(): array
    {
        return [
            'fecha_registro' => 'date',
        ];
    }
}
