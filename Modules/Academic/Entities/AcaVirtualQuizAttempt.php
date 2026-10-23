<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaVirtualQuizAttempt extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_FINISHED = 'finished';

    protected $table = 'aca_virtual_quiz_attempts';

    protected $fillable = [
        'activity_id',
        'student_id',
        'status',
        'started_at',
        'finished_at',
        'points_earned',
        'points_total',
        'score',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'points_earned' => 'decimal:2',
        'points_total' => 'decimal:2',
        'score' => 'decimal:2',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualActivity::class, 'activity_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolStudent::class, 'student_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AcaVirtualQuizAnswer::class, 'attempt_id');
    }
}
