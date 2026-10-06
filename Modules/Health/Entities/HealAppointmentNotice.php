<?php

namespace Modules\Health\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bloque de aviso previo a la cita (Salud > Avisos).
 *
 * Son tres filas fijas identificadas por `key`: `first` y `second` son los
 * avisos de "minutos antes" y `day_before` el aviso de "un dia antes" a la hora
 * guardada en send_time.
 */
class HealAppointmentNotice extends Model
{
    protected $table = 'heal_appointment_notices';

    protected $fillable = [
        'key',
        'active',
        'minutes_before',
        'send_time',
        'message',
    ];

    protected $casts = [
        'active' => 'boolean',
        'minutes_before' => 'integer',
    ];

    /**
     * Entregas registradas de este bloque.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(HealAppointmentNoticeDelivery::class, 'notice_id');
    }

    /**
     * true si es un aviso de "minutos antes" de la cita.
     */
    public function isBefore(): bool
    {
        return $this->key !== 'day_before';
    }

    /**
     * true si es el aviso de "un dia antes".
     */
    public function isDayBefore(): bool
    {
        return $this->key === 'day_before';
    }
}
