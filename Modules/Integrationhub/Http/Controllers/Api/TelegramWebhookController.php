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
use Modules\Integrationhub\Services\TelegramRegistrationService;

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
 * Siempre se responde HTTP 200: ante un error Telegram reintenta el update una
 * y otra vez, y un fallo de negocio (documento desconocido) no debe provocar
 * eso.
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
     * Enruta el mensaje segun el comando recibido o la conversacion en curso.
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

        if (in_array($command, ['/baja', '/stop', '/cancelar'], true)) {
            $this->handleOptOut($bot, $registration, $chatId);

            return;
        }

        if (in_array($command, ['/ayuda', '/help'], true)) {
            $bot->sendMessage($chatId, $this->helpMessage());

            return;
        }

        if ($command === '/start') {
            $this->handleStart($bot, $registration, $chatId, $username, $firstName);

            return;
        }

        // Cualquier texto libre: si hay una conversacion abierta es el documento;
        // si no, un recordatorio corto (no la ayuda completa, que confunde a
        // quien solo escribio "hola").
        $session = $registration->sessionFor($chatId);

        if ($session === null) {
            $bot->sendMessage($chatId, $this->startHint());

            return;
        }

        $this->handleDocument($bot, $registration, $session, $chatId, $text, $username, $firstName);
    }

    /**
     * /start: abre (o reinicia) el registro y pide el documento.
     */
    private function handleStart(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        string $chatId,
        ?string $username,
        ?string $firstName
    ): void {
        $registration->beginSession($chatId, $username, $firstName);

        $bot->sendMessage($chatId, $this->documentMessage($registration->findByChatId($chatId)));
    }

    /**
     * Interpreta el texto como documento y, si corresponde, vincula el chat.
     */
    private function handleDocument(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
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
                $session,
                $chatId,
                'No reconocimos ese dato como un documento de identidad. Escribe solo el número, sin espacios ni guiones.'
            );

            return;
        }

        $resolver = $this->resolver();

        if ($resolver === null) {
            $registration->closeSession($chatId);
            $bot->sendMessage($chatId, 'El registro por Telegram no está habilitado en este momento. Contacta a la institución.');

            return;
        }

        $registrant = $resolver->resolveByDocument($document);

        if ($registrant === null) {
            $this->rejectDocument(
                $bot,
                $registration,
                $session,
                $chatId,
                'No encontramos ese documento en el padrón.'
            );

            return;
        }

        $programs = array_values(array_filter(array_map(
            fn ($program) => trim((string) $program),
            (array) ($registrant['programs'] ?? [])
        )));

        if ($programs === [] && empty($registrant['subscription'])) {
            $registration->closeSession($chatId);

            $bot->sendMessage(
                $chatId,
                'Encontramos tu ficha, pero no tiene un programa activo ni una suscripción vigente. '
                . 'Contacta a la institución para revisar tu matrícula.'
            );

            return;
        }

        $registration->attach(
            (int) ($registrant['person_id'] ?? 0),
            $chatId,
            $username,
            $firstName
        );

        $registration->closeSession($chatId);

        $bot->sendMessage($chatId, $this->registeredMessage($registrant, $programs));
    }

    /**
     * Documento invalido o desconocido: se cuenta el intento y, agotados, se
     * cierra la conversacion para no dejar el chat a la espera.
     */
    private function rejectDocument(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        IntegrationTelegramRegistrationSession $session,
        string $chatId,
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

            $bot->sendMessage(
                $chatId,
                "Demasiados intentos seguidos.\n\nVerifica tu documento y escribe /start para volver a intentarlo."
            );

            return;
        }

        $bot->sendMessage(
            $chatId,
            $reason . "\n\nVuelve a escribirlo o pide ayuda a la institución. (Intento {$attempts} de {$max})"
        );
    }

    private function handleOptOut(
        TelegramBotService $bot,
        TelegramRegistrationService $registration,
        string $chatId
    ): void {
        $registration->closeSession($chatId);
        $deactivated = $registration->deactivate($chatId);

        $bot->sendMessage($chatId, $deactivated
            ? 'Listo, ya no recibirás avisos por este chat. Si quieres volver a activarlo, escribe /start y tu número de documento.'
            : 'Este chat no estaba registrado, así que no había nada que dar de baja.');
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

    private function documentMessage(?IntegrationTelegramContact $contact): string
    {
        $lines = ['Hola, soy el bot de avisos de la institución.'];

        if ($contact !== null && trim((string) $contact->chat_id) !== '') {
            $contact->loadMissing('person');
            $name = trim((string) ($contact->person?->short_name ?? ''));

            $lines[] = $name !== ''
                ? 'Este chat ya está registrado a nombre de ' . $name . '. Si escribes otro documento, el registro se actualizará.'
                : 'Este chat ya está registrado. Si escribes otro documento, el registro se actualizará.';
        }

        $lines[] = 'Para activar los avisos en este chat, escribe tu número de documento (DNI), solo el número.';
        $lines[] = 'Si ya no quieres recibir avisos, escribe /baja.';

        return implode("\n\n", $lines);
    }

    /**
     * @param array{person_id: int, name: string, subscription: bool} $registrant
     * @param array<int, string> $programs
     */
    private function registeredMessage(array $registrant, array $programs): string
    {
        $name = trim((string) ($registrant['name'] ?? ''));
        $lines = [
            ($name !== '' ? 'Listo, ' . $name . '. ' : 'Listo. ')
            . 'Tu chat quedó registrado y desde ahora recibirás por aquí los avisos de la institución.',
        ];

        if ($programs !== []) {
            $lines[] = 'Programas: ' . implode(', ', $programs) . '.';
        } elseif (! empty($registrant['subscription'])) {
            $lines[] = 'Recibirás los avisos de los programas que tienes habilitados con tu suscripción activa.';
        }

        $lines[] = 'Si quieres dejar de recibirlos, escribe /baja.';

        return implode("\n\n", $lines);
    }

    private function startHint(): string
    {
        return 'Para activar los avisos en este chat escribe /start y luego tu número de documento (DNI).';
    }

    private function helpMessage(): string
    {
        return "Soy el bot de avisos de la institución.\n\n"
            . "Para activar los avisos en este chat: escribe /start y luego tu número de documento (DNI).\n\n"
            . "Para dejar de recibir avisos: /baja.";
    }
}
