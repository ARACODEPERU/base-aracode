<?php

namespace Modules\Security\Services;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Jobs\SendSecurityErrorAlert;
use Throwable;

/**
 * Puente entre los logs de la aplicación y las alertas por Telegram.
 *
 * Cuando Laravel registra un mensaje dispara MessageLogged; este servicio
 * escucha esos eventos (lo hace SecurityServiceProvider) y, si el nivel alcanza
 * el umbral configurado, envía por la integración Telegram_bot el detalle del
 * error a los chat_id activos.
 *
 * Reglas de seguridad que se respetan aquí:
 *   - NUNCA rompe la petición: cualquier fallo se traga (try/catch).
 *   - NUNCA entra en bucle: los logs que produce el propio envío se marcan con
 *     el contexto ['security_alert' => true] y se ignoran; además hay un
 *     interruptor estático mientras se envía.
 *   - No satura: deduplica por huella (nivel + mensaje + archivo + línea)
 *     durante la ventana configurada.
 *   - No golpea la base en cada línea: ajustes y destinatarios se cachean.
 */
class ErrorAlertService
{
    /** Marca que se añade al contexto de los logs internos de las alertas. */
    public const CONTEXT_MARKER = 'security_alert';

    private const SETTINGS_CACHE_KEY = 'security.alerts.settings';

    private const RECIPIENTS_CACHE_KEY = 'security.alerts.recipients';

    private const DEDUP_PREFIX = 'security.alerts.seen:';

    /** true mientras el propio servicio está enviando: evita la recursión. */
    private static bool $suppressed = false;

    /**
     * Entrada del listener: evalúa el evento y, si corresponde, encola la alerta.
     */
    public function handle(MessageLogged $event): void
    {
        try {
            if (self::$suppressed || $this->isInternal($event)) {
                return;
            }

            $settings = $this->settings();

            if (! $settings->isEnabled() || ! $settings->levelReaches((string) $event->level)) {
                return;
            }

            $chatIds = $this->activeChatIds();

            if ($chatIds === []) {
                return;
            }

            $payload = $this->buildPayload($event);

            if ($this->isDuplicate($payload, $settings->cooldownMinutes())) {
                return;
            }

            SendSecurityErrorAlert::dispatch($chatIds, $this->renderMessage($payload));
        } catch (Throwable) {
            // Una alerta jamás puede tumbar la aplicación.
        }
    }

    /**
     * Aviso tal como se enviaría, útil para la vista previa de la pantalla.
     */
    public function previewMessage(): string
    {
        return $this->renderMessage($this->samplePayload());
    }

    /**
     * Envía una alerta de ejemplo a los destinatarios activos, sin depender de
     * la cola, para que el botón de prueba dé un resultado inmediato.
     *
     * @return array{message: string, sent: int, failed: int, results: array<int, array<string, mixed>>}
     */
    public function sendTest(?TelegramBotService $bot = null): array
    {
        $bot ??= app(TelegramBotService::class);
        $text = $this->previewMessage();

        $results = [];

        foreach ($this->activeRecipients() as $recipient) {
            $results[] = $this->deliver($bot, (string) $recipient->chat_id, $recipient->name, $text);
        }

        $sent = count(array_filter($results, fn (array $result) => $result['ok']));

        return [
            'message' => $text,
            'sent' => $sent,
            'failed' => count($results) - $sent,
            'results' => $results,
        ];
    }

    /**
     * Envía el mismo texto a una lista de chat_id (lo usa el job encolado).
     *
     * @param array<int, string> $chatIds
     */
    public function deliverToChatIds(array $chatIds, string $text, ?TelegramBotService $bot = null): array
    {
        $bot ??= app(TelegramBotService::class);
        $results = [];

        self::$suppressed = true;

        try {
            foreach ($chatIds as $chatId) {
                $results[] = $this->deliver($bot, (string) $chatId, null, $text);
            }
        } finally {
            self::$suppressed = false;
        }

        return $results;
    }

