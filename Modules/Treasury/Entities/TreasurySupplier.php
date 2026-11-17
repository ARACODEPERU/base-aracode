<?php

namespace Modules\Treasury\Entities;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Proveedor de pagos y compras. No existía en el proyecto (el módulo Purchases
 * es un esqueleto), así que vive dentro de Tesorería.
 */
class TreasurySupplier extends Model
{
    protected $fillable = [
        'doc_type',
        'doc_number',
        'name',
        'trade_name',
        'email',
        'phone',
        'address',
        'payment_terms_days',
        'bank_account_id',
        'notes',
        'status',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'status' => 'boolean',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TreasuryPaymentOrder::class, 'supplier_id');
    }

    public function recurringExpenses(): HasMany
    {
        return $this->hasMany(TreasuryRecurringExpense::class, 'supplier_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function documentLabel(): string
    {
        return trim(($this->doc_type ?: '') . ' ' . ($this->doc_number ?: ''));
    }
}
