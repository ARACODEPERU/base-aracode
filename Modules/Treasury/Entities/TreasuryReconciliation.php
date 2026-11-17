<?php

namespace Modules\Treasury\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Acta de conciliación bancaria: congela un día por cuenta, guarda quién la
 * hizo y la huella de las líneas incluidas. No se modifica después del cierre.
 */
class TreasuryReconciliation extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'treasury_account_id',
        'cutoff_date',
        'statement_balance',
        'system_balance',
        'difference',
        'currency',
        'status',
        'snapshot_hash',
        'notes',
        'created_by',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'cutoff_date' => 'date',
        'statement_balance' => 'decimal:2',
        'system_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'treasury_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreasuryReconciliationItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class, 'treasury_reconciliation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
