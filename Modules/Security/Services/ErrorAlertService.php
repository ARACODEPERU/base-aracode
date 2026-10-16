<?php

namespace Modules\Security\Services;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Entities\SecurityErrorLog;
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
 *   - Da contexto: además del resumen del chat, guarda el detalle completo del
 *     error (con ErrorLogService) y el mensaje lleva el enlace para abrirlo. Las
 *     repeticiones del mismo fallo solo suman, no avisan otra vez.
 */
class ErrorAlertService
{
    /** Marca que se añade al contexto de los logs internos de las alertas. */
    public const CONTEXT_MARKER = 'security_alert';

    /**
     * Marca con la que un comando programado avisa que ya explicó su fallo.
     *
     * Cuando el planificador ve un comando con salida distinta de cero registra
     * por su cuenta "Scheduled command [...] failed with exit code [1]", que no
     * dice nada del motivo. Si el propio comando ya registró el fallo real con
     * esta marca en el contexto, esa línea genérica no se avisa: el chat recibe
     * un solo mensaje, el que sirve para corregirlo.
     */
    public const SCHEDULED_COMMAND_MARKER = 'scheduled_command';

    private const SETTINGS_CACHE_KEY = 'security.alerts.settings';

    private const RECIPIENTS_CACHE_KEY = 'security.alerts.recipients';

    /** Fila guardada de una huella (se reutiliza durante la ventana de mezcla). */
    private const ROW_PREFIX = 'security.alerts.row:';

    /** Última vez que se avisó una huella (controla cada cuánto se repite el aviso). */
    private const ALERT_PREFIX = 'security.alerts.alerted:';

    /** Comandos programados cuyo fallo ya se explicó (silencio de la línea genérica). */
    private const EXPLAINED_PREFIX = 'security.alerts.explained:';

