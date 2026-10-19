<?php

namespace Modules\Security\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Services\ErrorAlertService;
use Modules\Security\Services\ErrorLogService;

/**
 * Pantalla "Alertas" del módulo Security.
 *
 * Administra los chat_id de Telegram que reciben los avisos, los ajustes
 * (interruptor general, un interruptor por tipo de aviso, nivel mínimo y
 * ventana anti-duplicados) y el envío de una alerta de prueba.
 *
 * Los tipos de aviso salen de SecurityAlertSetting::topics(): agregar uno nuevo
 * no requiere tocar la validación ni el guardado de esta pantalla.
 */
class SecurityAlertController extends Controller
{
    public function __construct(
        private readonly ErrorAlertService $alerts,
        private readonly TelegramBotService $bot,
        private readonly ErrorLogService $errorLogs,
    ) {
    }

    public function index()
    {
        $settings = SecurityAlertSetting::current();

        return Inertia::render('Security::Alerts/Index', [
            'settings' => $this->settingsPayload($settings),
            'topics' => $this->topicsPayload($settings),
            'levels' => SecurityAlertSetting::selectableLevels(),
            'logStats' => $this->logStats(),
            'recipients' => SecurityAlertRecipient::orderBy('id')
                ->get(['id', 'name', 'chat_id', 'is_active'])
                ->map(fn (SecurityAlertRecipient $recipient) => [
                    'id' => $recipient->id,
                    'name' => $recipient->name,
                    'chat_id' => $recipient->chat_id,
                    'is_active' => (bool) $recipient->is_active,
                ])
                ->values(),
            'botConfigured' => $this->bot->isConfigured(),
            'preview' => $this->alerts->previewMessage(),
        ]);
    }

