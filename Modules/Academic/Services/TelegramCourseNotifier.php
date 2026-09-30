<?php

namespace Modules\Academic\Services;

use Modules\Integrationhub\Services\TelegramBotService;

/**
 * Notificacion de curso por Telegram.
 *
 * Telegram no envia por telefono sino por chat_id, asi que este servicio solo
 * entrega un mensaje ya compuesto al chat que el alumno registro con el bot.
 * La integracion (Telegram_bot) y el token (parametro del sistema SC-00002)
 * viven en el modulo Integrationhub.
 *
 * Se ofrece en la pantalla de Notificaciones unicamente cuando hay token: igual
 * que el SMS de Vonage y el flujo de WhatsApp.
 */
class TelegramCourseNotifier
{
    public function __construct(private readonly TelegramBotService $bot)
    {
    }

    /**
     * true solo si el parametro del sistema tiene el token del bot.
     */
    public static function isConfigured(): bool
    {
        try {
            return app(TelegramBotService::class)->isConfigured();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Envia el mensaje al chat indicado.
     *
     * @throws \RuntimeException cuando falta el token o Telegram rechaza el envio.
     */
    public function send(string $chatId, string $text): void
    {
        $this->bot->sendMessage($chatId, $text);
    }
}
