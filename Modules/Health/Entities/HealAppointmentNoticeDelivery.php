<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Dental\Entities\DentAppointment;

/**
 * Entrega de un aviso de cita.
 *
 * Existe una fila por cita, bloque y canal; la clave unica impide que el
 * comando encole dos veces el mismo aviso.
 */
class HealAppointmentNoticeDelivery extends Model
{
    protected $table = 'heal_appointment_notice_deliveries';

    protected $fillable = [
        'appointment_id',
        'notice_id',
        'channel',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(DentAppointment::class, 'appointment_id');
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(HealAppointmentNotice::class, 'notice_id');
    }
}
