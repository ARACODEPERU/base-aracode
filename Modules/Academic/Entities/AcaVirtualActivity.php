<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaVirtualActivity extends Model
{
    protected $table = 'aca_virtual_activities';

    protected $fillable = [
        'course_id',
        'unit_id',
        'lesson_id',
        'type',
        'title',
        'instructions',
        'open_at',
        'close_at',
        'allow_late',
        'max_score',
        'grading_scale',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'open_at' => 'datetime',
        'close_at' => 'datetime',
        'allow_late' => 'boolean',
        'max_score' => 'decimal:2',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualCourse::class, 'course_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualUnit::class, 'unit_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualLesson::class, 'lesson_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AcaVirtualSubmission::class, 'activity_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AcaVirtualActivityFile::class, 'activity_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AcaVirtualActivityQuestion::class, 'activity_id')->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AcaVirtualQuizAttempt::class, 'activity_id');
    }

    /** Indica si la actividad es una evaluación configurable en linea. */
    public function isOnlineQuiz(): bool
    {
        return $this->type === 'quiz' && $this->questions->isNotEmpty();
    }
}
