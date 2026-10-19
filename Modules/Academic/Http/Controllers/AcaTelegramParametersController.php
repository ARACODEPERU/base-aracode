<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Services\SchoolTelegramNoticeService;

/**
 * Pantalla Académico > Colegio > Parámetros Telegram.
 *
 * Exclusiva para el rol admin (permiso aca_school_telegram_configuracion).
 * Administra el token / ID del bot de Telegram (SC-00033), los interruptores
 * por evento (entrada, salida, tardanza, salida anticipada, solo apoderado
 * principal), las plantillas de mensaje, el límite diario, la pausa entre
 * envíos, el registro del webhook con los comandos del bot y la guía paso a
 * paso para obtener el token en @BotFather.
 */
class AcaTelegramParametersController extends Controller
{
    /** Centinela de la máscara: si llega este valor no se pisa el token guardado. */
    private const SECRET_PLACEHOLDER = '********';

    private const EVENTS = [
        SchoolTelegramNoticeService::EVENT_ENTRY,
        SchoolTelegramNoticeService::EVENT_EXIT,
    ];

    public function __construct(
        private readonly SchoolTelegramNoticeService $notices,
    ) {
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $bot = $this->notices->bot();
        $configured = $this->notices->isConfigured();

        return Inertia::render('Academic::Parametros/Telegram', [
            'channel' => array_merge($this->notices->channelStatus(), [
                'parameterCodes' => $this->notices->parameterCodes(),
            ]),
            'settings' => $this->notices->settings(),
            'bot' => [
                'configured' => $configured,
                'username' => $configured ? $bot->cachedUsername() : null,
                'link' => $configured ? $bot->cachedRegistrationLink() : null,
                'webhookUrl' => $bot->webhookUrl(),
                'commands' => $bot->defaultCommands(),
            ],
            'variables' => $this->notices->variables(),
            'deliveries' => Schema::hasTable('aca_school_notice_deliveries')
                ? $this->notices->recentDeliveries()
                : [],
            'subscriptions' => Schema::hasTable('aca_school_telegram_subscriptions')
                ? $this->notices->recentSubscriptions()
                : [],
            'parametersUrl' => route('parameters'),
        ]);
    }

    /**
     * Guarda la configuración del canal Telegram del colegio y sus avisos.
     */
    public function update(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'token' => ['nullable', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
            'notify_entry' => ['required', 'boolean'],
            'notify_exit' => ['required', 'boolean'],
            'notify_late' => ['required', 'boolean'],
            'notify_early_exit' => ['required', 'boolean'],
            'primary_only' => ['required', 'boolean'],
            'daily_limit' => ['nullable', 'integer', 'min:0', 'max:999'],
            'interval_ms' => ['nullable', 'integer', 'min:0', 'max:60000'],
            'entry_message' => ['nullable', 'string', 'max:480'],
            'exit_message' => ['nullable', 'string', 'max:480'],
        ]);

        if ($this->isNewSecretValue($data['token'] ?? null)) {
            $this->notices->saveParameter('token_parameter', 'SC-00033', trim((string) $data['token']), 'tx');
        }

        $this->notices->saveParameter('enabled_parameter', 'SC-00034', $this->flagValue($data['active']), 'chx');
        $this->notices->saveParameter('entry_parameter', 'SC-00035', $this->flagValue($data['notify_entry']), 'chx');
        $this->notices->saveParameter('exit_parameter', 'SC-00036', $this->flagValue($data['notify_exit']), 'chx');
        $this->notices->saveParameter('late_parameter', 'SC-00037', $this->flagValue($data['notify_late']), 'chx');
        $this->notices->saveParameter('early_exit_parameter', 'SC-00038', $this->flagValue($data['notify_early_exit']), 'chx');
        $this->notices->saveParameter('primary_only_parameter', 'SC-00041', $this->flagValue($data['primary_only']), 'chx');

        $this->notices->saveParameter('entry_message_parameter', 'SC-00039', $this->blankToNull($data['entry_message'] ?? null), 'tx');
        $this->notices->saveParameter('exit_message_parameter', 'SC-00040', $this->blankToNull($data['exit_message'] ?? null), 'tx');
        $this->notices->saveParameter('daily_limit_parameter', 'SC-00042', (string) max(0, (int) ($data['daily_limit'] ?? 0)), 'tx');
        $this->notices->saveParameter('interval_ms_parameter', 'SC-00043', (string) max(0, min(60000, (int) ($data['interval_ms'] ?? 500))), 'tx');

