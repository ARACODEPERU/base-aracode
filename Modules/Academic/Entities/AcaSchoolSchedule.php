<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloque del horario escolar: una seccion, un area curricular, el docente que
 * la dicta, un dia de la semana y una hora de inicio y fin.
 *
 * Con esta tabla se responden las tres preguntas del horario: que cursos lleva
 * cada alumno (los bloques de su seccion), quien se los dicta
 * (teacher_person_id) y la jornada de la seccion (la suma de sus bloques).
 */
class AcaSchoolSchedule extends Model
{
    protected $table = 'aca_school_schedules';

    /** Dias de la semana usados en el horario (1=lunes ... 7=domingo). */
    public const WEEKDAYS = [1, 2, 3, 4, 5, 6, 7];

    /** Dias lectivos habituales: lunes a viernes. */
    public const WEEKDAYS_SCHOOL = [1, 2, 3, 4, 5];

    protected $fillable = [
        'school_id',
        'year_id',
        'section_id',
        'area_id',
        'teacher_person_id',
        'weekday',
        'start_time',
        'end_time',
        'room',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'sort_order' => 'integer',
        'status' => 'boolean',
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

    /** Etiquetas de los dias de la semana (clave 1..7). */
    public static function weekdayLabels(): array
    {
        return [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
    }

    /** Nombre corto del dia (Lun, Mar, ...). */
    public static function weekdayShortLabels(): array
    {
        return [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mié',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sáb',
            7 => 'Dom',
        ];
    }

    public static function weekdayLabel(int $weekday): string
    {
        return self::weekdayLabels()[$weekday] ?? (string) $weekday;
    }

    /** Hora corta HH:MM del bloque. */
    public function startLabel(): ?string
    {
        return AcaSchoolJourney::shortTime($this->start_time);
    }

    public function endLabel(): ?string
    {
        return AcaSchoolJourney::shortTime($this->end_time);
    }
}
