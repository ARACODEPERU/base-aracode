<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaSchoolYear extends Model
{
    use HasFactory;

    public const STATUS_ANNOUNCED = 'announced';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'school_id',
        'year',
        'status',
        'observations',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(AcaSchoolEnrollment::class, 'year_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
