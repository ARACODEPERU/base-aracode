<?php

namespace Modules\Academic\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaSchoolStudentGuardian extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'guardian_person_id',
        'relationship',
        'is_primary',
        'status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'status' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolStudent::class, 'student_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'guardian_person_id');
    }

    /**
     * Parentescos disponibles para apoderados (valores en BD, sin tildes).
     */
    public static function relationshipLabels(): array
    {
        return [
            'madre' => 'Madre',
            'padre' => 'Padre',
            'abuelo' => 'Abuelo',
            'abuela' => 'Abuela',
            'tio' => 'Tío',
            'tia' => 'Tía',
            'hermano' => 'Hermano',
            'hermana' => 'Hermana',
            'primo' => 'Primo',
            'prima' => 'Prima',
            'padrastro' => 'Padrastro',
            'madrasta' => 'Madrasta',
            'tutor_legal' => 'Tutor legal',
            'otro' => 'Otro',
        ];
    }

    public function relationshipLabel(): string
    {
        return self::relationshipLabels()[$this->relationship] ?? $this->relationship;
    }
}
