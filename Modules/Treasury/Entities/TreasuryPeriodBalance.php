<?php

namespace Modules\Treasury\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Foto inmutable del saldo de una cuenta al cerrar el periodo. Es la prueba
 * de lo que se cerró: no se recalcula ni se actualiza.
 */
class TreasuryPeriodBalance extends Model
{
    protected $fillable = [
        'period_id',
        'treasury_account_id',
        'currency',
        'opening_balance',
        'incomes',
        'expenses',
        'closing_balance',
        'tx_count',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'incomes' => 'decimal:2',
        'expenses' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'tx_count' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(TreasuryPeriod::class, 'period_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }
}
