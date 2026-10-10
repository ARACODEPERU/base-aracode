<?php

namespace Modules\Academic\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asistencia institucional (portería): el paso del alumno por la puerta del
 * colegio, con la hora real de entrada y de salida. Una fila por alumno y día.
 *
 * Es distinta de la asistencia de aula: aca_school_attendances sigue siendo la
 * planilla mensual SIAGIE que califica el docente (una letra A/T/J/F por día).
 * Aquí solo se registra el paso por portería y, cuando la entrada del día
 * genera una marca de aula, su id queda en classroom_attendance_id para poder
 * rastrear el origen sin duplicar la fuente de verdad.
 */
class AcaSchoolGateAttendance extends Model
{
    use HasFactory;

    protected $table = 'aca_school_gate_attendances';

    /** Evento de portería: entrada al colegio. */
    public const EVENT_ENTRADA = 'in';

    /** Evento de portería: salida del colegio. */
    public const EVENT_SALIDA = 'out';

    /** Estados de la entrada institucional (los mismos de la asistencia de aula). */
    public const ENTRY_ASISTENCIA = AcaSchoolAttendance::STATUS_ASISTENCIA;
    public const ENTRY_TARDANZA = AcaSchoolAttendance::STATUS_TARDANZA;

    protected $fillable = [
        'school_id',
        'year_id',
        'section_id',
        'enrollment_id',
        'student_id',
        'attendance_date',
        'entry_at',
        'entry_status',
        'exit_at',
        'early_exit',
        'scans_count',
        'last_event',
        'observations',
        'classroom_attendance_id',
        'user_id_registers',
    ];

    protected $casts = [
        'attendance_date' => 'date:Y-m-d',
        'entry_at' => 'datetime',
        'exit_at' => 'datetime',
        'early_exit' => 'boolean',
        'scans_count' => 'integer',
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

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolEnrollment::class, 'enrollment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolStudent::class, 'student_id');
    }

    /** Marca de aula (SIAGIE) que origino la entrada de este dia. */
    public function classroomAttendance(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolAttendance::class, 'classroom_attendance_id');
    }

    /** Quien opero la porteria en el ultimo escaneo del dia. */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id_registers');
    }

    /** El alumno ya registro su entrada al colegio. */
    public function hasEntered(): bool
    {
        return $this->entry_at !== null;
    }

    /** El alumno entro y todavia no ha registrado su salida. */
    public function isInside(): bool
    {
        return $this->entry_at !== null && $this->exit_at === null;
    }

    public static function entryStatusLabels(): array
    {
        return [
            self::ENTRY_ASISTENCIA => 'Asistencia',
            self::ENTRY_TARDANZA => 'Tardanza',
        ];
    }

    /** Etiqueta de la entrada institucional (Asistencia / Tardanza / Sin entrada). */
    public function entryStatusLabel(): string
    {
        if (! $this->hasEntered()) {
            return 'Sin entrada';
        }

        return self::entryStatusLabels()[$this->entry_status] ?? 'Asistencia';
    }

    /** Hora en formato corto HH:MM para vistas y reportes. */
    public static function shortTime(?Carbon $at): ?string
    {
        return $at?->format('H:i');
    }
}
