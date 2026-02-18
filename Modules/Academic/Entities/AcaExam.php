<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\factories\AcaExamFactory;

class AcaExam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'course_id',
        'module_id',
        'theme_id',
        'content_id',
        'description',
        'date_start',
        'date_end',
        'status',
        'duration_minutes',
        'file_resolved_name',
        'file_resolved_path'
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(AcaExamQuestion::class, 'exam_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcaModule::class, 'module_id');
    }

    public function student_exams(): HasMany
    {
        return $this->hasMany(AcaStudentExam::class, 'exam_id');
    }
}
