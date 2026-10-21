<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaVirtualSubmission extends Model
{
    protected $table = 'aca_virtual_submissions';

    protected $fillable = [
        'activity_id',
        'student_id',
        'content',
        'path',
        'mime_type',
        'size_kb',
        'status',
        'score',
        'grade_text',
        'feedback',
        'submitted_at',
        'graded_at',
        'graded_by_person_id',
    ];

    protected $casts = [
        'size_kb' => 'integer',
        'score' => 'decimal:2',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualActivity::class, 'activity_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolStudent::class, 'student_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Person::class, 'graded_by_person_id');
    }
}
