<?php

namespace Modules\Treasury\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Dimensión de reportes: centro de costo o proyecto.
 */
class TreasuryCostCenter extends Model
{
    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(TreasuryTransaction::class);
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(TreasuryPaymentOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }
}
