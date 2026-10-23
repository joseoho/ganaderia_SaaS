<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un pago subido por el cliente (comprobante) y su revisión.
 *
 * El flujo es el de las apps de servicios públicos: el cliente paga por Pago
 * Móvil, transferencia, Zelle o USDT, sube la imagen, y el operador aprueba (se
 * extiende la suscripción) o rechaza con un motivo.
 *
 * `referencia` es única: es la única defensa real contra subir dos veces el
 * mismo comprobante.
 */
class Pago extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_RECHAZADO = 'rechazado';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_APROBADO => 'Aprobado',
        self::ESTADO_RECHAZADO => 'Rechazado',
    ];

    public const METODOS = [
        'pago_movil' => 'Pago Móvil',
        'transferencia' => 'Transferencia',
        'zelle' => 'Zelle',
        'usdt' => 'USDT',
        'efectivo' => 'Efectivo',
        'otro' => 'Otro',
    ];

    protected $table = 'pagos';

    protected $fillable = [
        'inquilino_id',
        'suscripcion_id',
        'plan_id',
        'monto',
        'moneda',
        'tasa',
        'metodo',
        'referencia',
        'comprobante',
        'fecha_pago',
        'estado',
        'notas',
        'motivo_rechazo',
        'revisado_por',
        'revisado_en',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'tasa' => 'decimal:4',
            'fecha_pago' => 'date',
            'revisado_en' => 'datetime',
        ];
    }

    /** @return BelongsTo<Inquilino, $this> */
    public function inquilino(): BelongsTo
    {
        return $this->belongsTo(Inquilino::class, 'inquilino_id');
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /** @return BelongsTo<User, $this> */
    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /** Monto equivalente en dólares, usando la tasa guardada en el propio pago. */
    public function montoEnUsd(): float
    {
        if ($this->moneda === 'USD' || ! $this->tasa || (float) $this->tasa <= 0) {
            return (float) $this->monto;
        }

        return round((float) $this->monto / (float) $this->tasa, 2);
    }
}
