<?php

namespace Modules\Treasury\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de una ocurrencia ejecutada. La clave única
 * (recurrente, periodo) es lo que impide cobrar dos veces el mismo mes.
 */
class TreasuryRecurringRun extends Model
{
    protected $fillable = [
        'treasury_recurring_expense_id',
        'period',
        'treasury_payment_order_id',
        'executed_at',
        'error',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    public function recurring(): BelongsTo
    {
        return $this->belongsTo(TreasuryRecurringExpense::class, 'treasury_recurring_expense_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TreasuryPaymentOrder::class, 'treasury_payment_order_id');
    }
}
