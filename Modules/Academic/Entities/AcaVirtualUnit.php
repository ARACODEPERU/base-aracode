<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaVirtualUnit extends Model
{
    protected $table = 'aca_virtual_units';

    protected $fillable = [
        'course_id',
        'title',
        'purpose',
        'significant_situation',
        'competencies',
        'capabilities',
        'performances',
        'duration_hours',
        'start_date',
        'end_date',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualCourse::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(AcaVirtualLesson::class, 'unit_id')->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(AcaVirtualResource::class, 'unit_id')->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AcaVirtualActivity::class, 'unit_id')->orderBy('sort_order');
    }
}
