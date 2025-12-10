<?php

namespace Modules\Sales\Entities;

use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Sales\Database\factories\SalePaymentScheduleFactory;

class SalePaymentSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sale_id',
        'installment_number',
        'payment_date',
        'amount_to_pay',
        'amount_paid',
        'remaining_amount',
        'document_id',
        'is_paid',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class, 'document_id');
    }
    public function destinations(): HasMany
    {
        return $this->hasMany(SalePaymentScheduleDestination::class, 'schedule_id', 'id');
    }
}
