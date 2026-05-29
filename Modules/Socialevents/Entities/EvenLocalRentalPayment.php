<?php

namespace Modules\Socialevents\Entities;

use App\Models\Sale;
use App\Models\User;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvenLocalRentalPayment extends Model
{
    protected $table = 'even_local_rental_payments';

    protected $fillable = [
        'rental_id',
        'amount',
        'payment_method_id',
        'reference',
        'sale_id',
        'registered_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(EvenLocalRental::class, 'rental_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
