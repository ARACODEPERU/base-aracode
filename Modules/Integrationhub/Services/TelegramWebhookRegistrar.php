<?php

namespace Modules\Integrationhub\Services;

use RuntimeException;

/**
 * Registra el webhook del bot de Telegram y publica su menu de comandos.
 *
 * Es el unico lugar donde vive el "set" del webhook: lo comparten el comando
 * integrationhub:telegram-set-webhook, la accion de la pantalla de
 * Notificaciones del modulo Academico y la migracion que lo registra al
 * desplegar, asi que los tres apuntan siempre a la misma URL y al mismo secreto.
 *
 * setWebhook y setMyCommands son idempotentes por si mismos: Telegram guarda el
 * ultimo valor que recibe, de modo que repetir la llamada no duplica nada ni
 * ensucia la configuracion.
 */
class TelegramWebhookRegistrar
{
    public function __construct(private readonly TelegramBotService $bot)
    {
    }

    /**
     * true solo si el parametro del sistema tiene un token cargado.
     */
    public function isConfigured(): bool
    {
        return $this->bot->isConfigured();
    }

    /**
     * Apunta el webhook a esta aplicacion y publica el menu de comandos.
     *
     * La URL se calcula desde config('app.url'), por eso cada entorno registra
     * la suya; el secreto se deriva del token, por eso cambiar el token obliga a
     * registrar de nuevo.
     *
     * @return string URL que quedo registrada en Telegram
     *
     * @throws RuntimeException cuando falta el token o Telegram rechaza la peticion.
     */
    public function register(bool $dropPendingUpdates = true): string
    {
        $url = $this->bot->webhookUrl();

        $this->bot->setWebhook($url, $dropPendingUpdates);
        $this->bot->setMyCommands($this->bot->defaultCommands());

        return $url;
    }
}
