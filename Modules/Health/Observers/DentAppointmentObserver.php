<?php

namespace Modules\Health\Observers;

use Modules\Dental\Entities\DentAppointment;
use Modules\Health\Jobs\SyncAppointmentToGoogleCalendar;
use Modules\Health\Services\GoogleCalendarSyncService;
use Modules\Health\Support\AppointmentEventMapper;
use Modules\Health\Support\GoogleCalendarSyncGuard;

/**
 * Observador de las citas de la Agenda.
 *
 * Es el unico punto de salida hacia Google Calendar, y por eso cubre todos los
 * caminos que crean, mueven o borran una cita: la Agenda de Salud, el CRUD de
 * Dental, la cita siguiente de una atencion y el generador de datos de prueba.
 *
 * No hace el trabajo: encola el job para que el envio ocurra en la cola y no
 * frene la peticion del usuario. Tampoco encola nada cuando:
 *   - la escritura viene de Google (GoogleCalendarSyncGuard.suppressed()), o
 *   - el canal esta apagado o sin credenciales (sync.ready()), o
 *   - el cambio no toca ningun campo que Google conozca.
 */
class DentAppointmentObserver
{
    public function __construct(
        private readonly GoogleCalendarSyncService $sync,
        private readonly AppointmentEventMapper $mapper,
    ) {
    }

    public function created(DentAppointment $appointment): void
    {
        $this->enqueue((int) $appointment->id, GoogleCalendarSyncService::ACTION_UPSERT);
    }

    public function updated(DentAppointment $appointment): void
    {
        // Cambiar solo `updated_user_id`, `updated_at` o `no_show_at` no es un
        // cambio de la cita: no vale la pena escribir en Google.
        if (! $this->mapper->isRelevantChange($appointment->getChanges())) {
            return;
        }

        $this->enqueue((int) $appointment->id, GoogleCalendarSyncService::ACTION_UPSERT);
    }

    public function deleted(DentAppointment $appointment): void
    {
        $this->enqueue((int) $appointment->id, GoogleCalendarSyncService::ACTION_DELETE);
    }

    /**
     * Encola el push de la cita (o el borrado de su evento).
     */
    private function enqueue(int $appointmentId, string $action): void
    {
        if (GoogleCalendarSyncGuard::suppressed()) {
            return;
        }

        if ($appointmentId <= 0 || ! $this->sync->ready()) {
            return;
        }

        SyncAppointmentToGoogleCalendar::dispatch($appointmentId, $action);
    }
}
