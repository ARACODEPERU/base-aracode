<?php

namespace Modules\Treasury\Entities;

use App\Models\PettyCash;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Arqueo de una sesión de caja: lo contado contra lo que el sistema calcula.
 *
 * El arqueo no se edita ni se borra: si hubo un error, se registra otro
 * arqueo. La diferencia queda explicada en las notas.
 */
class TreasuryCashCount extends Model
{
    protected $fillable = [
        'petty_cash_id',
        'cutoff_date',
        'expected_amount',
        'counted_amount',
        'difference',
        'denominations',
        'notes',
        'closed_session',
        'created_by',
    ];

    protected $casts = [
        'cutoff_date' => 'date',
        'expected_amount' => 'decimal:2',
        'counted_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'denominations' => 'array',
        'closed_session' => 'boolean',
    ];

    public function pettyCash(): BelongsTo
    {
        return $this->belongsTo(PettyCash::class, 'petty_cash_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Hay faltante (negativo) o sobrante (positivo). */
    public function hasDifference(): bool
    {
        return abs((float) $this->difference) >= 0.01;
    }
}
