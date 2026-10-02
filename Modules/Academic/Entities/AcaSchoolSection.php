<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaSchoolSection extends Model
{
    use HasFactory;

    public const SHIFT_MANANA = 'manana';
    public const SHIFT_TARDE = 'tarde';
    public const SHIFT_NOCHE = 'noche';
    public const SHIFT_JORNADA = 'jornada';

    protected $fillable = [
        'school_id',
        'grade_id',
        'name',
        'capacity',
        'shift',
        'tutor_person_id',
        'auxiliary_person_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolGrade::class, 'grade_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Person::class, 'tutor_person_id');
    }

    public function auxiliary(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Person::class, 'auxiliary_person_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(AcaSchoolEnrollment::class, 'section_id');
    }

    /**
     * Matriculas que ocupan vacante en la seccion (status activo).
     */
    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(AcaSchoolEnrollment::class, 'section_id')
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO);
    }

    public static function shiftLabels(): array
    {
        return [
            self::SHIFT_MANANA => 'Mañana',
            self::SHIFT_TARDE => 'Tarde',
            self::SHIFT_NOCHE => 'Noche',
            self::SHIFT_JORNADA => 'Jornada completa',
        ];
    }
}
