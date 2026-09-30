<?php

namespace Modules\Integrationhub\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramRegistrationService;
use RuntimeException;

/**
 * Recepcion de los mensajes que los usuarios le escriben al bot de Telegram.
 *
 * Telegram entrega cada update por POST a la URL registrada con setWebhook y la
 * peticion se valida con el header X-Telegram-Bot-Api-Secret-Token (el secreto
 * se deriva del token del bot). El endpoint es publico a proposito: Telegram no
 * tiene sesion ni token de sanctum.
 *
 * Flujo de registro:
 *   - /start <codigo> vincula el chat_id con la persona del enlace de registro.
 *   - /start sin codigo explica como obtener el enlace.
 *   - /baja (o /stop) da de baja el chat para que deje de recibir campanas.
 *
 * Siempre se responde HTTP 200: ante un error Telegram reintenta el update una
 * y otra vez, y un fallo de negocio (codigo expirado) no debe provocar eso.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotService $bot,
        TelegramRegistrationService $registration
    ): JsonResponse {
        $secret = $bot->secret();
        $received = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        // Sin token configurado no hay bot que atender.
        if ($secret === null) {
            return response()->json(['ok' => true, 'message' => 'El bot de Telegram no tiene token configurado.'], 200);
        }

        if (! hash_equals($secret, $received)) {
            return response()->json(['message' => 'Secreto de webhook invalido.'], 403);
        }

        $update = $request->all();
        $message = $update['message'] ?? $update['edited_message'] ?? null;

        // Updates que no son mensajes de texto (adjuntos, callbacks, etc.).
        if (! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chatId = trim((string) ($message['chat']['id'] ?? ''));

        if ($chatId === '') {
            return response()->json(['ok' => true]);
        }

        $text = trim((string) ($message['text'] ?? ''));
        $username = $message['from']['username'] ?? null;
        $firstName = $message['from']['first_name'] ?? null;

        try {
            $this->handleMessage($bot, $registration, $chatId, $text, $username, $firstName);
        } catch (\Throwable $exception) {
            Log::error('Bot de Telegram: fallo al procesar un mensaje', [
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);

            IntegrationError::create([
                'message' => 'Webhook de Telegram (chat ' . $chatId . '): ' . $exception->getMessage(),
                'source' => 'telegram_webhook',
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Enruta el mensaje segun el comando recibido.
     */
    private function handleMessage(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        string $chatId,
        string $text,
        ?string $username,
        ?string $firstName
    ): void {
        $registration->touch($chatId);
        $command = $this->command($text);

        if ($command === '/start') {
            $this->handleStart($bot, $registration, $chatId, $text, $username, $firstName);

            return;
        }

        if (in_array($command, ['/baja', '/stop', '/cancelar'], true)) {
            $this->handleOptOut($bot, $registration, $chatId);

            return;
        }

        $bot->sendMessage($chatId, $this->helpMessage());
    }

    /**
     * /start: con codigo vincula el chat; sin codigo explica como registrarse.
     */
    private function handleStart(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        string $chatId,
        string $text,
        ?string $username,
        ?string $firstName
    ): void {
        $code = $this->startPayload($text);

        if ($code === '') {
            $bot->sendMessage($chatId, $this->helpMessage());

            return;
        }

        try {
            $contact = $registration->link($code, $chatId, $username, $firstName);
        } catch (RuntimeException $exception) {
            $bot->sendMessage($chatId, $exception->getMessage() . "\n\n" . $this->helpMessage());

            return;
        }

        $bot->sendMessage($chatId, $this->registeredMessage($contact));
    }

    private function handleOptOut(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        string $chatId
    ): void {
        $deactivated = $registration->deactivate($chatId);

        $bot->sendMessage($chatId, $deactivated
            ? 'Listo, ya no recibirás avisos por este chat. Si quieres volver a activarlo, pide a la institución un nuevo enlace de registro.'
            : 'Este chat no estaba registrado, así que no había nada que dar de baja.');
    }

    /**
     * Comando normalizado: minusculas y sin el sufijo @NombreDelBot.
     */
    private function command(string $text): ?string
    {
        $text = trim($text);

        if ($text === '' || ! str_starts_with($text, '/')) {
            return null;
        }

        $parts = preg_split('/\s+/', $text) ?: [];
        $command = strtolower((string) array_shift($parts));

        return explode('@', $command)[0];
    }

    /**
     * Texto que acompaña a /start (el codigo del deep link).
     */
    private function startPayload(string $text): string
    {
        $parts = preg_split('/\s+/', trim($text)) ?: [];
        array_shift($parts);

        return trim((string) ($parts[0] ?? ''));
    }

    private function registeredMessage(IntegrationTelegramContact $contact): string
    {
        $contact->loadMissing('person');
        $name = trim((string) ($contact->person?->short_name ?? ''));

        return trim(
            ($name !== '' ? 'Hola ' . $name . ', ' : 'Hola, ')
            . 'tu Telegram quedó registrado. Desde ahora recibirás por aquí los avisos de la institución.'
            . "\n\nSi en algún momento quieres dejar de recibirlos, escribe /baja."
        );
    }

    private function helpMessage(): string
    {
        return "Soy el bot de avisos de la institución.\n\n"
            . "Para activar los mensajes en este chat, abre el enlace de registro que te compartieron "
            . "(el que empieza con https://t.me/...) y pulsa Iniciar, o envía aquí /start CÓDIGO.\n\n"
            . "Si ya no quieres recibir avisos, escribe /baja.";
    }
}
