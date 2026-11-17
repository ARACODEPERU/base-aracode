<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partida del extracto bancario que todavía no existe en el libro
 * (comisión, ITF, interés, débito automático). Explica la diferencia antes
 * de cerrar la conciliación.
 */
class TreasuryReconciliationItem extends Model
{
    public const TYPE_INCOME = 'income';
    public const TYPE_EXPENSE = 'expense';

    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'treasury_account_id',
        'treasury_reconciliation_id',
        'cutoff_date',
        'description',
        'amount',
        'type',
        'status',
        'resolved_transaction_id',
        'created_by',
    ];

    protected $casts = [
        'cutoff_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(TreasuryReconciliation::class, 'treasury_reconciliation_id');
    }

    public function resolvedTransaction(): BelongsTo
    {
        return $this->belongsTo(TreasuryTransaction::class, 'resolved_transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
