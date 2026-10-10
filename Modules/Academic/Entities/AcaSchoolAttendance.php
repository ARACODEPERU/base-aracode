<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaSchoolAttendance extends Model
{
    use HasFactory;

    // Estados del registro de asistencia (terminologia SIAGIE / MINEDU)
    public const STATUS_ASISTENCIA = 'A';
    public const STATUS_TARDANZA = 'T';
    public const STATUS_JUSTIFICADA = 'J';
    public const STATUS_FALTA = 'F';

    public const STATUSES = [
        self::STATUS_ASISTENCIA,
        self::STATUS_TARDANZA,
        self::STATUS_JUSTIFICADA,
        self::STATUS_FALTA,
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ASISTENCIA => 'Asistencia',
            self::STATUS_TARDANZA => 'Tardanza',
            self::STATUS_JUSTIFICADA => 'Justificada',
            self::STATUS_FALTA => 'Falta',
        ];
    }

    protected $fillable = [
        'school_id',
        'year_id',
        'section_id',
        'enrollment_id',
        'attendance_date',
        'status',
        'user_id_registers',
    ];

    protected $casts = [
        'attendance_date' => 'date:Y-m-d',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolEnrollment::class, 'enrollment_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolSection::class, 'section_id');
    }
}
