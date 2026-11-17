<?php

namespace Modules\Treasury\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comisiones recordadas para un par origen -> destino.
 * Se proponen automáticamente en la siguiente transferencia entre esas cuentas.
 */
class TreasuryTransferProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'from_fixed_fee',
        'from_percent_fee',
        'to_fixed_fee',
        'to_percent_fee',
        'own_account_itf_free',
    ];

    protected $casts = [
        'from_fixed_fee' => 'decimal:2',
        'from_percent_fee' => 'decimal:4',
        'to_fixed_fee' => 'decimal:2',
        'to_percent_fee' => 'decimal:4',
        'own_account_itf_free' => 'boolean',
    ];

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'to_account_id');
    }
}
