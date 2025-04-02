<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\factories\AcaStudentSubscriptionFactory;

class AcaStudentSubscription extends Model
{
    use HasFactory;
    public $incrementing = false; // Deshabilitar incremento automático
    protected $primaryKey = ['student_id', 'subscription_id'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'subscription_id',
        'date_start',
        'date_end',
        'status',
        'notes',
        'renewals',
        'registration_user_id',
        'onli_sale_id',
        'xdocument_id',
        'amount_paid'
    ];

    protected static function newFactory(): AcaStudentSubscriptionFactory
    {
        //return AcaStudentSubscriptionFactory::new();
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AcaSubscriptionType::class, 'subscription_id');
    }
}
