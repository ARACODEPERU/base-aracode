<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Dental\Entities\DentAppointment;

/**
 * Mapeo entre una cita de la Agenda y un evento de Google Calendar.
 *
 * Tambien es el buzon de los eventos que llegan de Google sin cita conocida
 * (sync_state = pending_review). El `etag` y el `pushed_hash` son las firmas
 * que permiten reconocer el propio eco y no entrar en bucle.
 */
class HealGoogleCalendarEvent extends Model
{
    /** El evento y la cita estan enlazados y en sincronia. */
    public const STATE_LINKED = 'linked';

    /** El evento llego de Google y falta resolver paciente o doctor. */
    public const STATE_PENDING_REVIEW = 'pending_review';

    /** El evento se borro: fila lapida que reconoce un eco tardio. */
    public const STATE_DELETED = 'deleted';

    /** El usuario decidio no traer ese evento al sistema. */
    public const STATE_IGNORED = 'ignored';

    /** El ultimo intento de sincronizacion fallo. */
    public const STATE_ERROR = 'error';

    /** El ultimo cambio lo escribio el sistema (Salud). */
    public const ORIGIN_HEALTH = 'health';

    /** El ultimo cambio llego desde Google Calendar. */
    public const ORIGIN_GOOGLE = 'google';

    protected $table = 'heal_google_calendar_events';

    protected $fillable = [
        'appointment_id',
        'calendar_id',
        'google_event_id',
        'etag',
        'google_updated_at',
        'pushed_hash',
        'origin',
        'sync_state',
        'summary',
        'starts_at',
        'ends_at',
        'location',
        'payload',
        'error_message',
        'last_synced_at',
    ];

    protected $casts = [
        'google_updated_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'payload' => 'array',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(DentAppointment::class, 'appointment_id');
    }

    /**
     * true si el evento espera que un usuario le asigne paciente y doctor.
     */
    public function isPendingReview(): bool
    {
        return $this->sync_state === self::STATE_PENDING_REVIEW;
    }

    /**
     * true si la fila referencia un evento que existe en Google.
     */
    public function isLinked(): bool
    {
        return $this->sync_state === self::STATE_LINKED && (string) $this->google_event_id !== '';
    }
}
