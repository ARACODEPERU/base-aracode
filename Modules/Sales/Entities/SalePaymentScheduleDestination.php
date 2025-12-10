<?php

namespace Modules\Sales\Entities;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;


class SalePaymentScheduleDestination extends Model
{
    use HasFactory;

    protected $table = 'sale_payment_schedule_destinations';

    protected $fillable = [
        'method_id',
        'date_payment',
        'reference',
        'amount',
        'proof',
        'schedule_id',
        'document_id',
        'sale_id',
        'status',
        'user_id',
        'note',
    ];

    public function method(): HasOne
    {
        return $this->hasOne(PaymentMethod::class, 'id' , 'method_id');
    }
}
