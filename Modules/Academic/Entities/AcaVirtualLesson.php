<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaVirtualLesson extends Model
{
    protected $table = 'aca_virtual_lessons';

    protected $fillable = [
        'unit_id',
        'title',
        'purpose',
        'intro_activity',
        'development',
        'closure',
        'metacognition',
        'evaluation',
        'duration_minutes',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualUnit::class, 'unit_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(AcaVirtualResource::class, 'lesson_id')->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AcaVirtualActivity::class, 'lesson_id')->orderBy('sort_order');
    }
}
