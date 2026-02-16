<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcaModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'position',
        'description',
        'teacher_id',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(AcaCourse::class, 'course_id');
    }

    public function themes(): HasMany
    {
        return $this->hasMany(AcaTheme::class, 'module_id');
    }
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(AcaTeacher::class, 'teacher_id');
    }

    public function exam(): HasOne
    {
        return $this->hasOne(AcaExam::class, 'module_id');
    }
}
