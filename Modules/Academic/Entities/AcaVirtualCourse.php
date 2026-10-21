<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaVirtualCourse extends Model
{
    protected $table = 'aca_virtual_courses';

    protected $fillable = [
        'school_id',
        'year_id',
        'section_id',
        'area_id',
        'teacher_person_id',
        'name',
        'description',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function year(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolYear::class, 'year_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolSection::class, 'section_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolArea::class, 'area_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Person::class, 'teacher_person_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(AcaVirtualUnit::class, 'course_id')->orderBy('sort_order');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(AcaVirtualResource::class, 'course_id')->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AcaVirtualActivity::class, 'course_id')->orderBy('sort_order');
    }
}
