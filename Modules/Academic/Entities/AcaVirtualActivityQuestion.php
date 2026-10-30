<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaVirtualActivityQuestion extends Model
{
    public const TYPE_SINGLE = 'single_choice';

    public const TYPE_MULTIPLE = 'multiple_choice';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPE_SHORT = 'short_answer';

    /** Tipos que se responden eligiendo alternativas. */
    public const CHOICE_TYPES = [self::TYPE_SINGLE, self::TYPE_TRUE_FALSE, self::TYPE_MULTIPLE];

    protected $table = 'aca_virtual_activity_questions';

    protected $fillable = [
        'activity_id',
        'type',
        'question',
        'points',
        'answer_text',
        'sort_order',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualActivity::class, 'activity_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(AcaVirtualActivityOption::class, 'question_id')->orderBy('sort_order');
    }

    public function isChoice(): bool
    {
        return in_array($this->type, self::CHOICE_TYPES, true);
    }
}
