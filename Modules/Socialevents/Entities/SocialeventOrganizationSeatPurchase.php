<?php

namespace Modules\Socialevents\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialeventOrganizationSeatPurchase extends Model
{
    protected $table = 'socialevent_organization_seat_purchases';

    protected $fillable = [
        'organization_id',
        'purchased_by',
        'external_reference',
        'idempotency_key',
        'provider_payment_id',
        'payment_method',
        'status',
        'quantity',
        'unit_price',
        'total',
        'credited_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'credited_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(SocialeventOrganization::class, 'organization_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }
}
