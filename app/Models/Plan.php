<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un plan de venta: precio, duracion del periodo y limites.
 *
 * El precio esta en USD (es la moneda con la que se cobra en Venezuela); el
 * cliente puede pagar en bolivares y la tasa se guarda en el pago, no aqui.
 */
class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'precio_usd',
        'dias',
        'limite_animales',
        'limite_usuarios',
        'activo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'precio_usd' => 'decimal:2',
            'dias' => 'integer',
            'limite_animales' => 'integer',
            'limite_usuarios' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /** @return HasMany<Suscripcion, $this> */
    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'plan_id');
    }

    public function precioConMoneda(): string
    {
        return 'US$ '.number_format((float) $this->precio_usd, 2);
    }

    public function limiteAnimalesTexto(): string
    {
        return $this->limite_animales === null ? 'sin limite' : $this->limite_animales.' animales';
    }

    public function limiteUsuariosTexto(): string
    {
        return $this->limite_usuarios === null ? 'sin limite' : $this->limite_usuarios.' usuarios';
    }
}
