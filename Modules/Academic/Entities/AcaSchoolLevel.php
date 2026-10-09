<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaSchoolLevel extends Model
{
    use HasFactory;

    public const INICIAL = 'inicial';
    public const PRIMARIA = 'primaria';
    public const SECUNDARIA = 'secundaria';

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'sort_order',
        'status',
        'scale',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Escala de evaluacion del nivel: la configurada por el colegio o, si no
     * definio ninguna, el default del MINEDU (Inicial literal AD/A/B/C y
     * Primaria/Secundaria vigesimal 0-20).
     */
    public function evaluationScale(): string
    {
        if (in_array($this->scale, ['literal', 'vigesimal'], true)) {
            return $this->scale;
        }

        return $this->code === self::INICIAL
            ? AcaSchoolGradeCompetency::SCALE_LITERAL
            : AcaSchoolGradeCompetency::SCALE_VIGESIMAL;
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(AcaSchool::class, 'school_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(AcaSchoolGrade::class, 'level_id')->orderBy('sort_order');
    }

    /**
     * Jornadas del nivel: hora oficial de entrada y salida por turno.
     */
    public function journeys(): HasMany
    {
        return $this->hasMany(AcaSchoolJourney::class, 'level_id');
    }

    /**
     * Estructura estandar de la educacion basica regular en Peru:
     * Inicial (3, 4, 5 anios), Primaria (1°-6°) y Secundaria (1°-5°).
     * Se usa al precargar la estructura de un colegio.
     */
    public static function standardPeruStructure(): array
    {
        return [
            [
                'code' => self::INICIAL,
                'name' => 'Inicial',
                'grades' => ['3 años', '4 años', '5 años'],
            ],
            [
                'code' => self::PRIMARIA,
                'name' => 'Primaria',
                'grades' => ['1°', '2°', '3°', '4°', '5°', '6°'],
            ],
            [
                'code' => self::SECUNDARIA,
                'name' => 'Secundaria',
                'grades' => ['1°', '2°', '3°', '4°', '5°'],
            ],
        ];
    }
}
