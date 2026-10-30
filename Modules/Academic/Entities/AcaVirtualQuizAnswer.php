<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaVirtualQuizAnswer extends Model
{
    protected $table = 'aca_virtual_quiz_answers';

    protected $fillable = [
        'attempt_id',
        'question_id',
        'option_ids',
        'answer_text',
        'points_earned',
        'is_correct',
        'manually_graded',
        'graded_by_person_id',
    ];

    protected $casts = [
        'option_ids' => 'array',
        'points_earned' => 'decimal:2',
        'is_correct' => 'boolean',
        'manually_graded' => 'boolean',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualQuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualActivityQuestion::class, 'question_id');
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Person::class, 'graded_by_person_id');
    }
}
