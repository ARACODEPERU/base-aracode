<?php

namespace Modules\Health\Console;

use Illuminate\Console\Command;
use Modules\Health\Services\GoogleCalendarSyncService;

/**
 * Trae a la Agenda los cambios hechos en Google Calendar.
 *
 * No envia nada: solo lee el feed incremental (o la ventana completa la primera
 * vez y cuando el syncToken caduca) y aplica los cambios a las citas. Corre
 * desde el planificador cada pocos minutos y tambien lo usan la notificacion
 * push de Google y el boton "Sincronizar ahora".
 *
 * No hace nada si faltan las tablas, si el canal esta apagado (SC-00010), si
 * faltan credenciales o si la direccion Google -> sistema esta apagada
 * (SC-00017).
 */
class SyncGoogleCalendar extends Command
{
    protected $signature = 'health:sync-google-calendar {--full : Ignora el sync token y vuelve a leer la ventana completa}';

    protected $description = 'Lee los cambios de Google Calendar y los aplica a la Agenda de Salud';

    public function handle(GoogleCalendarSyncService $sync): int
    {
        if (! $sync->tablesReady()) {
            $this->info('Google Calendar: la instalacion todavia no esta migrada.');

            return self::SUCCESS;
        }

        if (! $sync->ready()) {
            $this->info('Google Calendar: el canal esta apagado o sin credenciales.');

            return self::SUCCESS;
        }

        if (! $sync->inboundEnabled()) {
            $this->info('Google Calendar: la recepcion de cambios esta apagada (parametro SC-00017).');

            return self::SUCCESS;
        }

        try {
            $result = $sync->pull((bool) $this->option('full'));
        } catch (\Throwable $exception) {
            $this->error('Google Calendar: la sincronizacion fallo: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Google Calendar (%s): %d cita(s) creada(s), %d actualizada(s), %d borrada(s), %d por revisar.',
            ($result['full'] ?? false) ? 'lectura completa' : 'lectura incremental',
            (int) ($result['created'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['deleted'] ?? 0),
            (int) ($result['review'] ?? 0)
        ));

        return self::SUCCESS;
    }
}
