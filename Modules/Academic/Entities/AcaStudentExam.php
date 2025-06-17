<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\factories\AcaStudentExamFactory;

class AcaStudentExam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'exam_id',
        'student_id',
        'date_start',
        'date_End',
        'punctuation',
        'status',
        'details'
    ];

    public function getDetailsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class,'student_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(AcaExam::class,'exam_id');
    }
}
