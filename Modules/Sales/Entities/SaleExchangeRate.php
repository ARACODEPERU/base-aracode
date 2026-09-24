<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class SaleExchangeRate extends Model
{
    protected $table = 'sales_exchange_rates';

    protected $fillable = [
        'currency_code',
        'rate_date',
        'purchase_rate',
        'sale_rate',
        'source',
        'fetched_by',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'purchase_rate' => 'decimal:4',
        'sale_rate' => 'decimal:4',
    ];

    /**
     * Usuario que disparo la consulta manual (null = comando programado).
     */
    public function fetchedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'fetched_by');
    }
}
