<?php

namespace Modules\Academic\Services;

use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramMessageService;
use Modules\Integrationhub\Support\TelegramMessages;

/**
 * Notificacion de curso por Telegram.
 *
 * Telegram no envia por telefono sino por chat_id, asi que este servicio arma
 * el aviso con la plantilla configurable (Support\TelegramMessages::CAMPAIGN) y
 * lo entrega al chat que el alumno registro con el bot. La integracion
 * (Telegram_bot), el token (parametro del sistema SC-00002) y los textos viven
 * en el modulo Integrationhub.
 *
 * Se ofrece en la pantalla de Notificaciones unicamente cuando hay token: igual
 * que el SMS de Vonage y el flujo de WhatsApp.
 */
class TelegramCourseNotifier
{
    public function __construct(
        private readonly TelegramBotService $bot,
        private readonly TelegramMessageService $messages,
    ) {
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
     * Texto final del aviso: la plantilla configurada con los datos que el
     * administrador escribio en el formulario de la campana.
     *
     * El curso y el tiempo son variables de la plantilla, y cuando vienen vacios
     * su linea no se envia.
     */
    public function renderCampaignText(
        string $message,
        string $courseName,
        ?string $time,
        ?string $studentName = null
    ): string {
        return $this->messages->render(TelegramMessages::CAMPAIGN, [
            'mensaje' => $message,
            'curso' => $courseName,
            'tiempo' => $time,
            'nombre' => $studentName,
        ]);
    }

    /**
     * Formato configurado para los avisos de campana (html o texto plano).
     */
    public function campaignIsHtml(): bool
    {
        return $this->messages->isHtml(TelegramMessages::CAMPAIGN);
    }

    /**
     * Envia el mensaje al chat indicado.
     *
     * @throws \RuntimeException cuando falta el token o Telegram rechaza el envio.
     */
    public function send(string $chatId, string $text, bool $html = true): void
    {
        $this->bot->sendFormatted($chatId, $text, $html);
    }
}