    /** Claves cuyo valor NUNCA se guarda (contraseñas, tokens, cookies...). */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'apikey',
        'authorization',
        'cookie',
        '_token',
    ];

    /** Valor con el que se sustituye un dato sensible. */
    private const REDACTED = '*** (oculto)';

    /** true mientras el propio servicio está enviando: evita la recursión. */
    private static bool $suppressed = false;

    public function __construct(private readonly ErrorLogService $errorLogs)
    {
    }

    /** Ajustes resueltos en esta petición (el servicio es singleton). */
    private ?SecurityAlertSetting $settingsMemo = null;

    /** Destinatarios activos resueltos en esta petición. */
    private ?\Illuminate\Support\Collection $recipientsMemo = null;

    /** true si resolver la configuración falló: no se reintenta en la misma petición. */
    private bool $memoFailed = false;

    /**
     * Entrada del listener: evalúa el evento y, si corresponde, encola la alerta.
     */
    public function handle(MessageLogged $event): void
    {
        if (self::$suppressed || $this->memoFailed || $this->isInternal($event)) {
            return;
        }

        try {
            $settings = $this->settings();

            if (! $settings->isEnabled() || ! $settings->levelReaches((string) $event->level)) {
                return;
            }

            $chatIds = $this->activeChatIds();

            if ($chatIds === []) {
                return;
            }

            $payload = $this->buildPayload($event);

            // La línea genérica del planificador no dice nada del motivo: si el
            // propio comando ya avisó del fallo real hace un momento, se ignora.
            if ($this->isScheduledFailureLine((string) ($payload['message'] ?? ''))
                && $this->commandWasExplained((string) ($payload['message'] ?? ''))) {
                return;
            }

            $fingerprint = SecurityErrorLog::fingerprint($payload);
            $cooldown = $settings->cooldownMinutes();
            $mergeMinutes = $this->mergeMinutes();

            // ¿Es la repetición de un fallo ya conocido? Se suma a la MISMA fila
            // guardada (no se duplica el contexto en el disco) y solo se vuelve a
            // avisar cuando pasa la ventana del aviso.
            $rowId = $this->rememberedRow($fingerprint, $mergeMinutes);

            if ($rowId !== null) {
                $row = $rowId > 0 ? SecurityErrorLog::find($rowId) : null;

                if ($row) {
                    $this->errorLogs->touch($row, $payload);
                }

                if (! $this->canAlert($fingerprint, $cooldown)) {
                    return;
                }

                if ($row) {
                    $payload['reference'] = $row->reference();
                    $payload['link'] = $this->errorLogs->linkFor((int) $row->id);
                    $payload['occurrences'] = (string) $row->occurrences;
                }

                $this->dispatchAlert($chatIds, $payload, $event);

                return;
            }

            $log = null;

            // El detalle completo se guarda antes de avisar: así el mensaje ya
            // puede llevar el enlace al contexto.
            if ($settings->storesContext()) {
                $log = $this->errorLogs->record($payload, $fingerprint);
                $payload['reference'] = $log?->reference() ?? '';
                $payload['link'] = $this->errorLogs->linkFor($log?->id);
            }

            $this->rememberRow($fingerprint, $mergeMinutes, $log?->id);
            $this->canAlert($fingerprint, $cooldown);

            $this->dispatchAlert($chatIds, $payload, $event);
        } catch (Throwable $exception) {
            // Una alerta jamás puede tumbar la aplicación: el motivo queda en el
            // log (con la marca interna, para no generar otra alerta) y el fallo
            // se recuerda para no repetir consultas que fallen en cada línea de
            // log de la misma petición.
            $this->logFailure('La alerta de error no se pudo preparar', [
                'error' => $exception->getMessage(),
            ]);

            $this->memoFailed = true;
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
        if ($this->settingsMemo instanceof SecurityAlertSetting) {
            return $this->settingsMemo;
        }

        $minutes = $this->cacheMinutes();

        if ($minutes <= 0) {
            return $this->settingsMemo = SecurityAlertSetting::current();
        }

        try {
            return $this->settingsMemo = Cache::remember(
                self::SETTINGS_CACHE_KEY,
                now()->addMinutes($minutes),
                fn () => SecurityAlertSetting::current()
            );
        } catch (Throwable) {
            return $this->settingsMemo = SecurityAlertSetting::current();
        }
    }

    /**
     * Olvida la caché de ajustes y destinatarios (tras guardar en pantalla).
     */
    public function forgetCache(): void
    {
        $this->settingsMemo = null;
        $this->recipientsMemo = null;
        $this->memoFailed = false;

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
        if ($this->recipientsMemo instanceof \Illuminate\Support\Collection) {
            return $this->recipientsMemo;
        }

        $minutes = $this->cacheMinutes();

        if ($minutes <= 0) {
            return $this->recipientsMemo = $this->queryActiveRecipients();
        }

        try {
            return $this->recipientsMemo = Cache::remember(
                self::RECIPIENTS_CACHE_KEY,
                now()->addMinutes($minutes),
                fn () => $this->queryActiveRecipients()
            );
        } catch (Throwable) {
            return $this->recipientsMemo = $this->queryActiveRecipients();
        }
    }

    /**
     * Arma el arreglo serializable que viaja en el job y se guarda en el
     * contexto del error (sin objetos Throwable).
     *
     * `trace` es el resumen que va en el mensaje del chat; `trace_full` y
     * `context` son el detalle completo que se guarda para verlo por enlace.
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
        $traceFull = '';

        if ($exception instanceof Throwable) {
            $class = $exception::class;
            $file = $exception->getFile();
            $line = (string) $exception->getLine();
            $traceFull = $this->limit($exception->getTraceAsString(), $this->contextLimit());
        }

        return [
            'level' => strtoupper((string) $event->level),
            'message' => $this->limit((string) $event->message),
            'class' => $class,
            'file' => $file,
            'line' => $line,
            'trace' => $this->formatTrace($exception),
            'trace_full' => $traceFull,
            'context' => $this->encodeContext($event),
            'environment' => (string) app()->environment(),
            'url' => $this->requestUrl(),
            'method' => $this->requestMethod(),
            'user' => $this->userLabel(),
            'user_id' => $this->userId(),
            'ip' => $this->requestIp(),
            'agent' => $this->requestAgent(),
            'date' => now()->format('d/m/Y H:i:s'),
            'reference' => '',
            'link' => '',
            'occurrences' => '1',
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
            'agente' => $payload['agent'] ?? '',
            'fecha' => $payload['date'] ?? '',
            'referencia' => $payload['reference'] ?? '',
            'repeticiones' => $payload['occurrences'] ?? '',
            'enlace' => $payload['link'] ?? '',
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
            'agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
            'date' => now()->format('d/m/Y H:i:s'),
            'reference' => '1',
            'occurrences' => '1',
            'link' => $this->errorLogs->linkFor(1),
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
            $this->logFailure('No se pudo enviar una alerta de error por Telegram', [
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
     * Fila guardada de una huella dentro de la ventana de mezcla.
     *
     * Devuelve null si es la primera vez, 0 si ya se vio pero no se guardó
     * contexto, o el id de la fila (para sumarle la repetición).
     */
    private function rememberedRow(string $fingerprint, int $mergeMinutes): ?int
    {
        if ($mergeMinutes <= 0) {
            return null;
        }

        try {
            $value = Cache::get(self::ROW_PREFIX . $fingerprint);

            return $value === null ? null : (int) $value;
        } catch (Throwable) {
            // Sin caché no se reconoce la repetición: se avisa igual.
            return null;
        }
    }

    /**
     * Recuerda la fila de una huella durante la ventana de mezcla.
     */
    private function rememberRow(string $fingerprint, int $mergeMinutes, ?int $errorLogId): void
    {
        if ($mergeMinutes <= 0) {
            return;
        }

        try {
            Cache::put(self::ROW_PREFIX . $fingerprint, $errorLogId ?? 0, now()->addMinutes($mergeMinutes));
        } catch (Throwable) {
            // Sin caché cada fallo se guarda como uno nuevo.
        }
    }

    /**
     * true si toca avisar esta huella (ha pasado la ventana del aviso).
     *
     * El aviso se puede repetir (el error sigue vivo), pero no en cada línea de
     * log: como mucho una vez cada `cooldown_minutes` por huella.
     */
    private function canAlert(string $fingerprint, int $cooldownMinutes): bool
    {
        if ($cooldownMinutes <= 0) {
            return true;
        }

        try {
            // add() es atómico y solo devuelve true la primera vez de la ventana.
            return Cache::add(self::ALERT_PREFIX . $fingerprint, true, now()->addMinutes($cooldownMinutes));
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * Encola el aviso y, si el log explica el fallo de un comando programado, lo
     * recuerda para no avisar después la línea genérica del planificador.
     *
     * El encolado va aparte a propósito: si la cola falla (o el propio aviso no
     * se puede armar), el sistema sigue igual, el motivo queda en el log —sin
     * disparar una alerta por el fallo de la alerta— y no se marca como
     * explicado el fallo de un comando programado, porque el chat no lo recibió.
     *
     * Se encola por el bus en lugar de usar SendSecurityErrorAlert::dispatch():
     * ese helper entrega el job en el destructor del PendingDispatch, es decir,
     * fuera de este try/catch, y un fallo de la cola terminaba explotando más
     * tarde en medio de la petición.
     *
     * @param array<int, string>    $chatIds
     * @param array<string, string> $payload
     */
    private function dispatchAlert(array $chatIds, array $payload, MessageLogged $event): void
    {
        try {
            app(\Illuminate\Contracts\Bus\Dispatcher::class)
                ->dispatch(new SendSecurityErrorAlert($chatIds, $this->renderMessage($payload)));
        } catch (Throwable $exception) {
            $this->logFailure('No se pudo encolar la alerta de error', [
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $command = $this->explainedCommand($event);

        if ($command === null) {
            return;
        }

        try {
            Cache::put(
                self::EXPLAINED_PREFIX . $command,
                true,
                now()->addMinutes($this->scheduledSilenceMinutes())
            );
        } catch (Throwable) {
            // Sin caché puede llegar la línea genérica de más; no es grave.
        }
    }

    /**
     * Comando programado del que este log es el motivo real (o null).
     */
    private function explainedCommand(MessageLogged $event): ?string
    {
        $context = is_array($event->context) ? $event->context : [];
        $command = trim((string) ($context[self::SCHEDULED_COMMAND_MARKER] ?? ''));

        return $command === '' ? null : $command;
    }

    /**
     * true si el mensaje es la línea genérica con la que el planificador avisa
     * que un comando terminó con error. No trae el motivo: solo el código.
     */
    private function isScheduledFailureLine(string $message): bool
    {
        return (bool) preg_match('/^Scheduled command \[.+\] failed with exit code \[\d+\]\.$/', trim($message));
    }

    /**
     * true si el comando de esa línea genérica ya explicó su fallo hace poco.
     */
    private function commandWasExplained(string $message): bool
    {
        if (! preg_match('/^Scheduled command \[(.+)\] failed with exit code \[\d+\]\.$/', trim($message), $matches)) {
            return false;
        }

        $parts = preg_split('/\s+/', trim($matches[1])) ?: [];
        $command = trim((string) end($parts), "'\"");

        if ($command === '') {
            return false;
        }

        try {
            return Cache::has(self::EXPLAINED_PREFIX . $command);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Deja constancia de un fallo del propio sistema de alertas.
     *
     * Se registra a nivel warning (por debajo del umbral por defecto) y con la
     * marca interna, así que nunca genera otra alerta ni entra en bucle; y si el
     * log tampoco está disponible, el sistema sigue: una alerta fallida se
     * registra, jamás detiene a la aplicación.
     *
     * @param array<string, mixed> $context
     */
    private function logFailure(string $message, array $context = []): void
    {
        try {
            Log::warning($message, array_merge([self::CONTEXT_MARKER => true], $context));
        } catch (Throwable) {
            // Sin log disponible no queda nada más que hacer.
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

    /**
     * Minutos que se reutiliza la misma fila para un error que se repite.
     */
    private function mergeMinutes(): int
    {
        return max(0, (int) config('security.alerts.merge_minutes', 1440));
    }

    /**
     * Minutos de silencio de la línea genérica del planificador tras explicarse
     * el fallo real del comando.
     */
    private function scheduledSilenceMinutes(): int
    {
        return max(1, (int) config('security.alerts.scheduled_silence_minutes', 10));
    }

    private function limit(string $value, ?int $override = null): string
    {
        $limit = $override ?? max(50, (int) config('security.alerts.message_limit', 3000));

        return mb_substr($value, 0, $limit);
    }

    /**
     * Máximo de caracteres que se guardan por error (traza + contexto).
     */
    private function contextLimit(): int
    {
        return max(500, (int) config('security.alerts.context_limit', 20000));
    }

    /**
     * Contexto del log y de la petición, saneado y en JSON, tal como se guarda
     * para verlo desde el enlace del aviso.
     */
    private function encodeContext(MessageLogged $event): string
    {
        $context = is_array($event->context) ? $event->context : [];

        // La excepción se guarda aparte (clase, archivo, línea y traza).
        unset($context['exception']);

        $data = [
            'log' => $this->sanitize($context),
            'request' => $this->requestContext(),
        ];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        return $this->limit($json === false ? '' : $json, $this->contextLimit());
    }

    /**
     * Datos de la petición que ayudan a reproducir el error.
     *
     * @return array<string, mixed>
     */
    private function requestContext(): array
    {
        try {
            if (app()->runningInConsole() || ! app()->bound('request')) {
                return [];
            }

            $request = request();

            return [
                'query' => $this->sanitize($request->query()),
                'input' => $this->sanitize($request->all()),
                'headers' => $this->sanitize([
                    'user_agent' => $request->userAgent(),
                    'referer' => $request->headers->get('referer'),
                    'accept_language' => $request->headers->get('accept-language'),
                ]),
            ];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Quita del contexto lo que no se puede guardar (objetos) o no se debe
     * guardar (contraseñas, tokens, cookies) y recorta textos enormes.
     */
    private function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 6) {
            return '...';
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                $result[$key] = is_string($key) && $this->isSensitiveKey($key)
                    ? self::REDACTED
                    : $this->sanitize($item, $depth + 1);
            }

            return $result;
        }

        if ($value instanceof Throwable) {
            return [
                'class' => $value::class,
                'message' => $value->getMessage(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ];
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : $value::class;
        }

        if (is_string($value) && mb_strlen($value) > 2000) {
            return mb_substr($value, 0, 2000) . '…';
        }

        return is_scalar($value) || $value === null ? $value : gettype($value);
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(trim($key));

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($normalized === $sensitive || str_contains($normalized, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    private function formatTrace(?Throwable $exception): string
    {
        if (! $exception instanceof Throwable) {
            return '';
        }

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

    private function requestAgent(): string
    {
        try {
            if (app()->runningInConsole() || ! app()->bound('request')) {
                return '';
            }

            return (string) (request()->userAgent() ?? '');
        } catch (Throwable) {
            return '';
        }
    }

    private function userId(): string
    {
        try {
            $id = auth()->id();

            return $id === null ? '' : (string) $id;
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
