<?php

namespace Modules\Health\Services;

use App\Models\Parameter;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Health\Support\GoogleCalendarEventMissingException;
use Modules\Health\Support\GoogleCalendarSyncTokenExpiredException;
use RuntimeException;

/**
 * Cliente de Google Calendar del modulo Salud.
 *
 * Habla con el API REST con la fachada Http (Guzzle ya viene en el proyecto):
 * no se necesita el paquete google/apiclient. Implementa el flujo OAuth 2.0 de
 * tipo "authorization code" con `access_type=offline` para obtener un refresh
 * token, que queda guardado en un parametro del sistema (SC-00013) y con el que
 * se renueva el access token (cacheado hasta poco antes de expirar).
 *
 * Todo se lee de parametros del sistema y de `health.google_calendar.*`, igual
 * que el canal de SMS de los avisos: si falta el Client ID, el Client Secret o
 * el refresh token, isConfigured() devuelve false y no se sincroniza nada.
 */
class GoogleCalendarService
{
    /** Clave de cache del access token vigente. */
    private const ACCESS_TOKEN_CACHE_KEY = 'health.google_calendar.access_token';

    /** Descripcion del parametro que guarda el refresh token. */
    private const REFRESH_TOKEN_DESCRIPTION = 'Google Calendar (Salud): refresh token de OAuth 2.0 (lo guarda el boton "Conectar con Google"; tambien se puede pegar a mano)';

    /**
     * true solo si el interruptor Activo (SC-00010) esta encendido.
     */
    public function isActive(): bool
    {
        return $this->isEnabledFlag($this->parameter('enabled', 'SC-00010'));
    }

    /**
     * true solo si el interruptor Google -> sistema (SC-00017) esta encendido.
     */
    public function isInboundEnabled(): bool
    {
        return $this->isEnabledFlag($this->parameter('inbound', 'SC-00017'));
    }

    /**
     * true solo si estan el Client ID, el Client Secret y el refresh token.
     */
    public function isConfigured(): bool
    {
        return $this->clientId() !== null
            && $this->clientSecret() !== null
            && $this->refreshToken() !== null;
    }

    /**
     * Codigos de los parametros que consume el canal (para la pantalla).
     *
     * @return array{enabled: string, client_id: string, client_secret: string, refresh_token: string, calendar_id: string, channel_token: string, window_days: string, inbound: string}
     */
    public function parameterCodes(): array
    {
        return [
            'enabled' => $this->config('enabled_parameter', 'SC-00010'),
            'client_id' => $this->config('client_id_parameter', 'SC-00011'),
            'client_secret' => $this->config('client_secret_parameter', 'SC-00012'),
            'refresh_token' => $this->config('refresh_token_parameter', 'SC-00013'),
            'calendar_id' => $this->config('calendar_id_parameter', 'SC-00014'),
            'channel_token' => $this->config('channel_token_parameter', 'SC-00015'),
            'window_days' => $this->config('window_days_parameter', 'SC-00016'),
            'inbound' => $this->config('inbound_parameter', 'SC-00017'),
        ];
    }

