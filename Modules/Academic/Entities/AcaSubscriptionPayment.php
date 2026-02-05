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
        'amount'
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
}