    public function storeRecipient(Request $request)
    {
        $validated = $this->validateRecipient($request);

        $recipient = SecurityAlertRecipient::create([
            'name' => $validated['name'] ?? null,
            'chat_id' => $this->normalizeChatId($validated['chat_id']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->alerts->forgetCache();

        return response()->json([
            'message' => 'Destinatario agregado. Recibirá las próximas alertas.',
            'recipient' => $this->recipientPayload($recipient),
        ], 201);
    }

    public function updateRecipient(Request $request, int $id)
    {
        $recipient = SecurityAlertRecipient::findOrFail($id);
        $validated = $this->validateRecipient($request, $recipient->id);

        $recipient->update([
            'name' => $validated['name'] ?? null,
            'chat_id' => $this->normalizeChatId($validated['chat_id']),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->alerts->forgetCache();

        return response()->json([
            'message' => 'Destinatario actualizado.',
            'recipient' => $this->recipientPayload($recipient->fresh()),
        ]);
    }

    public function destroyRecipient(int $id)
    {
        $recipient = SecurityAlertRecipient::findOrFail($id);
        $recipient->delete();

        $this->alerts->forgetCache();

        return response()->json([
            'message' => 'Destinatario eliminado. Ya no recibirá alertas.',
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'min_level' => ['required', Rule::in(SecurityAlertSetting::selectableLevels())],
            'cooldown_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'store_context' => ['nullable', 'boolean'],
            'retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'purge_enabled' => ['nullable', 'boolean'],
        ] + $this->topicRules());

        $settings = SecurityAlertSetting::current();
        $settings->fill([
            'enabled' => $validated['enabled'],
            'min_level' => $validated['min_level'],
            'cooldown_minutes' => $validated['cooldown_minutes'],
            // Los ajustes de contexto son opcionales: si no llegan se conserva
            // lo que ya había (pantallas o clientes antiguos).
            'store_context' => $validated['store_context'] ?? $settings->storesContext(),
            'retention_days' => $validated['retention_days'] ?? $settings->retentionDays(),
            'purge_enabled' => $validated['purge_enabled'] ?? $settings->autoPurge(),
        ]);

        // Cada interruptor llega dentro de `topics`; el que no llegue conserva su
        // valor, para que una pantalla antigua no apague avisos sin querer.
        foreach (array_keys(SecurityAlertSetting::topics()) as $topic) {
            $settings->setAttribute(
                $topic . '_enabled',
                $validated['topics'][$topic] ?? $settings->topicEnabled($topic)
            );
        }

        $settings->save();

        $this->alerts->forgetCache();

        return response()->json([
            'message' => 'Ajustes guardados.',
            'settings' => $this->settingsPayload($settings),
            'topics' => $this->topicsPayload($settings),
            'logStats' => $this->logStats(),
            'preview' => $this->alerts->previewMessage(),
        ]);
    }

    /**
     * Envía una alerta de ejemplo a los destinatarios activos.
     */
    public function sendTest()
    {
        if (! $this->bot->isConfigured()) {
            throw ValidationException::withMessages([
                'test' => 'El bot de Telegram no está configurado: falta el token en el parámetro SC-00002.',
            ]);
        }

        if ($this->alerts->activeChatIds() === []) {
            throw ValidationException::withMessages([
                'test' => 'No hay destinatarios activos: agrega al menos un chat_id para enviar la prueba.',
            ]);
        }

        $result = $this->alerts->sendTest($this->bot);

        $message = $result['sent'] > 0
            ? "Alerta de prueba enviada a {$result['sent']} destinatario(s)."
            : 'No se pudo enviar la prueba a ningún destinatario. Revisa los chat_id y el token del bot.';

        if ($result['failed'] > 0 && $result['sent'] > 0) {
            $message .= " {$result['failed']} envío(s) fallaron.";
        }

        return response()->json([
            'message' => $message,
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'results' => $result['results'],
        ]);
    }

    /**
     * Ajustes tal como los consume la pantalla.
     *
     * @return array<string, mixed>
     */
    private function settingsPayload(SecurityAlertSetting $settings): array
    {
        return [
            'enabled' => $settings->isEnabled(),
            'min_level' => $settings->minLevel(),
            'cooldown_minutes' => $settings->cooldownMinutes(),
            'store_context' => $settings->storesContext(),
            'retention_days' => $settings->retentionDays(),
            'purge_enabled' => $settings->autoPurge(),
        ];
    }

    /**
     * Interruptores por tipo de aviso, con la etiqueta que muestra la pantalla.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topicsPayload(SecurityAlertSetting $settings): array
    {
        $topics = [];

        foreach (SecurityAlertSetting::topics() as $key => $topic) {
            $topics[] = [
                'key' => $key,
                'label' => $topic['label'],
                'description' => $topic['description'],
                'enabled' => $settings->topicEnabled($key),
            ];
        }

        return $topics;
    }

    /**
     * Reglas de validación de los interruptores por tipo.
     *
     * @return array<string, array<int, string>>
     */
    private function topicRules(): array
    {
        $rules = ['topics' => ['sometimes', 'array']];

        foreach (array_keys(SecurityAlertSetting::topics()) as $topic) {
            $rules["topics.{$topic}"] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * Cuánto contexto de errores hay guardado (lo resume la pantalla).
     *
     * @return array<string, mixed>
     */
    private function logStats(): array
    {
        $stats = $this->errorLogs->stats();

        return [
            'total' => $stats['total'],
            'size_bytes' => $stats['size_bytes'],
            'oldest_at' => $stats['oldest_at'],
            'newest_at' => $stats['newest_at'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRecipient(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'chat_id' => [
                'required',
                'string',
                'max:60',
                'regex:/^@?[A-Za-z0-9_-]+$/',
                Rule::unique('security_alert_recipients', 'chat_id')->ignore($ignoreId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'chat_id.regex' => 'El chat_id debe ser el número que entrega Telegram (puede ser negativo) o un @usuario del canal.',
        ]);
    }

    private function normalizeChatId(string $chatId): string
    {
        return trim($chatId);
    }

    /**
     * @return array<string, mixed>
     */
    private function recipientPayload(SecurityAlertRecipient $recipient): array
    {
        return [
            'id' => $recipient->id,
            'name' => $recipient->name,
            'chat_id' => $recipient->chat_id,
            'is_active' => (bool) $recipient->is_active,
        ];
    }
}
