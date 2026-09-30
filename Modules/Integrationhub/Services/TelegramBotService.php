<?php

namespace Modules\Integrationhub\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Cache;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use RuntimeException;

/**
 * Bot de Telegram a traves de Integrationhub.
 *
 * La API de Telegram exige el token dentro de la URL (bot<token>/sendMessage),
 * asi que el token del parametro del sistema (SC-00002 por defecto) se inyecta
 * como variable de ruta en cada llamada a runEndpoint: asi el envio queda en el
 * Historial de Integrationhub igual que los flujos de WhatsApp y el bot solo
 * aparece habilitado cuando hay token.
 *
 * El secreto del webhook se deriva del propio token (HMAC), de modo que no hace
 * falta configurar un parametro adicional: el mismo valor que se envia en
 * setWebhook es el que valida el controlador que recibe los mensajes.
 */
class TelegramBotService
{
    /** Clave de cache del usuario publico del bot. */
    private const USERNAME_CACHE_KEY = 'integrationhub.telegram.bot_username';

    /** Token ya resuelto en esta instancia (no se cachea en la aplicacion). */
    private ?string $resolvedToken = null;

    private bool $tokenResolved = false;

    /**
     * true solo si el parametro del sistema tiene un token cargado.
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
     * Token del bot normalizado, o null si todavia no se configuro.
     */
    public function token(): ?string
    {
        if ($this->tokenResolved) {
            return $this->resolvedToken;
        }

        $this->tokenResolved = true;

        $parameterCode = trim((string) config('integrationhub.telegram.parameter', 'SC-00002'));

        if ($parameterCode === '') {
            return $this->resolvedToken = null;
        }

        $raw = Parameter::where('parameter_code', $parameterCode)->value('value_default');
        $token = trim((string) $raw);

        return $this->resolvedToken = $token === '' ? null : $token;
    }

    /**
     * Secreto compartido con Telegram para validar el webhook.
     *
     * Se deriva del token para no crear otro parametro: si el token cambia, el
     * webhook se debe registrar de nuevo (lo hace el comando del modulo).
     */
    public function secret(): ?string
    {
        $token = $this->token();

        if ($token === null) {
            return null;
        }

        return substr(hash_hmac('sha256', 'telegram_bot_webhook', $token), 0, 40);
    }

