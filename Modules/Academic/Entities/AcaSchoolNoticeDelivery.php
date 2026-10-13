<?php

namespace Modules\Academic\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrega de un aviso de asistencia al apoderado.
 *
 * Existe una fila por asistencia de portería, evento (entrada o salida) y
 * destinatario; la clave única impide que la portería encole dos veces el mismo
 * aviso aunque el alumno vuelva a pasar por la puerta en el mismo día.
 */
class AcaSchoolNoticeDelivery extends Model
{
    protected $table = 'aca_school_notice_deliveries';

    /** Evento de portería que originó el aviso: entrada. */
    public const EVENT_ENTRY = 'in';

    /** Evento de portería que originó el aviso: salida. */
    public const EVENT_EXIT = 'out';

    protected $fillable = [
        'gate_attendance_id',
        'student_id',
        'guardian_person_id',
        'recipient_key',
        'event',
        'phone',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function gateAttendance(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolGateAttendance::class, 'gate_attendance_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolStudent::class, 'student_id');
    }

    /** Apoderado destinatario (null cuando el aviso sale al teléfono de la matrícula). */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'guardian_person_id');
    }

    /** Etiqueta legible del evento del aviso. */
    public function eventLabel(): string
    {
        return match ($this->event) {
            self::EVENT_ENTRY => 'Entrada',
            self::EVENT_EXIT => 'Salida',
            default => (string) $this->event,
        };
    }
}
