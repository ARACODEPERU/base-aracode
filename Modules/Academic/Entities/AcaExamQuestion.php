<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\factories\AcaExamQuestionFactory;

class AcaExamQuestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'exam_id',
        'description',
        'score',
        'type_answers',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(AcaExamAnswer::class, 'question_id');
    }
}