        return back()->with('message', 'Parámetros de los avisos por Telegram guardados.');
    }

    /**
     * Registra el webhook del bot escolar en Telegram y publica sus comandos.
     */
    public function webhook(Request $request)
    {
        $this->ensureAdmin($request);

        $bot = $this->notices->bot();

        if (! $bot->isConfigured()) {
            throw ValidationException::withMessages([
                'webhook' => 'Falta configurar el Token / ID del bot en el parámetro SC-00033. Guárdalo primero.',
            ]);
        }

        try {
            $url = $bot->registerWebhook(true);
            $username = $bot->username(true);
            $link = $bot->registrationLink(false);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'webhook' => 'No se pudo conectar el webhook con Telegram: ' . $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Webhook y comandos registrados en Telegram correctamente.',
            'url' => $url,
            'username' => $username,
            'link' => $link,
        ]);
    }

    /**
     * Consulta el usuario público del bot y devuelve el enlace de registro (https://t.me/<bot>).
     */
    public function link(Request $request)
    {
        $this->ensureAdmin($request);

        $bot = $this->notices->bot();

        if (! $bot->isConfigured()) {
            throw ValidationException::withMessages([
                'link' => 'Falta configurar el Token / ID del bot en el parámetro SC-00033.',
            ]);
        }

        $username = $bot->username(true);

        if ($username === null) {
            throw ValidationException::withMessages([
                'link' => 'No se pudo obtener el usuario del bot en Telegram. Verifica que el token sea válido.',
            ]);
        }

        return response()->json([
            'message' => 'Enlace del bot obtenido correctamente.',
            'username' => $username,
            'link' => $bot->registrationLink(false),
        ]);
    }

    /**
     * Vista previa del mensaje con datos de ejemplo.
     */
    public function preview(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'event' => ['nullable', Rule::in(self::EVENTS)],
            'message' => ['nullable', 'string', 'max:480'],
        ]);

        return response()->json([
            'text' => $this->notices->renderSampleText(
                $data['event'] ?? SchoolTelegramNoticeService::EVENT_ENTRY,
                $data['message'] ?? null
            ),
        ]);
    }

    /**
     * Envía un mensaje de prueba por Telegram al Chat ID indicado.
     */
    public function test(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'chat_id' => ['required', 'string', 'max:64'],
            'event' => ['nullable', Rule::in(self::EVENTS)],
            'message' => ['nullable', 'string', 'max:480'],
        ]);

        if (! $this->notices->isConfigured()) {
            throw ValidationException::withMessages([
                'chat_id' => 'Falta configurar el Token / ID del bot de Telegram (SC-00033).',
            ]);
        }

        if (! $this->notices->isChannelActive()) {
            throw ValidationException::withMessages([
                'chat_id' => 'El canal está desactivado: enciende el interruptor Activo (SC-00034) y guarda los parámetros antes de probar.',
            ]);
        }

        $chatId = trim((string) $data['chat_id']);
        $event = $data['event'] ?? SchoolTelegramNoticeService::EVENT_ENTRY;
        $text = $this->notices->renderSampleText($event, $data['message'] ?? null);

        try {
            $this->notices->sendTo($chatId, $text);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'chat_id' => 'No se pudo enviar el mensaje de Telegram: ' . $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Mensaje de prueba enviado al Chat ID ' . $chatId . '.',
            'text' => $text,
        ]);
    }

    /**
     * Garantiza que solo el rol admin acceda a la pantalla y acciones cuando
     * hay un usuario autenticado con soporte de roles.
     */
    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        if ($user && method_exists($user, 'hasRole') && ! $user->hasRole('admin')) {
            abort(403, 'Solo el rol admin tiene acceso a Parámetros Telegram del colegio.');
        }
    }

    private function flagValue(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isNewSecretValue(?string $value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && $value !== self::SECRET_PLACEHOLDER;
    }
}