    /**
     * Ajustes vigentes (cache corta).
     */
    public function settings(): SecurityAlertSetting
    {
        $minutes = $this->cacheMinutes();

        if ($minutes <= 0) {
            return SecurityAlertSetting::current();
        }

        try {
            return Cache::remember(
                self::SETTINGS_CACHE_KEY,
                now()->addMinutes($minutes),
                fn () => SecurityAlertSetting::current()
            );
        } catch (Throwable) {
            return SecurityAlertSetting::current();
        }
    }

    /**
     * Olvida la caché de ajustes y destinatarios (tras guardar en pantalla).
     */
    public function forgetCache(): void
    {
        try {
            Cache::forget(self::SETTINGS_CACHE_KEY);
            Cache::forget(self::RECIPIENTS_CACHE_KEY);
        } catch (Throwable) {
            // Sin caché disponible no hay nada que olvidar.
        }
    }

    /**
     * @return array<int, string>
     */
    public function activeChatIds(): array
    {
        return $this->activeRecipients()
            ->pluck('chat_id')
            ->map(fn ($chatId) => trim((string) $chatId))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, SecurityAlertRecipient>
     */
    public function activeRecipients()
    {
        $minutes = $this->cacheMinutes();

        if ($minutes <= 0) {
            return $this->queryActiveRecipients();
        }

        try {
            return Cache::remember(
                self::RECIPIENTS_CACHE_KEY,
                now()->addMinutes($minutes),
                fn () => $this->queryActiveRecipients()
            );
        } catch (Throwable) {
            return $this->queryActiveRecipients();
        }
    }

    /**
     * Arma el arreglo serializable que viaja en el job (sin objetos Throwable).
     *
     * @return array<string, string>
     */
    public function buildPayload(MessageLogged $event): array
    {
        $context = is_array($event->context) ? $event->context : [];
        $exception = $context['exception'] ?? null;

        $class = '';
        $file = '';
        $line = '';
        $trace = '';

        if ($exception instanceof Throwable) {
            $class = $exception::class;
            $file = $exception->getFile();
            $line = (string) $exception->getLine();
            $trace = $this->formatTrace($exception);
        }

        return [
            'level' => strtoupper((string) $event->level),
            'message' => $this->limit((string) $event->message),
            'class' => $class,
            'file' => $file,
            'line' => $line,
            'trace' => $trace,
            'environment' => (string) app()->environment(),
            'url' => $this->requestUrl(),
            'method' => $this->requestMethod(),
            'user' => $this->userLabel(),
            'ip' => $this->requestIp(),
            'date' => now()->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Renderiza la plantilla con las variables del payload.
     *
     * Las líneas cuya variable llega vacía se descartan (igual que los textos
     * del bot de Telegram), de modo que un error sin usuario no muestre
     * "Usuario: ".
     *
     * @param array<string, string> $payload
     */
    public function renderMessage(array $payload, ?string $template = null): string
    {
        $template = $template ?? (string) config('security.alerts.template', '');

        $values = [
            'nivel' => $payload['level'] ?? '',
            'mensaje' => $payload['message'] ?? '',
            'clase' => $payload['class'] ?? '',
            'archivo' => $payload['file'] ?? '',
            'linea' => $payload['line'] ?? '',
            'traza' => $payload['trace'] ?? '',
            'entorno' => $payload['environment'] ?? '',
            'ruta' => $payload['url'] ?? '',
            'metodo' => $payload['method'] ?? '',
            'usuario' => $payload['user'] ?? '',
            'ip' => $payload['ip'] ?? '',
            'fecha' => $payload['date'] ?? '',
        ];

        $lines = preg_split('/\R/', $template) ?: [];
        $kept = [];

        foreach ($lines as $line) {
            if ($this->dependsOnEmptyVariable($line, $values)) {
                continue;
            }

            $kept[] = (string) preg_replace_callback(
                '/\{([a-z0-9_]+)\}/i',
                fn (array $match) => $values[strtolower($match[1])] ?? $match[0],
                $line
            );
        }

        return trim(implode("\n", $kept));
    }

    /**
     * Payload de ejemplo para la vista previa y el envío de prueba.
     *
     * @return array<string, string>
     */
    public function samplePayload(): array
    {
        return [
            'level' => 'ERROR',
            'message' => 'Alerta de prueba enviada desde Seguridad → Alertas.',
            'class' => 'RuntimeException',
            'file' => 'app/Http/Controllers/ExampleController.php',
            'line' => '42',
            'trace' => '',
            'environment' => (string) app()->environment(),
            'url' => $this->requestUrl(),
            'method' => $this->requestMethod() ?: 'CLI',
            'user' => $this->userLabel() ?: 'Prueba',
            'ip' => $this->requestIp(),
            'date' => now()->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Entrega a un chat concreto; nunca lanza.
     *
     * @return array<string, mixed>
     */
    private function deliver(TelegramBotService $bot, string $chatId, ?string $name, string $text): array
    {
        try {
            $bot->sendFormatted($chatId, $text, true);

            return [
                'chat_id' => $chatId,
                'name' => $name,
                'ok' => true,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            // Se registra en nivel warning (por debajo del umbral) para no
            // generar una alerta por el fallo de una alerta.
            Log::warning('No se pudo enviar una alerta de error por Telegram', [
                self::CONTEXT_MARKER => true,
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'chat_id' => $chatId,
                'name' => $name,
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * true si el log lo produjo el propio sistema de alertas.
     */
    private function isInternal(MessageLogged $event): bool
    {
        $context = is_array($event->context) ? $event->context : [];

        return ($context[self::CONTEXT_MARKER] ?? false) === true;
    }

    /**
     * true si la misma huella ya se avisó dentro de la ventana.
     *
     * @param array<string, string> $payload
     */
    private function isDuplicate(array $payload, int $cooldownMinutes): bool
    {
        if ($cooldownMinutes <= 0) {
            return false;
        }

        $fingerprint = sha1(implode('|', [
            $payload['level'] ?? '',
            $payload['message'] ?? '',
            $payload['file'] ?? '',
            $payload['line'] ?? '',
        ]));

        try {
            // add() es atómico: devuelve false si la clave ya existía.
            return ! Cache::add(self::DEDUP_PREFIX . $fingerprint, true, now()->addMinutes($cooldownMinutes));
        } catch (Throwable) {
            // Sin caché no se deduplica, pero se avisa igual.
            return false;
        }
    }

    private function queryActiveRecipients()
    {
        return SecurityAlertRecipient::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    private function cacheMinutes(): int
    {
        return (int) config('security.alerts.cache_minutes', 5);
    }

    private function limit(string $value): string
    {
        $limit = max(50, (int) config('security.alerts.message_limit', 3000));

        return mb_substr($value, 0, $limit);
    }

    private function formatTrace(Throwable $exception): string
    {
        $lines = max(0, (int) config('security.alerts.trace_lines', 0));

        if ($lines === 0) {
            return '';
        }

        $frames = preg_split('/\R/', $exception->getTraceAsString()) ?: [];

        return $this->limit(implode("\n", array_slice($frames, 0, $lines)));
    }

    /**
     * @param array<string, string> $values
     */
    private function dependsOnEmptyVariable(string $line, array $values): bool
    {
        preg_match_all('/\{([a-z0-9_]+)\}/i', $line, $matches);

        foreach ($matches[1] ?? [] as $name) {
            $key = strtolower($name);

            if (array_key_exists($key, $values) && $values[$key] === '') {
                return true;
            }
        }

        return false;
    }

    private function requestUrl(): string
    {
        try {
            if (app()->runningInConsole() || ! app()->bound('request')) {
                return '';
            }

            return (string) request()->fullUrl();
        } catch (Throwable) {
            return '';
        }
    }

    private function requestMethod(): string
    {
        try {
            if (app()->runningInConsole() || ! app()->bound('request')) {
                return '';
            }

            return (string) request()->method();
        } catch (Throwable) {
            return '';
        }
    }

    private function requestIp(): string
    {
        try {
            if (! app()->bound('request')) {
                return '';
            }

            return (string) (request()->ip() ?? '');
        } catch (Throwable) {
            return '';
        }
    }

    private function userLabel(): string
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return '';
            }

            $name = trim((string) ($user->name ?? ''));
            $email = trim((string) ($user->email ?? ''));

            if ($name !== '' && $email !== '') {
                return $name . ' (' . $email . ')';
            }

            return $name !== '' ? $name : $email;
        } catch (Throwable) {
            return '';
        }
    }
}
