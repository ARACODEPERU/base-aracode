<?php

namespace Modules\Academic\Entities;

use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AcaCapRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'status',
        'modality_id',
        'subscription_id',
        'date_start',
        'date_end',
        'unlimited',
        'certificate_date',
        'sale_note_id',
        'document_id',
        'arrival_source_id',
        'arrival_source_information',
        'payment_installments',
        'advancement',
        'amount_paid',
        'user_id_registers'
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id');
    }
    public function salenote(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_note_id');
    }
    public function document(): BelongsTo
    {
        return $this->belongsTo(SaleDocument::class, 'document_id');
    }
    public function registrador()
    {
        return $this->belongsTo(User::class, 'user_id_registers', 'id');
    }


    protected static function booted()
    {
        static::creating(function ($registration) {
            if (Auth::check()) {
                $registration->user_id_registers = Auth::id();
            }
        });
    }
}
