<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Integrationhub\Support\TelegramHtml;
use RuntimeException;

/**
 * Cliente del bot de Telegram exclusivo para los avisos de asistencia del colegio.
 *
 * El token del bot se lee del parámetro del sistema configurado en
 * academic.school_notices.telegram.token_parameter (SC-00033 por defecto).
 * El secreto del webhook se deriva mediante HMAC del propio token para que
 * Telegram valide las peticiones entrantes a /api/academic/school/telegram/webhook.
 */
class SchoolTelegramBotService
{
    /** Clave de caché del usuario público del bot escolar. */
    private const USERNAME_CACHE_KEY = 'academic.school_notices.telegram.bot_username';

    private ?string $resolvedToken = null;

    private bool $tokenResolved = false;

    /**
     * true solo si el parámetro del sistema tiene un token cargado.
     */
    public function isConfigured(): bool
    {
        try {
            return $this->token() !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Olvida el token resuelto en memoria y el usuario cacheado (tras guardar un token nuevo).
     */
    public function flushToken(): void
    {
        $this->resolvedToken = null;
        $this->tokenResolved = false;

        try {
            Cache::forget(self::USERNAME_CACHE_KEY);
        } catch (\Throwable) {
            // Sin caché disponible no hay nada que limpiar.
        }
    }

    /**
     * Token del bot normalizado, o null si todavía no se configuró.
     */
    public function token(): ?string
    {
        if ($this->tokenResolved) {
            return $this->resolvedToken;
        }

        $this->tokenResolved = true;

        $parameterCode = trim((string) config('academic.school_notices.telegram.token_parameter', 'SC-00033'));

        if ($parameterCode === '') {
            return $this->resolvedToken = null;
        }

        $raw = Parameter::where('parameter_code', $parameterCode)->value('value_default');
        $token = trim((string) $raw);

        return $this->resolvedToken = $token === '' ? null : $token;
    }

    /**
     * Secreto compartido con Telegram para validar el webhook escolar.
     */
    public function secret(): ?string
    {
        $token = $this->token();

        if ($token === null) {
            return null;
        }

        return substr(hash_hmac('sha256', 'school_telegram_bot_webhook', $token), 0, 40);
    }

    /**
     * Usuario público del bot (sin @) para armar el enlace t.me.
     */
    public function username(bool $refresh = false): ?string
    {
        if ($refresh) {
            try {
                Cache::forget(self::USERNAME_CACHE_KEY);
            } catch (\Throwable) {
                // Ignorar fallo de caché.
            }
        }

        $cached = $this->cachedUsername();

        if ($cached !== null) {
            return $cached;
        }

        if ($this->token() === null) {
            return null;
        }

        try {
            $body = $this->request('GET', 'getMe');
            $username = trim((string) ($body['result']['username'] ?? ''));

            if ($username === '') {
                return null;
            }

            $minutes = max(1, (int) config('academic.school_notices.telegram.username_cache_minutes', 1440));
            Cache::put(self::USERNAME_CACHE_KEY, $username, now()->addMinutes($minutes));

            return $username;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Usuario del bot ya conocido en caché, sin consultar la red.
     */
    public function cachedUsername(): ?string
    {
        try {
            $cached = Cache::get(self::USERNAME_CACHE_KEY);

            return is_string($cached) && $cached !== '' ? $cached : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Enlace público del bot escolar (https://t.me/<bot>).
     */
    public function registrationLink(bool $refresh = false): ?string
    {
        $username = $this->username($refresh);

        return $username === null ? null : $this->link($username);
    }

    /**
     * Enlace público ya cacheado, sin consultar Telegram.
     */
    public function cachedRegistrationLink(): ?string
    {
        $username = $this->cachedUsername();

        return $username === null ? null : $this->link($username);
    }

    /**
     * URL pública que se registra en Telegram con setWebhook.
     */
    public function webhookUrl(): string
    {
        $path = (string) config('academic.school_notices.telegram.webhook_path', 'api/academic/school/telegram/webhook');

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Envía un mensaje a un chat de Telegram.
     *
     * @param array<string, mixed> $extra Opciones adicionales (parse_mode, reply_markup, ...)
     */
    public function sendMessage(string $chatId, string $text, array $extra = []): array
    {
        $chatId = trim($chatId);
        $text = trim($text);

        if ($chatId === '') {
            throw new RuntimeException('El chat_id del destinatario está vacío.');
        }

        if ($text === '') {
            throw new RuntimeException('El mensaje de Telegram está vacío.');
        }

        return $this->request('POST', 'sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extra));
    }

    /**
     * Envía un mensaje con soporte HTML seguro (escapa caracteres sueltos y
     * reintenta como texto plano si Telegram rechaza alguna etiqueta).
     */
    public function sendFormatted(string $chatId, string $text, bool $html = true, array $extra = []): array
    {
        $options = $html ? array_merge(['parse_mode' => 'HTML'], $extra) : $extra;

        try {
            return $this->sendMessage($chatId, TelegramHtml::prepare($text, $html), $options);
        } catch (RuntimeException $exception) {
            if (! $html) {
                throw $exception;
            }

            Log::warning('Bot escolar de Telegram rechazó el formato HTML; se reenvía como texto plano', [
                'chat_id' => $chatId,
                'error' => $exception->getMessage(),
            ]);

            return $this->sendMessage($chatId, TelegramHtml::prepare($text, false), $extra);
        }
    }

    /**
     * Registra el webhook del bot escolar y publica el menú de comandos.
     */
    public function registerWebhook(bool $dropPendingUpdates = true): string
    {
        $url = $this->webhookUrl();
        $secret = $this->secret();

        if ($secret === null) {
            throw new RuntimeException(
                'Falta configurar el token del bot de Telegram en el parámetro '
                . config('academic.school_notices.telegram.token_parameter', 'SC-00033') . '.'
            );
        }

        $this->request('POST', 'setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'drop_pending_updates' => $dropPendingUpdates,
        ]);

        $this->setMyCommands($this->defaultCommands());

        return $url;
    }

    /**
     * Publica el menú de comandos del bot escolar.
     *
     * @param array<int, array{command: string, description: string}> $commands
     */
    public function setMyCommands(array $commands): array
    {
        return $this->request('POST', 'setMyCommands', [
            'commands' => array_values($commands),
        ]);
    }

    /**
     * Comandos oficiales que ofrece el bot escolar de asistencia.
     *
     * @return array<int, array{command: string, description: string}>
     */
    public function defaultCommands(): array
    {
        return [
            ['command' => 'registrar', 'description' => 'Registrar DNI de apoderado y de tus hijos para recibir avisos'],
            ['command' => 'baja', 'description' => 'Dar de baja y dejar de recibir avisos de asistencia'],
            ['command' => 'chatid', 'description' => 'Mi Chat ID (ver el identificador de este chat)'],
            ['command' => 'info', 'description' => 'Información del colegio, horarios y uso del bot'],
        ];
    }

    private function link(string $username): string
    {
        return 'https://t.me/' . ltrim($username, '@');
    }

    /**
     * Ejecuta una petición contra la API de Telegram Bot.
     *
     * @param array<string, mixed> $payload
     */
    private function request(string $method, string $action, array $payload = []): array
    {
        $token = $this->token();

        if ($token === null) {
            throw new RuntimeException(
                'Falta configurar el token del bot de Telegram en el parámetro '
                . config('academic.school_notices.telegram.token_parameter', 'SC-00033') . '.'
            );
        }

        $base = rtrim((string) config('academic.school_notices.telegram.api_base', 'https://api.telegram.org'), '/');
        $url = "{$base}/bot{$token}/" . ltrim($action, '/');
        $timeout = max(5, (int) config('academic.school_notices.telegram.timeout', 30));

        try {
            $request = Http::timeout($timeout)->acceptJson();

            $response = strtoupper($method) === 'GET'
                ? $request->get($url, $payload)
                : $request->post($url, $payload);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo conectar con Telegram: ' . $exception->getMessage(), 0, $exception);
        }

        $body = $response->json();

        if (! $response->successful()) {
            $description = is_array($body)
                ? trim((string) ($body['description'] ?? $response->body()))
                : trim((string) $response->body());

            throw new RuntimeException(
                'Telegram respondió HTTP ' . $response->status() . ': ' . mb_substr($description, 0, 300)
            );
        }

        if (is_array($body) && ($body['ok'] ?? null) === false) {
            throw new RuntimeException(
                'Telegram rechazó la petición: ' . trim((string) ($body['description'] ?? json_encode($body)))
            );
        }

        return is_array($body) ? $body : [];
    }
}