    /**
     * Usuario publico del bot (sin @) para armar los enlaces t.me.
     *
     * Se consulta con getMe y se cachea porque el dato cambia muy rara vez; un
     * fallo de red devuelve null sin romper la pantalla.
     */
    public function username(bool $refresh = false): ?string
    {
        if ($refresh) {
            Cache::forget(self::USERNAME_CACHE_KEY);
        }

        $cached = $this->cachedUsername();

        if ($cached !== null) {
            return $cached;
        }

        if ($this->token() === null) {
            return null;
        }

        try {
            $body = $this->run($this->endpoint('get_me'), [], false);
            $username = trim((string) ($body['result']['username'] ?? ''));

            if ($username === '') {
                return null;
            }

            $minutes = max(1, (int) config('integrationhub.telegram.username_cache_minutes', 1440));
            Cache::put(self::USERNAME_CACHE_KEY, $username, now()->addMinutes($minutes));

            return $username;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Usuario del bot ya conocido, sin consultar Telegram.
     *
     * Se usa para pintar la pantalla sin exponerla a un timeout de red: la
     * primera consulta la hacen las acciones explicitas (generar enlaces o
     * registrar el webhook) y el dato queda cacheado.
     */
    public function cachedUsername(): ?string
    {
        $cached = Cache::get(self::USERNAME_CACHE_KEY);

        return is_string($cached) && $cached !== '' ? $cached : null;
    }

    /**
     * Enlace unico de registro: el mismo para todas las personas.
     *
     * No lleva ningun codigo: al abrirlo, Telegram abre el chat del bot y la
     * persona pulsa Iniciar (o escribe /start); el bot le pide su documento y lo
     * valida contra el padron antes de guardar el chat_id.
     */
    public function registrationLink(): ?string
    {
        $username = $this->username();

        return $username === null ? null : $this->link($username);
    }

    /**
     * Enlace de registro ya conocido, sin consultar Telegram.
     *
     * Lo usa la pantalla de notificaciones para mostrarlo sin exponerse a un
     * timeout de red cuando el usuario del bot ya esta cacheado.
     */
    public function cachedRegistrationLink(): ?string
    {
        $username = $this->cachedUsername();

        return $username === null ? null : $this->link($username);
    }

    /**
     * Arma el deep link del bot para el usuario indicado.
     */
    private function link(string $username): string
    {
        return 'https://t.me/' . ltrim($username, '@');
    }

    /**
     * URL publica que se registra en Telegram con setWebhook.
     */
    public function webhookUrl(): string
    {
        $path = (string) config('integrationhub.telegram.webhook_path', 'api/integrationhub/telegram/webhook');

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Envia un mensaje de texto plano a un chat.
     *
     * No se usa parse_mode a proposito: el texto de las campanas lo escribe el
     * administrador y una sola etiqueta HTML invalida haria fallar el envio.
     *
     * @return array Respuesta de Telegram (result = mensaje enviado)
     *
     * @throws RuntimeException cuando falta el token o Telegram rechaza el envio.
     */
    public function sendMessage(string $chatId, string $text): array
    {
        $chatId = trim($chatId);
        $text = trim($text);

        if ($chatId === '') {
            throw new RuntimeException('El chat_id del destinatario está vacío.');
        }

        if ($text === '') {
            throw new RuntimeException('El mensaje de Telegram está vacío.');
        }

        return $this->run($this->endpoint('send_message'), [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    /**
     * Registra el webhook del bot apuntando a esta aplicacion.
     */
    public function setWebhook(string $url, bool $dropPendingUpdates = true): array
    {
        $secret = $this->secret();

        if ($secret === null) {
            throw new RuntimeException(
                'Falta el token del bot en el parámetro '
                . config('integrationhub.telegram.parameter', 'SC-00002') . ' del sistema.'
            );
        }

        return $this->run($this->endpoint('set_webhook'), [
            'url' => $url,
            'secret_token' => $secret,
            'drop_pending_updates' => $dropPendingUpdates,
        ]);
    }

    /**
     * Quita el webhook (util en local, para volver a getUpdates).
     */
    public function deleteWebhook(bool $dropPendingUpdates = false): array
    {
        return $this->run($this->endpoint('delete_webhook'), [
            'drop_pending_updates' => $dropPendingUpdates,
        ]);
    }

    /**
     * Publica el menu de comandos del bot.
     *
     * @param array<int, array{command: string, description: string}> $commands
     */
    public function setMyCommands(array $commands): array
    {
        return $this->run($this->endpoint('set_my_commands'), [
            'commands' => array_values($commands),
        ]);
    }

    /**
     * Comandos que se publican en el menu del bot.
     *
     * @return array<int, array{command: string, description: string}>
     */
    public function defaultCommands(): array
    {
        return [
            ['command' => 'start', 'description' => 'Registrar este chat con tu documento'],
            ['command' => 'baja', 'description' => 'Dejar de recibir mensajes del bot'],
            ['command' => 'ayuda', 'description' => 'Cómo activar los avisos en este chat'],
        ];
    }

    /**
     * Ejecuta un endpoint de la integracion Telegram_bot.
     *
     * runEndpoint responde JSON en lugar de lanzar excepcion, por eso aqui se
     * revisan el codigo HTTP y el cuerpo: Telegram responde HTTP 400 con
     * {"ok":false,"description":"..."} cuando el token, el chat o el texto no
     * son validos.
     *
     * @throws RuntimeException cuando la llamada falla.
     */
    private function run(string $endpoint, array $fieldValues, bool $trackResults = true): array
    {
        $token = $this->token();

        if ($token === null) {
            throw new RuntimeException(
                'Falta configurar el token del bot de Telegram en el parámetro '
                . config('integrationhub.telegram.parameter', 'SC-00002') . ' del sistema.'
            );
        }

        $response = app(IntegrationhubController::class)->runEndpoint(
            $endpoint,
            array_merge(['token' => $token], $fieldValues),
            [],
            $trackResults
        );

        $payload = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
        $status = method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : 200;
        $body = $payload['response'] ?? ($payload['received']['body'] ?? null);

        if ($status >= 400) {
            $message = $payload['message'] ?? 'Error desconocido';

            throw new RuntimeException(
                'Telegram respondió HTTP ' . $status . ': '
                . (is_scalar($message) ? $message : json_encode($message))
            );
        }

        if (is_array($body)) {
            if (($body['ok'] ?? null) === false) {
                throw new RuntimeException(
                    'Telegram rechazó la petición: ' . trim((string) ($body['description'] ?? json_encode($body)))
                );
            }

            if (! empty($body['error'])) {
                $detail = is_array($body['error'])
                    ? ($body['error']['message'] ?? json_encode($body['error']))
                    : (string) $body['error'];

                throw new RuntimeException('Telegram respondió con error: ' . trim($detail));
            }
        }

        return is_array($body) ? $body : [];
    }

    /**
     * Nombre del endpoint configurado para una accion.
     */
    private function endpoint(string $key): string
    {
        return (string) config('integrationhub.telegram.endpoints.' . $key, 'telegram_' . $key);
    }
}
