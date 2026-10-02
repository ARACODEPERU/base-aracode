<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaSchool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'modular_code',
        'address',
        'phone',
        'email',
        'logo',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'status' => 'boolean',
    ];

    public function years(): HasMany
    {
        return $this->hasMany(AcaSchoolYear::class, 'school_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(AcaSchoolLevel::class, 'school_id')->orderBy('sort_order');
    }

    public function students(): HasMany
    {
        return $this->hasMany(AcaSchoolStudent::class, 'school_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(AcaSchoolEnrollment::class, 'school_id');
    }

    /**
     * Colegio por defecto para el modo mono-colegio (el primero marcado,
     * o el primero que exista).
     */
    public static function defaultSchool(): ?self
    {
        return self::where('is_default', true)->first()
            ?? self::orderBy('id')->first();
    }
}
