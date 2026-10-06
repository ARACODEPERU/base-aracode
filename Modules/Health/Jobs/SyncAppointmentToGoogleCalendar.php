<?php

namespace Modules\Health\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Services\GoogleCalendarSyncService;

/**
 * Envia una cita a Google Calendar (o borra su evento).
 *
 * El observador de la cita solo encola: el trabajo real ocurre aqui, en la
 * cola, para no frenar la peticion del usuario ni el planificador. El servicio
 * es idempotente (compara el `etag` y el contenido antes de escribir), asi que
 * un reintento no duplica ni repite escrituras.
 */
class SyncAppointmentToGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Reintentos ante un fallo transitorio (red o Google).
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Segundos de espera entre reintentos.
     *
     * @var int
     */
    public $backoff = 30;

    public function __construct(
        public int $appointmentId,
        public string $action = GoogleCalendarSyncService::ACTION_UPSERT,
    ) {
    }

    public function handle(GoogleCalendarSyncService $sync): void
    {
        if (! $sync->ready()) {
            return;
        }

        if ($this->action === GoogleCalendarSyncService::ACTION_DELETE) {
            $sync->pushDelete($this->appointmentId);

            return;
        }

        $appointment = DentAppointment::with(['patient', 'doctor'])->find($this->appointmentId);

        if (! $appointment) {
            // La cita se borro antes de que el worker la tomara: se limpia el
            // evento que hubiera quedado en Google.
            $sync->pushDelete($this->appointmentId);

            return;
        }

        $sync->push($appointment);
    }
}
