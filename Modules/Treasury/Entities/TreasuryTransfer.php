<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transferencia entre dos cuentas de tesorería (por ejemplo, de Mercado Pago a BCP).
 * Guarda el desglose de comisiones de origen y destino, y el ITF.
 */
class TreasuryTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'transfer_date',
        'amount',
        'from_fixed_fee',
        'from_percent_fee',
        'to_fixed_fee',
        'to_percent_fee',
        'itf_applies',
        'itf_amount',
        'net_received',
        'reference',
        'user_id',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
        'from_fixed_fee' => 'decimal:2',
        'from_percent_fee' => 'decimal:4',
        'to_fixed_fee' => 'decimal:2',
        'to_percent_fee' => 'decimal:4',
        'itf_applies' => 'boolean',
        'itf_amount' => 'decimal:2',
        'net_received' => 'decimal:2',
    ];

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'to_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
