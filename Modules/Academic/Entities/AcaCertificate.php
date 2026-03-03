<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'registration_id',
        'course_id',
        'module_id',
        'image',
        'content'
    ];

    protected static function newFactory()
    {
        return \Modules\Academic\Database\factories\AcaCertificateFactory::new();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcaModule::class, 'module_id');
    }
}
