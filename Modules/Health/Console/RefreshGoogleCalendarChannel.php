<?php

namespace Modules\Health\Console;

use Illuminate\Console\Command;
use Modules\Health\Services\GoogleCalendarSyncService;

/**
 * Registra o renueva el canal de notificaciones push de Google Calendar.
 *
 * Los canales de Google expiran (como maximo unas semanas), asi que hay que
 * volver a registrarlos antes de que caduquen; de eso se encarga este comando,
 * que corre a diario.
 *
 * Google solo entrega notificaciones a una URL HTTPS publica. Si el sistema
 * esta en local (por ejemplo http://base-aracode.test) no hay canal posible: se
 * avisa y se termina con exito, porque la reconciliacion programada
 * (health:sync-google-calendar) sigue revisando los cambios.
 */
class RefreshGoogleCalendarChannel extends Command
{
    protected $signature = 'health:google-calendar-refresh-channel';

    protected $description = 'Registra o renueva el canal de notificaciones push de Google Calendar';

    public function handle(GoogleCalendarSyncService $sync): int
    {
        if (! $sync->ready()) {
            $this->info('Google Calendar: el canal esta apagado o sin credenciales.');

            return self::SUCCESS;
        }

        if ($sync->webhookUrl() === null) {
            $this->warn(
                'Google Calendar: no hay una URL HTTPS publica para las notificaciones (define HEALTH_GOOGLE_WEBHOOK_URL). '
                . 'Mientras tanto, la reconciliacion programada es la que revisa los cambios.'
            );

            return self::SUCCESS;
        }

        $result = $sync->refreshChannel();

        if (! $result['ok']) {
            $this->error('Google Calendar: ' . $result['message']);

            return self::FAILURE;
        }

        $this->info('Google Calendar: ' . $result['message'] . ' Vence el ' . ($result['expiresAt'] ?? 'sin fecha') . '.');

        return self::SUCCESS;
    }
}
