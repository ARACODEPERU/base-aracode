<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaStudentGradeDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id',
        'module_id',
        'exam_score',
        'attendance_score',
        'participation_score',
        'average',
        'module_approved',
        'observations'
    ];

    protected $casts = [
        'exam_score' => 'decimal:2',
        'attendance_score' => 'decimal:2',
        'participation_score' => 'decimal:2',
        'average' => 'decimal:2',
        'module_approved' => 'boolean',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(AcaStudentGrade::class, 'grade_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AcaModule::class, 'module_id');
    }
}
