<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jornada escolar del colegio: hora oficial de entrada y salida por nivel y
 * turno (level_id nulo = aplica al colegio completo).
 *
 * Se usa para delimitar la grilla del horario (los bloques de clase caen dentro
 * de la jornada) y, mas adelante, para saber si una salida de porteria es
 * anticipada y exige motivo.
 */
class AcaSchoolJourney extends Model
{
    protected $table = 'aca_school_journeys';

    protected $fillable = [
        'school_id',
        'level_id',
        'shift',
        'entry_time',
        'exit_time',
        'tolerance_minutes',
        'recess_start',
        'recess_end',
        'status',
    ];

    protected $casts = [
        'tolerance_minutes' => 'integer',
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

    /** Etiqueta del turno (Mañana, Tarde, Noche, Jornada completa). */
    public function shiftLabel(): string
    {
        return AcaSchoolSection::shiftLabels()[$this->shift] ?? ucfirst($this->shift);
    }

    /** Hora en formato corto HH:MM (para vistas y PDF). */
    public static function shortTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return substr($time, 0, 5);
    }

    /**
     * Jornada que aplica a una seccion: primero la de su nivel y turno, luego
     * la generica del nivel (turno "jornada"), luego la del colegio para ese
     * turno y por ultimo la del colegio con turno "jornada".
     */
    public static function forSection(?AcaSchoolSection $section): ?self
    {
        if (! $section) {
            return null;
        }

        $schoolId = $section->school_id;
        $levelId = $section->grade?->level_id;

        $candidates = [
            [$schoolId, $levelId, $section->shift],
            [$schoolId, $levelId, AcaSchoolSection::SHIFT_JORNADA],
            [$schoolId, null, $section->shift],
            [$schoolId, null, AcaSchoolSection::SHIFT_JORNADA],
        ];

        foreach ($candidates as [$school, $level, $shift]) {
            $journey = self::where('school_id', $school)
                ->where('level_id', $level)
                ->where('shift', $shift)
                ->where('status', true)
                ->first();

            if ($journey) {
                return $journey;
            }
        }

        return null;
    }
}
