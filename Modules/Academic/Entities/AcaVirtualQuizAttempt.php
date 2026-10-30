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
        'attempt_number',
        'status',
        'started_at',
        'expires_at',
        'question_order',
        'option_order',
        'finished_at',
        'points_earned',
        'points_total',
        'score',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'question_order' => 'array',
        'option_order' => 'array',
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

    /** Indica si el tiempo del intento ya terminó. */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS
            && $this->expires_at !== null
            && now()->greaterThanOrEqualTo($this->expires_at);
    }
}
