<?php

namespace Modules\Academic\Entities;

use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'document_id'
    ];

    protected static function newFactory()
    {
        return \Modules\Academic\Database\factories\AcaCapRegistrationFactory::new();
    }

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
}