    /**
     * Estado del canal para la pantalla de Google Calendar.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        return [
            'active' => $this->isActive(),
            'inbound' => $this->isInboundEnabled(),
            'configured' => $this->isConfigured(),
            'clientId' => $this->clientId() !== null,
            'clientSecret' => $this->clientSecret() !== null,
            'refreshToken' => $this->refreshToken() !== null,
            'channelToken' => $this->channelToken() !== null,
            'calendarId' => $this->calendarId(),
            'windowDays' => $this->windowDays(),
            'redirectUri' => $this->redirectUri(),
            'parameterCodes' => $this->parameterCodes(),
            // Ids de los interruptores: la pantalla los guarda por id, igual
            // que hacen los avisos por SMS con su parametro Activo.
            'enabledParameterId' => $this->parameterId('enabled', 'SC-00010'),
            'inboundParameterId' => $this->parameterId('inbound', 'SC-00017'),
        ];
    }

    /**
     * Id del parametro del sistema (para la pantalla).
     */
    public function parameterId(string $key, string $defaultCode): ?int
    {
        $code = $this->config($key . '_parameter', $defaultCode);

        if ($code === '') {
            return null;
        }

        $id = Parameter::where('parameter_code', $code)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Calendario del consultorio con el que se sincroniza.
     */
    public function calendarId(): string
    {
        return $this->parameter('calendar_id', 'SC-00014')
            ?? trim((string) $this->config('default_calendar_id', 'primary'));
    }

    /**
     * Dias de ventana de la primera lectura (hacia atras y hacia adelante).
     */
    public function windowDays(): int
    {
        $value = $this->parameter('window_days', 'SC-00016');

        $days = $value === null ? 0 : (int) $value;

        if ($days <= 0) {
            $days = (int) $this->config('default_window_days', 60);
        }

        return min(365, max(1, $days));
    }

    /**
     * Secreto que valida las notificaciones push.
     */
    public function channelToken(): ?string
    {
        return $this->parameter('channel_token', 'SC-00015');
    }

    /**
     * Zona horaria con la que se escriben y se leen los eventos.
     */
    public function timezone(): string
    {
        return (string) config('health.google_calendar.timezone', 'America/Lima');
    }

    /**
     * URL a la que Google devuelve el codigo de autorizacion.
     */
    public function redirectUri(): string
    {
        try {
            return route('heal_google_calendar_callback');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * URL del consentimiento de Google para conectar la cuenta.
     */
    public function authorizationUrl(string $state): string
    {
        $params = [
            'client_id' => (string) $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => (string) $this->config('scope', 'https://www.googleapis.com/auth/calendar.events'),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            // prompt=consent obliga a Google a devolver el refresh token en cada
            // conexion (sin el, la segunda vez no lo manda).
            'prompt' => 'consent',
            'state' => $state,
        ];

        return (string) $this->config('auth_url', 'https://accounts.google.com/o/oauth2/v2/auth')
            . '?' . http_build_query($params);
    }

    /**
     * Canjea el codigo de autorizacion y guarda el refresh token.
     *
     * @return array<string, mixed> Respuesta de Google (scope, token_type, ...)
     */
    public function exchangeCode(string $code): array
    {
        $data = $this->tokenRequest([
            'code' => $code,
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ], 'canjear el codigo de autorizacion');

        $refreshToken = $data['refresh_token'] ?? null;

        if (! is_string($refreshToken) || trim($refreshToken) === '') {
            throw new RuntimeException(
                'Google no devolvio un refresh token. Vuelve a conectar la cuenta aceptando los permisos del calendario.'
            );
        }

        $this->storeRefreshToken($refreshToken);

        $accessToken = $data['access_token'] ?? null;

        if (is_string($accessToken) && $accessToken !== '') {
            $this->cacheAccessToken($accessToken, (int) ($data['expires_in'] ?? 3600));
        }

        return $data;
    }

    /**
     * Guarda el refresh token en el parametro del sistema y limpia la cache.
     */
    public function storeRefreshToken(string $token): void
    {
        $code = $this->config('refresh_token_parameter', 'SC-00013');

        if ($code === '') {
            throw new RuntimeException('No hay configurado el parametro del refresh token de Google Calendar.');
        }

        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => $code],
            ['description' => self::REFRESH_TOKEN_DESCRIPTION, 'control_type' => 'tx']
        );

        $parameter->update(['value_default' => trim($token)]);

        Cache::forget(self::ACCESS_TOKEN_CACHE_KEY);
    }

    /**
     * Olvida las credenciales de la cuenta conectada.
     */
    public function disconnect(): void
    {
        $code = $this->config('refresh_token_parameter', 'SC-00013');

        if ($code !== '') {
            Parameter::where('parameter_code', $code)->update(['value_default' => null]);
        }

        Cache::forget(self::ACCESS_TOKEN_CACHE_KEY);
    }

    /**
     * Access token vigente, renovandolo con el refresh token cuando hace falta.
     *
     * @throws RuntimeException cuando no hay credenciales o Google las rechaza.
     */
    public function accessToken(bool $forceRefresh = false): string
    {
        if (! $forceRefresh) {
            $cached = Cache::get(self::ACCESS_TOKEN_CACHE_KEY);

            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $data = $this->tokenRequest([
            'refresh_token' => (string) $this->refreshToken(),
            'grant_type' => 'refresh_token',
        ], 'renovar el token de acceso');

        $token = $data['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Google no devolvio un token de acceso.');
        }

        $this->cacheAccessToken($token, (int) ($data['expires_in'] ?? 3600));

        return $token;
    }

    /**
     * Crea el evento en el calendario configurado.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function insertEvent(array $payload): array
    {
        return $this->api('post', $this->eventsUrl(), ['json' => $payload], 'crear el evento');
    }

    /**
     * Actualiza un evento existente.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     *
     * @throws GoogleCalendarEventMissingException cuando el evento ya no existe.
     */
    public function updateEvent(string $eventId, array $payload): array
    {
        $response = $this->send('patch', $this->eventUrl($eventId), ['json' => $payload]);

        if (in_array($response->status(), [404, 410], true)) {
            throw new GoogleCalendarEventMissingException('El evento ' . $eventId . ' ya no existe en Google Calendar.');
        }

        return $this->decode($response, 'actualizar el evento');
    }

    /**
     * Borra un evento. Un evento que ya no existe se considera borrado.
     */
    public function deleteEvent(string $eventId): void
    {
        $response = $this->send('delete', $this->eventUrl($eventId));

        if ($response->successful() || in_array($response->status(), [404, 410], true)) {
            return;
        }

        throw new RuntimeException(
            'Google Calendar rechazo borrar el evento (HTTP ' . $response->status() . '): '
            . mb_substr(trim((string) $response->body()), 0, 300)
        );
    }

    /**
     * Evento puntual (null si Google responde que no existe).
     *
     * @return array<string, mixed>|null
     */
    public function findEvent(string $eventId): ?array
    {
        $response = $this->send('get', $this->eventUrl($eventId));

        if (in_array($response->status(), [404, 410], true)) {
            return null;
        }

        return $this->decode($response, 'consultar el evento');
    }

    /**
     * Lista de eventos (lectura completa o incremental con syncToken).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     *
     * @throws GoogleCalendarSyncTokenExpiredException cuando el syncToken caduco.
     */
    public function listEvents(array $params): array
    {
        $response = $this->send('get', $this->eventsUrl(), ['query' => $params]);

        if ($response->status() === 410) {
            throw new GoogleCalendarSyncTokenExpiredException('El sync token de Google Calendar caduco.');
        }

        return $this->decode($response, 'leer los eventos');
    }

    /**
     * Registra el canal de notificaciones push.
     *
     * Google solo entrega a URLs HTTPS publicas: en local el canal no se puede
     * registrar y la reconciliacion programada es la que revisa los cambios.
     *
     * @return array<string, mixed>
     */
    public function watch(string $webhookUrl): array
    {
        $body = [
            'id' => (string) Str::uuid(),
            'type' => 'web_hook',
            'address' => $webhookUrl,
        ];

        $token = $this->channelToken();

        if ($token !== null) {
            $body['token'] = $token;
        }

        return $this->api('post', $this->eventsUrl() . '/watch', ['json' => $body], 'registrar el canal de notificaciones');
    }

    /**
     * Cierra un canal de notificaciones.
     */
    public function stopChannel(string $channelId, ?string $resourceId = null): void
    {
        $body = ['id' => $channelId];

        if ($resourceId !== null && $resourceId !== '') {
            $body['resourceId'] = $resourceId;
        }

        $response = $this->send('post', $this->apiUrl() . '/channels/stop', ['json' => $body]);

        if ($response->successful() || in_array($response->status(), [404, 410], true)) {
            return;
        }

        throw new RuntimeException(
            'Google Calendar rechazo cerrar el canal de notificaciones (HTTP ' . $response->status() . '): '
            . mb_substr(trim((string) $response->body()), 0, 300)
        );
    }

    /**
     * Client ID configurado (null si falta).
     */
    public function clientId(): ?string
    {
        return $this->parameter('client_id', 'SC-00011');
    }

    /**
     * Client Secret configurado (null si falta).
     */
    public function clientSecret(): ?string
    {
        return $this->parameter('client_secret', 'SC-00012');
    }

    /**
     * Refresh token configurado (null si falta).
     */
    public function refreshToken(): ?string
    {
        return $this->parameter('refresh_token', 'SC-00013');
    }

    /**
     * Peticion al endpoint de tokens de Google (form-urlencoded).
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function tokenRequest(array $body, string $action): array
    {
        $body['client_id'] = (string) $this->clientId();
        $body['client_secret'] = (string) $this->clientSecret();

        try {
            $response = Http::asForm()
                ->timeout((int) $this->config('timeout', 30))
                ->acceptJson()
                ->post((string) $this->config('token_url', 'https://oauth2.googleapis.com/token'), $body);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo conectar con Google para ' . $action . ': ' . $exception->getMessage(), 0, $exception);
        }

        $data = $this->decode($response, $action);

        if (isset($data['error']) && ! isset($data['access_token'])) {
            throw new RuntimeException('Google rechazo ' . $action . ': ' . (string) ($data['error_description'] ?? $data['error']));
        }

        return $data;
    }

    /**
     * Peticion autenticada al API de Calendar que devuelve JSON.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function api(string $method, string $url, array $options, string $action): array
    {
        return $this->decode($this->send($method, $url, $options), $action);
    }

    /**
     * Envia la peticion con el access token vigente.
     *
     * @param array<string, mixed> $options
     */
    private function send(string $method, string $url, array $options = []): Response
    {
        // Fuera del try: si falta configuracion el error debe ser ese, no un
        // "no se pudo conectar".
        $token = $this->accessToken();

        try {
            return Http::withToken($token)
                ->timeout((int) $this->config('timeout', 30))
                ->acceptJson()
                ->send($method, $url, $options);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo conectar con Google Calendar: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Valida la respuesta y devuelve el JSON.
     *
     * @return array<string, mixed>
     */
    private function decode(Response $response, string $action): array
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                'Google Calendar rechazo ' . $action . ' (HTTP ' . $response->status() . '): '
                . mb_substr(trim((string) $response->body()), 0, 300)
            );
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * URL del recurso de eventos del calendario configurado.
     */
    private function eventsUrl(): string
    {
        return $this->apiUrl() . '/calendars/' . rawurlencode($this->calendarId()) . '/events';
    }

    /**
     * URL de un evento puntual.
     */
    private function eventUrl(string $eventId): string
    {
        return $this->eventsUrl() . '/' . rawurlencode($eventId);
    }

    private function apiUrl(): string
    {
        return rtrim((string) $this->config('api_url', 'https://www.googleapis.com/calendar/v3'), '/');
    }

    /**
     * Guarda el access token en cache hasta poco antes de que expire.
     */
    private function cacheAccessToken(string $token, int $expiresIn): void
    {
        $ttl = max(60, $expiresIn - 60);

        Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $token, now()->addSeconds($ttl));
    }

    /**
     * Valor de un parametro del sistema por su clave de configuracion.
     */
    private function parameter(string $key, string $defaultCode): ?string
    {
        $code = $this->config($key . '_parameter', $defaultCode);

        if ($code === '') {
            return null;
        }

        $value = trim((string) Parameter::where('parameter_code', $code)->value('value_default'));

        return $value === '' ? null : $value;
    }

    /**
     * Interpreta un interruptor de los parametros del sistema.
     */
    private function isEnabledFlag(?string $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true'], true);
    }

    /**
     * Valor de configuracion del canal.
     */
    private function config(string $key, mixed $default = null): mixed
    {
        return config('health.google_calendar.' . $key, $default);
    }
}
