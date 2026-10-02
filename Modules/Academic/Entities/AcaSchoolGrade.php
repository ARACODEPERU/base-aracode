<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaSchoolGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'level_id',
        'name',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolLevel::class, 'level_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AcaSchoolSection::class, 'grade_id')->orderBy('name');
    }
}
