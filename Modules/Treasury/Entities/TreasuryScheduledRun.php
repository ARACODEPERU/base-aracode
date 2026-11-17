<?php

namespace Modules\Treasury\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de un cobro programado ya ejecutado para una cuenta y un periodo (AAAA-MM).
 */
class TreasuryScheduledRun extends Model
{
    protected $fillable = [
        'treasury_account_id',
        'period',
        'treasury_transaction_id',
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'treasury_transaction_id');
    }
}
