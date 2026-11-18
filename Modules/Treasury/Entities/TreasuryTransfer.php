<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transferencia entre dos cuentas de tesorería (por ejemplo, de Mercado Pago a BCP).
 * Guarda el desglose de comisiones (sin IGV entre cuentas propias), el ITF y, si las monedas
 * difieren, el tipo de cambio y el monto realmente acreditado en el destino.
 * Cada gasto se registra además como egreso propio en el libro.
 */
class TreasuryTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_account_id',
        'to_account_id',
        'transfer_date',
        'amount',
        'from_currency',
        'to_currency',
        'exchange_rate',
        'amount_destination',
        'from_fixed_fee',
        'from_percent_fee',
        'to_fixed_fee',
        'to_percent_fee',
        'from_fee_amount',
        'from_fee_igv',
        'to_fee_amount',
        'to_fee_igv',
        'itf_applies',
        'itf_amount',
        'net_received',
        'reference',
        'user_id',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'amount_destination' => 'decimal:2',
        'from_fixed_fee' => 'decimal:2',
        'from_percent_fee' => 'decimal:4',
        'to_fixed_fee' => 'decimal:2',
        'to_percent_fee' => 'decimal:4',
        'from_fee_amount' => 'decimal:2',
        'from_fee_igv' => 'decimal:2',
        'to_fee_amount' => 'decimal:2',
        'to_fee_igv' => 'decimal:2',
        'itf_applies' => 'boolean',
        'itf_amount' => 'decimal:2',
        'net_received' => 'decimal:2',
        'voided_at' => 'datetime',
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

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
