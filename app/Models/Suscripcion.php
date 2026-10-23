<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * El periodo pagado de una ganaderia.
 *
 * Mientras `fin` no haya pasado, la finca trabaja normal. Cuando pasa, entra en
 * solo lectura hasta que se apruebe un pago: los datos NO se borran ni se
 * ocultan, para que el cliente pueda sacarlos.
 *
 * @property \Illuminate\Support\Carbon $inicio
 * @property \Illuminate\Support\Carbon $fin
 * @property bool $es_prueba
 */
class Suscripcion extends Model
{
    use HasFactory;

    protected $table = 'suscripciones';

    protected $fillable = [
        'inquilino_id',
        'plan_id',
        'inicio',
        'fin',
        'es_prueba',
    ];

    protected function casts(): array
    {
        return [
            'inicio' => 'date',
            'fin' => 'date',
            'es_prueba' => 'boolean',
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

    /** @return HasMany<Pago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'suscripcion_id');
    }

    public function estaVigente(): bool
    {
        return $this->fin->endOfDay()->isFuture() || $this->fin->isToday();
    }

    public function diasRestantes(): int
    {
        return max(0, (int) now()->startOfDay()->diffInDays($this->fin->startOfDay(), false));
    }

    /** ¿Está a punto de vencer? (para avisar en el panel) */
    public function estaPorVencer(int $dias = 5): bool
    {
        return $this->estaVigente() && $this->diasRestantes() <= $dias;
    }
}
