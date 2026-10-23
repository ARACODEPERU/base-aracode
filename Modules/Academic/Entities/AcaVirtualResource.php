<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaVirtualResource extends Model
{
    protected $table = 'aca_virtual_resources';

    protected $fillable = [
        'course_id',
        'unit_id',
        'lesson_id',
        'type',
        'title',
        'url',
        'content',
        'path',
        'mime_type',
        'size_kb',
        'visibility',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'size_kb' => 'integer',
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
}
