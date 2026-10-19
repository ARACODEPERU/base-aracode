<?php

namespace Modules\Academic\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\TelegramWebhookGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\AcaSchoolTelegramSession;
use Modules\Academic\Services\SchoolTelegramBotService;
use Modules\Academic\Services\SchoolTelegramRegistrationService;

/**
 * Webhook público del bot de Telegram para los avisos de asistencia escolar.
 *
 * Comandos soportados:
 *   - /registrar (o /start): inicia el registro validando primero el DNI del
 *     padre, madre o apoderado y luego el DNI de su hijo estudiante del
 *     colegio. Al registrar un hijo pregunta si tiene otro hijo en el colegio;
 *     si responde "Sí" pide el DNI del otro hijo y tras registrarlo vuelve a
 *     preguntar si hay otro hijo más.
 *   - /baja (o /stop): da de baja todas las suscripciones del chat para que ya
 *     no reciba ningún mensaje.
 *   - /chatid (o /miid): devuelve el identificador del chat (Mi Chat ID).
 *   - /info (o /informacion, /ayuda): muestra el nombre del colegio, dirección,
 *     teléfono, correo, año lectivo, horarios de jornada y los hijos
 *     actualmente registrados en este chat.
 *
 * Botones inline: además del texto, el bot ofrece botones que ejecutan los
 * comandos principales (/registrar, /info, /chatid y /baja) y que responden a
 * la pregunta de si tiene otro hijo. Cada pulsación llega como
 * callback_query; se responde siempre con answerCallbackQuery para apagar el
 * indicador de carga y luego se ejecuta la misma acción que el comando o el
 * texto equivalente, de modo que ambos caminos compartan la lógica.
 */
class SchoolTelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration
    ): JsonResponse {
        $secret = $bot->secret();
        $received = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret === null) {
            return response()->json(['ok' => true, 'message' => 'El bot escolar de Telegram no tiene token configurado.'], 200);
        }

        if (! hash_equals($secret, $received)) {
            return response()->json(['message' => 'Secreto de webhook inválido.'], 403);
        }

        $update = $request->all();

        // Freno anti-abuso: deduplica los reintentos de Telegram y descarta el
        // exceso de mensajes de un mismo chat o del bot completo. Se responde
        // 200 para que Telegram no reintente lo descartado.
        $guard = new TelegramWebhookGuard(
            'academic',
            (int) config('academic.school_notices.telegram.webhook_per_chat_per_minute', 20),
            (int) config('academic.school_notices.telegram.webhook_global_per_minute', 600)
        );

        if (! $guard->allows($update, $this->chatId($update))) {
            return response()->json(['ok' => true]);
        }

        // Pulsación de un botón inline.
        $callback = $update['callback_query'] ?? null;

        if (is_array($callback)) {
            $chatId = trim((string) ($callback['message']['chat']['id'] ?? ''));

            if ($chatId !== '') {
                try {
                    $this->handleCallback(
                        $bot,
                        $registration,
                        $chatId,
                        trim((string) ($callback['id'] ?? '')),
                        trim((string) ($callback['data'] ?? '')),
                        $callback['from']['username'] ?? null,
                        $callback['from']['first_name'] ?? null
                    );
                } catch (\Throwable $exception) {
                    Log::error('Bot escolar de Telegram: fallo al procesar un botón', [
                        'chat_id' => $chatId,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            return response()->json(['ok' => true]);
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;

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
            Log::error('Bot escolar de Telegram: fallo al procesar un mensaje', [
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * chat_id del update, venga como mensaje o como pulsación de un botón.
     *
     * Se usa para aplicar el límite por chat antes de procesar nada.
     *
     * @param array<string, mixed> $update
     */
    private function chatId(array $update): string
    {
        $message = $update['callback_query']['message']
            ?? $update['message']
            ?? $update['edited_message']
            ?? null;

        return is_array($message) ? trim((string) ($message['chat']['id'] ?? '')) : '';
    }

    /**
     * Enruta el mensaje según el comando o el paso conversacional activo.
     */
    private function handleMessage(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        string $chatId,
        string $text,
        ?string $username,
        ?string $firstName
    ): void {
        $registration->touch($chatId);
        $command = $this->command($text);

        if (in_array($command, ['/registrar', '/start', '/registro'], true)) {
            $this->handleStartRegistration($bot, $registration, $chatId, $username, $firstName);

            return;
        }

        if (in_array($command, ['/baja', '/stop', '/cancelar', '/dardebaja'], true)) {
            $this->handleOptOut($bot, $registration, $chatId);

            return;
        }

        if (in_array($command, ['/chatid', '/miid', '/michatid'], true)) {
            $this->handleChatId($bot, $chatId);

            return;
        }

        if (in_array($command, ['/info', '/informacion', '/colegio', '/ayuda', '/help'], true)) {
            $this->handleInfo($bot, $registration, $chatId);

            return;
        }

        $session = $registration->sessionFor($chatId);

        if ($session === null) {
            $this->sendMenuHint($bot, $registration, $chatId);

            return;
        }

        match ($session->step) {
            SchoolTelegramRegistrationService::STEP_GUARDIAN_DNI => $this->handleGuardianDni($bot, $registration, $session, $chatId, $text),
            SchoolTelegramRegistrationService::STEP_STUDENT_DNI => $this->handleStudentDni($bot, $registration, $session, $chatId, $text, $username, $firstName),
            SchoolTelegramRegistrationService::STEP_ANOTHER_CHILD => $this->handleAnotherChild($bot, $registration, $session, $chatId, $text),
            default => $this->handleGuardianDni($bot, $registration, $session, $chatId, $text),
        };
    }

    /**
     * Atiende la pulsación de un botón inline.
     *
     * Los datos viajan en callback_data con el formato "accion:valor":
     *   - another:yes / another:no responde la pregunta del registro.
     *   - cmd:<comando> ejecuta uno de los comandos principales.
     *   - baja:si / baja:no confirma (o cancela) la baja del chat.
     */
    private function handleCallback(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        string $chatId,
        string $callbackQueryId,
        string $data,
        ?string $username,
        ?string $firstName
    ): void {
        $registration->touch($chatId);

        [$action, $value] = array_pad(explode(':', $data, 2), 2, '');
        $action = strtolower(trim($action));
        $value = strtolower(trim($value));

        if ($action === 'cmd') {
            $this->acknowledge($bot, $callbackQueryId);
            $this->executeCommand($bot, $registration, $chatId, $value, $username, $firstName);

            return;
        }

        if ($action === 'another') {
            $session = $registration->sessionFor($chatId);

            if ($session === null || $session->step !== SchoolTelegramRegistrationService::STEP_ANOTHER_CHILD) {
                $this->acknowledge(
                    $bot,
                    $callbackQueryId,
                    'La sesión de registro ya no está activa. Escribe /registrar para comenzar de nuevo.',
                    true
                );

                return;
            }

            if (! in_array($value, ['yes', 'no'], true)) {
                $this->acknowledge($bot, $callbackQueryId, 'Opción no reconocida.', true);

                return;
            }

            $this->acknowledge($bot, $callbackQueryId, $value === 'yes' ? 'Sí, tengo otro hijo(a)' : 'No, finalizar');
            $this->answerAnotherChild($bot, $registration, $session, $chatId, $value);

            return;
        }

        if ($action === 'baja') {
            if ($value === 'si') {
                $this->acknowledge($bot, $callbackQueryId, 'Registro dado de baja');
                $this->handleOptOut($bot, $registration, $chatId);

                return;
            }

            $this->acknowledge($bot, $callbackQueryId, 'No se realizaron cambios');
            $bot->sendFormatted(
                $chatId,
                "👍 No se realizaron cambios. Tu registro sigue activo.",
                true,
                $this->mainMenuKeyboard()
            );

            return;
        }

        $this->acknowledge($bot, $callbackQueryId, 'Opción no reconocida.', true);
    }

    /**
     * Ejecuta un comando principal como si se hubiera escrito en el chat.
     */
    private function executeCommand(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        string $chatId,
        string $command,
        ?string $username,
        ?string $firstName
    ): void {
        if ($command === 'baja') {
            // La baja es irreversible desde el chat: se pide confirmación.
            $bot->sendFormatted(
                $chatId,
                "🚪 <b>¿Confirmas que quieres dejar de recibir los avisos de asistencia?</b>\n\n"
                . "Dejarás de recibir los mensajes de entrada y salida de todos los estudiantes vinculados a este chat.",
                true,
                $this->keyboard([
                    [
                        ['text' => '✅ Sí, darme de baja', 'callback_data' => 'baja:si'],
                        ['text' => '↩️ Cancelar', 'callback_data' => 'baja:no'],
                    ],
                ])
            );

            return;
        }

        if (! in_array($command, ['registrar', 'start', 'registro', 'chatid', 'miid', 'michatid', 'info', 'informacion', 'colegio'], true)) {
            $bot->sendFormatted(
                $chatId,
                "🤔 No reconozco esa opción. Elige una de las siguientes:",
                true,
                $this->mainMenuKeyboard()
            );

            return;
        }

        $this->handleMessage($bot, $registration, $chatId, '/' . $command, $username, $firstName);
    }

    /**
     * Envía la respuesta silenciosa (o con aviso) del botón pulsado.
     *
     * Un fallo al responder no debe impedir que se ejecute la acción.
     */
    private function acknowledge(
        SchoolTelegramBotService $bot,
        string $callbackQueryId,
        ?string $text = null,
        bool $alert = false
    ): void {
        try {
            $bot->answerCallbackQuery($callbackQueryId, $text, $alert);
        } catch (\Throwable $exception) {
            Log::warning('Bot escolar de Telegram: no se pudo responder el callback_query', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Teclado inline de comandos principales.
     *
     * @return array<string, string>
     */
    private function mainMenuKeyboard(): array
    {
        return $this->keyboard([
            [
                ['text' => '📝 Registrarme', 'callback_data' => 'cmd:registrar'],
                ['text' => 'ℹ️ Info del colegio', 'callback_data' => 'cmd:info'],
            ],
            [
                ['text' => '🆔 Mi Chat ID', 'callback_data' => 'cmd:chatid'],
                ['text' => '🚪 Darme de baja', 'callback_data' => 'cmd:baja'],
            ],
        ]);
    }

    /**
     * Teclado inline para responder si tiene otro hijo en el colegio.
     *
     * @return array<string, string>
     */
    private function anotherChildKeyboard(): array
    {
        return $this->keyboard([
            [
                ['text' => '✅ Sí, tengo otro hijo(a)', 'callback_data' => 'another:yes'],
                ['text' => '🏁 No, finalizar', 'callback_data' => 'another:no'],
            ],
        ]);
    }

    /**
     * Prepara el reply_markup inline en el formato que espera Telegram.
     *
     * Se envía como cadena JSON porque la petición viaja como formulario.
     *
     * @param  array<int, array<int, array{text: string, callback_data: string}>> $rows
     * @return array<string, string>
     */
    private function keyboard(array $rows): array
    {
        return [
            'reply_markup' => (string) json_encode(
                ['inline_keyboard' => $rows],
                JSON_UNESCAPED_UNICODE
            ),
        ];
    }

    /**
     * Inicia el proceso de registro pidiendo primero el DNI del padre, madre o apoderado.
     */
    private function handleStartRegistration(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        string $chatId,
        ?string $username,
        ?string $firstName
    ): void {
        $registration->beginRegistration($chatId, $username, $firstName);
        $schoolInfo = $registration->schoolInfo();
        $currentChildren = $registration->activeSubscriptionsForChat($chatId);

        $extraNote = '';

        if ($currentChildren !== []) {
            $names = array_map(
                fn (array $item) => '• ' . $item['name'] . ($item['section'] !== '' ? " ({$item['section']})" : ''),
                $currentChildren
            );
            $extraNote = "\n\n📋 <b>Actualmente recibes avisos de:</b>\n" . implode("\n", $names) . "\n";
        }

        $reply = "👋 ¡Bienvenido(a) al sistema de avisos de asistencia de <b>{$schoolInfo['name']}</b>!{$extraNote}\n"
            . "Vamos a registrar tu chat para que recibas notificaciones cuando tu hijo(a) ingrese y salga del colegio.\n\n"
            . "🪪 <b>Paso 1:</b> Por favor, escribe tu <b>número de DNI (padre, madre o apoderado)</b>, solo números.";

        $bot->sendFormatted($chatId, $reply, true);
    }

    /**
     * Paso 1: valida el DNI del padre, madre o apoderado y pide el DNI del hijo.
     */
    private function handleGuardianDni(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        AcaSchoolTelegramSession $session,
        string $chatId,
        string $text
    ): void {
        $document = $this->normalizeDocument($text);

        if ($document === '') {
            $this->rejectAttempt(
                $bot,
                $registration,
                $session,
                $chatId,
                "🤔 No reconocí ese texto como un número de DNI válido.\n\nPor favor, escribe solo el <b>DNI del padre, madre o apoderado</b> (sin espacios ni guiones)."
            );

            return;
        }

        $guardian = $registration->resolveGuardianByDocument($document);

        if ($guardian === null) {
            $this->rejectAttempt(
                $bot,
                $registration,
                $session,
                $chatId,
                "🔎 No encontramos ningún padre, madre o apoderado registrado en el colegio con el DNI <b>{$document}</b>.\n\nVerifica el número y vuelve a escribirlo, o comunícate con la secretaría del colegio."
            );

            return;
        }

        $context = $registration->context($session);
        $context['guardian_person_id'] = $guardian['person_id'];
        $context['guardian_name'] = $guardian['name'];
        $context['guardian_document'] = $guardian['document'];
        $context['registered_students'] = $context['registered_students'] ?? [];

        $registration->advanceStep($session, SchoolTelegramRegistrationService::STEP_STUDENT_DNI, $context);

        $reply = "✅ ¡Hola, <b>{$guardian['name']}</b>! Hemos validado tus datos como apoderado(a).\n\n"
            . "🎒 <b>Paso 2:</b> Ahora escribe el <b>número de DNI de tu hijo(a) estudiante</b> del colegio.";

        $bot->sendFormatted($chatId, $reply, true);
    }

    /**
     * Paso 2: valida el DNI del hijo estudiante, lo vincula al chat y pregunta
     * si tiene otro hijo en el colegio.
     */
    private function handleStudentDni(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        AcaSchoolTelegramSession $session,
        string $chatId,
        string $text,
        ?string $username,
        ?string $firstName
    ): void {
        $document = $this->normalizeDocument($text);

        if ($document === '') {
            $this->rejectAttempt(
                $bot,
                $registration,
                $session,
                $chatId,
                "🤔 No reconocí ese texto como un número de DNI válido.\n\nPor favor, escribe solo el <b>DNI de tu hijo(a) estudiante</b> (sin espacios ni guiones)."
            );

            return;
        }

        $context = $registration->context($session);
        $guardianPersonId = (int) ($context['guardian_person_id'] ?? 0);

        if ($guardianPersonId <= 0) {
            $registration->closeSession($chatId);
            $bot->sendFormatted($chatId, "⚠️ La sesión de registro se reinició. Escribe /registrar para comenzar de nuevo.", true);

            return;
        }

        $resolution = $registration->resolveStudentForGuardian($guardianPersonId, $document);

        if ($resolution['status'] === 'not_found') {
            $this->rejectAttempt(
                $bot,
                $registration,
                $session,
                $chatId,
                "🔎 No encontramos ningún estudiante activo en el colegio con el DNI <b>{$document}</b>.\n\nVerifica el número de DNI de tu hijo(a) y vuelve a escribirlo."
            );

            return;
        }

        if ($resolution['status'] === 'not_linked') {
            $this->rejectAttempt(
                $bot,
                $registration,
                $session,
                $chatId,
                "🔒 El estudiante con DNI <b>{$document}</b> no figura vinculado a tu ficha de padre, madre o apoderado en el sistema del colegio.\n\nVerifica el DNI o solicita en secretaría que te asocien como su apoderado(a)."
            );

            return;
        }

        $studentId = (int) $resolution['student_id'];
        $studentName = (string) $resolution['name'];
        $sectionLabel = trim((string) ($resolution['section'] ?? ''));

        $registration->subscribe(
            $guardianPersonId,
            $studentId,
            $resolution['school_id'] ?? null,
            $chatId,
            $username,
            $firstName
        );

        $registeredStudents = is_array($context['registered_students'] ?? null)
            ? $context['registered_students']
            : [];

        $registeredStudents[$studentId] = [
            'student_id' => $studentId,
            'name' => $studentName,
            'section' => $sectionLabel,
        ];

        $context['registered_students'] = $registeredStudents;

        $registration->advanceStep($session, SchoolTelegramRegistrationService::STEP_ANOTHER_CHILD, $context);

        $sectionLine = $sectionLabel !== '' ? "\n📚 Sección: <b>{$sectionLabel}</b>" : '';

        $reply = "🎉 ¡Registro exitoso! Quedaste vinculado(a) para recibir las notificaciones de entrada y salida de:\n\n"
            . "👦👧 Estudiante: <b>{$studentName}</b>{$sectionLine}\n\n"
            . "❓ <b>¿Tienes otro hijo o hija en el colegio o escuela que quieras registrar?</b>\n"
            . "Responde <b>SÍ</b> para registrar el DNI de otro hijo(a) o <b>NO</b> para finalizar.";

        $bot->sendFormatted($chatId, $reply, true, $this->anotherChildKeyboard());
    }

    /**
     * Paso 3: procesa la respuesta (Sí / No) sobre si tiene otro hijo en el colegio.
     */
    private function handleAnotherChild(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        AcaSchoolTelegramSession $session,
        string $chatId,
        string $text
    ): void {
        $answer = $this->parseYesNo($text);

        if ($answer !== null) {
            $this->answerAnotherChild($bot, $registration, $session, $chatId, $answer);

            return;
        }

        // Si el usuario escribió directamente el DNI del otro hijo en lugar de "sí", lo procesamos como DNI del siguiente hijo.
        if ($this->normalizeDocument($text) !== '') {
            $context = $registration->context($session);
            $session = $registration->advanceStep($session, SchoolTelegramRegistrationService::STEP_STUDENT_DNI, $context);

            $this->handleStudentDni(
                $bot,
                $registration,
                $session,
                $chatId,
                $text,
                $session->telegram_username,
                $session->telegram_first_name
            );

            return;
        }

        $bot->sendFormatted(
            $chatId,
            "🤔 Por favor, responde <b>SÍ</b> si tienes otro hijo(a) en el colegio para registrar su DNI, o <b>NO</b> para terminar el registro.",
            true,
            $this->anotherChildKeyboard()
        );
    }

    /**
     * Resuelve la respuesta (Sí / No) sobre si tiene otro hijo en el colegio.
     *
     * Se usa tanto para el texto escrito como para los botones inline.
     */
    private function answerAnotherChild(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        AcaSchoolTelegramSession $session,
        string $chatId,
        string $answer
    ): void {
        if ($answer === 'yes') {
            $context = $registration->context($session);
            $registration->advanceStep($session, SchoolTelegramRegistrationService::STEP_STUDENT_DNI, $context);

            $bot->sendFormatted(
                $chatId,
                "🎒 ¡Perfecto! Escribe ahora el <b>número de DNI de tu otro hijo(a) estudiante</b> del colegio.",
                true
            );

            return;
        }

        $registration->closeSession($chatId);
        $subscriptions = $registration->activeSubscriptionsForChat($chatId);

        $lines = array_map(
            fn (array $sub) => '• <b>' . $sub['name'] . '</b>' . ($sub['section'] !== '' ? " ({$sub['section']})" : ''),
            $subscriptions
        );

        $listBlock = $lines !== [] ? "\n\n👨‍👩‍👧‍👦 <b>Estudiantes registrados en este chat:</b>\n" . implode("\n", $lines) : '';

        $reply = "✅ ¡Todo listo! Tu registro ha finalizado correctamente.{$listBlock}\n\n"
            . "🔔 A partir de ahora recibirás un mensaje por aquí cada vez que se registre la entrada o la salida en portería.\n\n"
            . "Si en el futuro deseas dejar de recibir mensajes, escribe /baja.";

        $bot->sendFormatted($chatId, $reply, true, $this->mainMenuKeyboard());
    }

    /**
     * /baja: desactiva todas las suscripciones del chat para que no reciba más avisos.
     */
    private function handleOptOut(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        string $chatId
    ): void {
        $registration->closeSession($chatId);
        $count = $registration->unsubscribeAll($chatId);

        if ($count > 0) {
            $bot->sendFormatted(
                $chatId,
                "👍 Se ha dado de baja tu registro en este chat ({$count} estudiante(s) desvinculado(s)).\n\n"
                . "Ya no recibirás notificaciones de entrada ni de salida.\n"
                . "Si deseas volver a registrarte más adelante, escribe /registrar.",
                true,
                $this->mainMenuKeyboard()
            );

            return;
        }

        $bot->sendFormatted(
            $chatId,
            "ℹ️ Este chat no tenía estudiantes activos registrados.\n\nSi deseas activar las notificaciones de asistencia, escribe /registrar.",
            true,
            $this->mainMenuKeyboard()
        );
    }

    /**
     * /chatid: devuelve el identificador del chat actual.
     */
    private function handleChatId(SchoolTelegramBotService $bot, string $chatId): void
    {
        $reply = "🆔 <b>Mi Chat ID</b>\n\n"
            . "El identificador de este chat de Telegram es:\n"
            . "<code>{$chatId}</code>\n\n"
            . "Puedes copiarlo y compartirlo con la administración del colegio si necesitan realizar una prueba o verificar tu conexión.";

        $bot->sendFormatted($chatId, $reply, true, $this->mainMenuKeyboard());
    }

    /**
     * /info: devuelve la información del colegio, jornadas, hijos suscritos y comandos disponibles.
     */
    private function handleInfo(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        string $chatId
    ): void {
        $info = $registration->schoolInfo();
        $subscriptions = $registration->activeSubscriptionsForChat($chatId);

        $lines = [
            "🏫 <b>{$info['name']}</b>",
        ];

        if ($info['modular_code'] !== '') {
            $lines[] = "📌 Código modular: <b>{$info['modular_code']}</b>" . ($info['type'] !== '' ? " ({$info['type']})" : '');
        } elseif ($info['type'] !== '') {
            $lines[] = "📌 Gestión: <b>{$info['type']}</b>";
        }

        if ($info['year'] !== '') {
            $lines[] = "📅 Año escolar activo: <b>{$info['year']}</b>";
        }

        if ($info['address'] !== '') {
            $lines[] = "📍 Dirección: {$info['address']}";
        }

        if ($info['phone'] !== '') {
            $lines[] = "📞 Teléfono: {$info['phone']}";
        }

        if ($info['email'] !== '') {
            $lines[] = "✉️ Correo: {$info['email']}";
        }

        if ($info['journeys'] !== []) {
            $lines[] = "\n🕒 <b>Horarios de jornada:</b>";
            foreach ($info['journeys'] as $journeyLine) {
                $lines[] = "• {$journeyLine}";
            }
        }

        if ($subscriptions !== []) {
            $lines[] = "\n👨‍👩‍👧‍👦 <b>Estudiantes vinculados a este chat:</b>";
            foreach ($subscriptions as $sub) {
                $lines[] = "• <b>{$sub['name']}</b>" . ($sub['section'] !== '' ? " ({$sub['section']})" : '');
            }
        } else {
            $lines[] = "\nℹ️ <i>Aún no tienes estudiantes vinculados en este chat. Usa /registrar para activarlos.</i>";
        }

        $lines[] = "\n🤖 <b>Comandos disponibles:</b>";
        $lines[] = "/registrar – Registrar tu DNI de apoderado y el de tu(s) hijo(s)";
        $lines[] = "/baja – Dar de baja y dejar de recibir notificaciones";
        $lines[] = "/chatid – Ver Mi Chat ID";
        $lines[] = "/info – Ver esta información del colegio";

        $bot->sendFormatted($chatId, implode("\n", $lines), true, $this->mainMenuKeyboard());
    }

    /**
     * Respuesta de ayuda cuando alguien escribe un mensaje suelto sin sesión activa.
     */
    private function sendMenuHint(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        string $chatId
    ): void {
        $info = $registration->schoolInfo();

        $reply = "👋 Hola, soy el bot de asistencia de <b>{$info['name']}</b>.\n\n"
            . "Elige una de las siguientes opciones:\n\n"
            . "🔹 /registrar – Registrar tu DNI y el de tu(s) hijo(s) para recibir avisos de entrada y salida.\n"
            . "🔹 /baja – Dar de baja el registro para dejar de recibir mensajes.\n"
            . "🔹 /chatid – Consultar Mi Chat ID.\n"
            . "🔹 /info – Ver información del colegio y tus hijos vinculados.";

        $bot->sendFormatted($chatId, $reply, true, $this->mainMenuKeyboard());
    }

    /**
     * Cuenta un intento fallido y cierra la sesión si se supera el máximo.
     */
    private function rejectAttempt(
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        AcaSchoolTelegramSession $session,
        string $chatId,
        string $message
    ): void {
        $attempts = $registration->countAttempt($session);
        $max = $registration->maxAttempts();

        if ($attempts >= $max) {
            $registration->closeSession($chatId);
            $bot->sendFormatted(
                $chatId,
                "🚫 Has superado el número máximo de intentos ({$max}).\n\nVerifica los datos y escribe /registrar cuando quieras volver a intentarlo.",
                true,
                $this->mainMenuKeyboard()
            );

            return;
        }

        $bot->sendFormatted(
            $chatId,
            "{$message}\n\n<i>(Intento {$attempts} de {$max})</i>",
            true
        );
    }

    /**
     * Extrae el comando en minúsculas y sin el sufijo @NombreDelBot.
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
     * Normaliza un número de documento (DNI / CE): sin espacios ni guiones y con al menos un dígito.
     */
    private function normalizeDocument(string $text): string
    {
        $document = strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $text));

        if (! preg_match('/\d/', $document)) {
            return '';
        }

        return strlen($document) >= 5 && strlen($document) <= 20 ? $document : '';
    }

    /**
     * Reconoce respuestas afirmativas ("sí", "si", "claro", "1") o negativas ("no", "ninguno", "finalizar").
     */
    private function parseYesNo(string $text): ?string
    {
        $normalized = mb_strtolower(trim($text), 'UTF-8');
        $normalized = str_replace(['í', 'ì', 'ï', 'î'], 'i', $normalized);
        $normalized = trim((string) preg_replace('/[^a-z0-9\s]/u', '', $normalized));

        if (in_array($normalized, ['si', 's', 'yes', 'y', 'claro', 'otro', 'registrar otro', 'si tengo', 'si tengo otro'], true)) {
            return 'yes';
        }

        if (in_array($normalized, ['no', 'n', 'ninguno', 'nada', 'listo', 'finalizar', 'terminar', 'no tengo', 'solo uno'], true)) {
            return 'no';
        }

        return null;
    }
}
