<?php

namespace Modules\Health\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Modules\Health\Services\GoogleCalendarSyncService;

/**
 * Lee los cambios de Google Calendar y los aplica al sistema.
 *
 * Lo disparan tres cosas: la notificacion push de Google (con un pequeno
 * retardo para que una rafaga de cambios se resuelva en una sola lectura), el
 * planificador cada pocos minutos como red de seguridad (Google no garantiza
 * que todas las notificaciones lleguen) y el boton "Sincronizar ahora".
 *
 * withoutOverlapping evita que dos lecturas avancen el syncToken a la vez.
 */
class PullGoogleCalendarChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Una lectura fallida no se reintenta sola: la siguiente corrida programada
     * vuelve a intentarlo con el mismo syncToken.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('health-google-calendar-pull'))->expireAfter(600)];
    }

    public function handle(GoogleCalendarSyncService $sync): void
    {
        if (! $sync->ready() || ! $sync->inboundEnabled()) {
            $sync->clearPushPending();

            return;
        }

        try {
            $sync->pull();
        } finally {
            // La marca se limpia siempre: si la lectura fallo, la siguiente
            // notificacion de Google (o el planificador) la vuelve a poner.
            $sync->clearPushPending();
        }
    }
}
