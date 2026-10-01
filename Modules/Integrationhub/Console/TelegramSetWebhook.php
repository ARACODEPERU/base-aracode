<?php

namespace Modules\Integrationhub\Console;

use Illuminate\Console\Command;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramWebhookRegistrar;

/**
 * Registra (o retira) el webhook del bot de Telegram apuntando a esta
 * aplicacion, y publica el menu de comandos del bot.
 *
 * Se usa despues de desplegar o cada vez que cambia el token en el parametro
 * del sistema SC-00002, porque el secreto del webhook se deriva del token.
 */
class TelegramSetWebhook extends Command
{
    protected $signature = 'integrationhub:telegram-set-webhook {action=set : set, delete o status}';

    protected $description = 'Registra, retira o consulta el webhook del bot de Telegram';

    public function handle(TelegramBotService $bot, TelegramWebhookRegistrar $registrar): int
    {
        if (! $registrar->isConfigured()) {
            $this->error(
                'Falta el token del bot en el parámetro '
                . config('integrationhub.telegram.parameter', 'SC-00002')
                . ' (Parámetros del sistema).'
            );

            return self::FAILURE;
        }

        $action = strtolower((string) $this->argument('action'));

        try {
            if ($action === 'delete') {
                $bot->deleteWebhook(true);
                $this->info('Webhook retirado. Usa /api/integrationhub/telegram/webhook con getUpdates para depurar.');

                return self::SUCCESS;
            }

            if ($action === 'status') {
                $this->line('Usuario del bot: ' . ($bot->username(true) ?? 'no disponible'));
                $this->line('URL del webhook: ' . $bot->webhookUrl());
                $this->line('Enlace de registro: ' . ($bot->registrationLink() ?? 'no disponible'));

                return self::SUCCESS;
            }

            $url = $registrar->register(true);

            $this->info('Webhook registrado en: ' . $url);
            $this->line('Usuario del bot: ' . ($bot->username(true) ?? 'no disponible'));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
