<?php

namespace Modules\Integrationhub\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Integrationhub\Contracts\TelegramRegistrantResolver;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Entities\IntegrationTelegramRegistrationSession;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramMessageService;
use Modules\Integrationhub\Services\TelegramRegistrationService;
use Modules\Integrationhub\Support\TelegramMessages;

/**
 * Recepcion de los mensajes que las personas le escriben al bot de Telegram.
 *
 * Telegram entrega cada update por POST a la URL registrada con setWebhook y la
 * peticion se valida con el header X-Telegram-Bot-Api-Secret-Token (el secreto
 * se deriva del token del bot). El endpoint es publico a proposito: Telegram no
 * tiene sesion ni token de sanctum.
 *
 * Registro:
 *   - /start abre la conversacion y pide el documento de identidad.
 *   - Con la conversacion abierta, el siguiente mensaje se interpreta como el
 *     documento: se resuelve contra el padron y, si corresponde, se guarda el
 *     chat_id.
 *   - /baja (o /stop) da de baja el chat para que deje de recibir campanas.
 *   - /ayuda recuerda como registrarse.
 *
 * Todo el texto que sale de aqui proviene del catalogo configurable
 * (Support\TelegramMessages + el servicio de mensajes): nada de copy escrito en
 * el controlador. Siempre se responde HTTP 200: ante un error Telegram reintenta
 * el update una y otra vez, y un fallo de negocio (documento desconocido) no
 * debe provocar eso.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        TelegramMessageService $messages
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

        // Updates que no son mensajes (adjuntos, callbacks, etc.).
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
            $this->handleMessage($bot, $registration, $messages, $chatId, $text, $username, $firstName);
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
     * Enruta el mensaje segun el comando recibido o la conversacion en curso.
     */
    private function handleMessage(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        TelegramMessageService $messages,
        string $chatId,
        string $text,
        ?string $username,
        ?string $firstName
    ): void {
        $registration->touch($chatId);
        $command = $this->command($text);

        if (in_array($command, ['/baja', '/stop', '/cancelar'], true)) {
            $this->handleOptOut($bot, $registration, $messages, $chatId);

            return;
        }

        if (in_array($command, ['/ayuda', '/help'], true)) {
            $this->reply($bot, $messages, $chatId, TelegramMessages::HELP);

            return;
        }

        if ($command === '/start') {
            $this->handleStart($bot, $registration, $messages, $chatId, $username, $firstName);

            return;
        }

        // Cualquier texto libre: si hay una conversacion abierta es el documento;
        // si no, un recordatorio corto (no la ayuda completa, que confunde a
        // quien solo escribio "hola").
        $session = $registration->sessionFor($chatId);

        if ($session === null) {
            $this->reply($bot, $messages, $chatId, TelegramMessages::START_HINT);

            return;
        }

        $this->handleDocument($bot, $registration, $messages, $session, $chatId, $text, $username, $firstName);
    }

    /**
     * /start: abre (o reinicia) el registro y pide el documento.
     */
    private function handleStart(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        TelegramMessageService $messages,
        string $chatId,
        ?string $username,
        ?string $firstName
    ): void {
        $registration->beginSession($chatId, $username, $firstName);

        $contact = $registration->findByChatId($chatId);
        $registered = $contact !== null && trim((string) $contact->chat_id) !== '';

        if (! $registered) {
            $this->reply($bot, $messages, $chatId, TelegramMessages::START_NEW);

            return;
        }

        $contact->loadMissing('person');

        $this->reply($bot, $messages, $chatId, TelegramMessages::START_REGISTERED, [
            'nombre' => (string) ($contact->person?->short_name ?? ''),
        ]);
    }

    /**
     * Interpreta el texto como documento y, si corresponde, vincula el chat.
     */
    private function handleDocument(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        TelegramMessageService $messages,
        IntegrationTelegramRegistrationSession $session,
        string $chatId,
        string $text,
        ?string $username,
        ?string $firstName
    ): void {
        $document = $this->document($text);

        if ($document === '') {
            $this->rejectDocument(
                $bot,
                $registration,
                $messages,
                $session,
                $chatId,
                TelegramMessages::DOCUMENT_UNRECOGNIZED,
                'El mensaje no parece un documento de identidad.'
            );

            return;
        }

        $resolver = $this->resolver();

        if ($resolver === null) {
            $registration->closeSession($chatId);
            $this->reply($bot, $messages, $chatId, TelegramMessages::REGISTRATION_DISABLED);

            return;
        }

        $registrant = $resolver->resolveByDocument($document);

        if ($registrant === null) {
            $this->rejectDocument(
                $bot,
                $registration,
                $messages,
                $session,
                $chatId,
                TelegramMessages::DOCUMENT_NOT_FOUND,
                'El documento no esta en el padron.'
            );

            return;
        }

        $programs = array_values(array_filter(array_map(
            fn ($program) => trim((string) $program),
            (array) ($registrant['programs'] ?? [])
        )));

        $hasSubscription = ! empty($registrant['subscription']);

        if ($programs === [] && ! $hasSubscription) {
            $registration->closeSession($chatId);
            $this->reply($bot, $messages, $chatId, TelegramMessages::DOCUMENT_NO_PROGRAMS);

            return;
        }

        $registration->attach(
            (int) ($registrant['person_id'] ?? 0),
            $chatId,
            $username,
            $firstName
        );

        $registration->closeSession($chatId);

        $this->reply($bot, $messages, $chatId, TelegramMessages::REGISTERED, [
            'nombre' => (string) ($registrant['name'] ?? ''),
            'programas' => implode(', ', $programs),
            'suscripcion' => $hasSubscription ? 'Suscripción activa' : '',
        ]);
    }

    /**
     * Documento invalido o desconocido: se cuenta el intento y, agotados, se
     * cierra la conversacion para no dejar el chat a la espera.
     */
    private function rejectDocument(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        TelegramMessageService $messages,
        IntegrationTelegramRegistrationSession $session,
        string $chatId,
        string $code,
        string $reason
    ): void {
        $attempts = $registration->countAttempt($session);
        $max = $registration->maxAttempts();

        IntegrationError::create([
            'message' => 'Registro de Telegram (chat ' . $chatId . '): ' . $reason
                . ' Intento ' . $attempts . ' de ' . $max . '.',
            'source' => 'telegram_registration',
        ]);

        if ($attempts >= $max) {
            $registration->closeSession($chatId);
            $this->reply($bot, $messages, $chatId, TelegramMessages::TOO_MANY_ATTEMPTS);

            return;
        }

        $this->reply($bot, $messages, $chatId, $code, [
            'intentos' => "Intento {$attempts} de {$max}",
        ]);
    }

    private function handleOptOut(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        TelegramMessageService $messages,
        string $chatId
    ): void {
        $registration->closeSession($chatId);
        $deactivated = $registration->deactivate($chatId);

        $this->reply(
            $bot,
            $messages,
            $chatId,
            $deactivated ? TelegramMessages::OPTOUT_DONE : TelegramMessages::OPTOUT_NONE
        );
    }

    /**
     * Renderiza un mensaje del catalogo y lo envia con su formato configurado.
     *
     * @param array<string, string|null> $variables
     */
    private function reply(
        TelegramBotService $bot,
        TelegramMessageService $messages,
        string $chatId,
        string $code,
        array $variables = []
    ): void {
        $bot->sendFormatted(
            $chatId,
            $messages->render($code, $variables),
            $messages->isHtml($code)
        );
    }

    /**
     * Implementacion del padron, si algun modulo la vinculo.
     */
    private function resolver(): ?TelegramRegistrantResolver
    {
        if (! app()->bound(TelegramRegistrantResolver::class)) {
            return null;
        }

        try {
            return app(TelegramRegistrantResolver::class);
        } catch (\Throwable) {
            // Un padron que no se puede construir (modulo no instalado o sin
            // configuracion) se trata como registro no disponible.
            return null;
        }
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
     * Documento normalizado a partir del mensaje (sin espacios ni guiones).
     *
     * Se exige al menos un digito: un texto de puras letras es un mensaje, no un
     * documento, y merece una respuesta distinta.
     */
    private function document(string $text): string
    {
        $document = strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $text));

        if (! preg_match('/\d/', $document)) {
            return '';
        }

        return strlen($document) >= 5 && strlen($document) <= 20 ? $document : '';
    }
}
