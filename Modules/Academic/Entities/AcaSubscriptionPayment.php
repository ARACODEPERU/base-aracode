<?php

namespace Modules\Academic\Entities;

use App\Models\SaleDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\factories\AcaSubscriptionPaymentFactory;

class AcaSubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subscription_id',
        'document_id',
        'date_start',
        'date_end',
        'amount',
        'authorized_payment_id',
        'payment_id',
        'preapproval_id',
        'status',
        'source',
        'paid_at',
        'mercadopago_event_id',
        'renewal_notified_at',
        'is_test',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'renewal_notified_at' => 'datetime',
        'is_test' => 'boolean',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AcaSubscriptionType::class, 'subscription_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id', 'id');
    }
    public function document(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class, 'document_id');
    }

    public function preapproval(): BelongsTo
    {
        return $this->belongsTo(AcaSubscriptionPreapproval::class, 'preapproval_id', 'preapproval_id');
    }
}
