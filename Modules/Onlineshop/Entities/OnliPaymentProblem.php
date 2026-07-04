<?php

namespace Modules\Onlineshop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnliPaymentProblem extends Model
{
    protected $table = 'onli_payment_problems';

    protected $fillable = [
        'sale_id',
        'phone_country',
        'phone',
        'email',
        'clie_full_name',
        'amount',
        'payment_method',
        'courses_info',
        'payment_data',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'courses_info' => 'array',
        'payment_data' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(OnliSale::class, 'sale_id');
    }
}
