<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaSchoolStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'person_id',
        'student_code',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Person::class, 'person_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(AcaSchoolEnrollment::class, 'student_id');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(AcaSchoolStudentGuardian::class, 'student_id');
    }

    /**
     * Genera el siguiente codigo interno de alumno para el colegio:
     * prefijo ANIO + secuencial (ej: 2026-0001).
     */
    public static function nextStudentCode(int $schoolId): string
    {
        $year = now()->format('Y');
        $count = self::where('school_id', $schoolId)
            ->where('student_code', 'like', $year.'-%')
            ->count();

        return $year.'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
