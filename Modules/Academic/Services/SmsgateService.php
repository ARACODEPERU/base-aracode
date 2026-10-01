<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Envio de SMS con SMSGate.
 *
 * La aplicacion movil de SMSGate se conecta al servidor (por defecto
 * https://api.sms-gate.app/mobile/v1) y desde ahi los dispositivos recogen y
 * envian los mensajes. Este sistema no habla con el API de dispositivos: envia
 * por el API externo POST {servidor}/3rdparty/v1/messages autenticandose con
 * usuario y contrasena (Basic auth), el camino que documenta SMSGate para
 * integraciones.
 *
 * Parametros del sistema:
 *   - SC-00003: URL del servidor (la misma que usa la aplicacion; admite la
 *     ruta /mobile/v1 y se normaliza a /3rdparty/v1).
 *   - SC-00004: usuario.
 *   - SC-00005: contrasena.
 *
 * Si falta el usuario o la contrasena, isConfigured() devuelve false y la
 * opcion "SMS via SMSGate" no se muestra en la pantalla de Notificaciones.
 */
class SmsgateService
{
    /** @var array{url: string, username: string, password: string}|null */
    private ?array $credentials = null;

    private bool $credentialsResolved = false;

    /**
     * true solo si hay URL, usuario y contrasena utilizables.
     */
    public function isConfigured(): bool
    {
        try {
            return $this->credentials() !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Envia un SMS de texto plano.
     *
     * @param  string $to   Telefono (con o sin "+"; se envia siempre con "+")
     * @param  string $text Mensaje a enviar
     * @return array        Respuesta de SMSGate (id, state, ...)
     *
     * @throws RuntimeException cuando falta configuracion o el servidor rechaza el envio.
     */
    public function send(string $to, string $text): array
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            throw new RuntimeException(
                'Falta configurar SMSGate (URL, usuario y contrasena) en los parametros del sistema '
                . config('academic.notifications.smsgate.url_parameter', 'SC-00003') . ', '
                . config('academic.notifications.smsgate.username_parameter', 'SC-00004') . ' y '
                . config('academic.notifications.smsgate.password_parameter', 'SC-00005') . '.'
            );
        }

        // SMSGate espera E.164 con "+"; el sistema guarda los numeros sin "+".
        $digits = ltrim(trim($to), '+');
        $to = $digits === '' ? '' : '+' . $digits;
        $text = trim($text);

        if ($to === '') {
            throw new RuntimeException('El telefono del destinatario esta vacio.');
        }

        if ($text === '') {
            throw new RuntimeException('El mensaje del SMS esta vacio.');
        }

        try {
            $response = Http::withBasicAuth($credentials['username'], $credentials['password'])
                ->timeout((int) config('academic.notifications.smsgate.timeout', 30))
                ->acceptJson()
                ->post($this->endpoint(), [
                    'phoneNumbers' => [$to],
                    'textMessage' => ['text' => $text],
                ]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo conectar con SMSGate: ' . $exception->getMessage(), 0, $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'SMSGate rechazo el SMS (HTTP ' . $response->status() . '): '
                . mb_substr(trim((string) $response->body()), 0, 300)
            );
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * Credenciales normalizadas de los parametros del sistema.
     *
     * @return array{url: string, username: string, password: string}|null
     */
    public function credentials(): ?array
    {
        if ($this->credentialsResolved) {
            return $this->credentials;
        }

        $this->credentialsResolved = true;

        $url = $this->url();
        $username = $this->parameter('username_parameter', 'SC-00004');
        $password = $this->parameter('password_parameter', 'SC-00005');

        $this->credentials = ($url !== '' && $username !== null && $password !== null)
            ? ['url' => $url, 'username' => $username, 'password' => $password]
            : null;

        return $this->credentials;
    }

    /**
     * URL del servidor de SMSGate (la misma que usa la aplicacion movil).
     */
    public function url(): string
    {
        $value = $this->parameter('url_parameter', 'SC-00003');

        return $value ?? trim((string) config('academic.notifications.smsgate.default_url', 'https://api.sms-gate.app/mobile/v1'));
    }

    /**
     * URL del API externo de envio.
     *
     * Normaliza la URL que usa la aplicacion (termina en /mobile/v1) a la base
     * del servidor y le agrega el path externo, respetando el prefijo /api de
     * los servidores privados:
     *   https://api.sms-gate.app/mobile/v1 -> https://api.sms-gate.app/3rdparty/v1/messages
     *   https://tu-servidor/api/mobile/v1  -> https://tu-servidor/api/3rdparty/v1/messages
     */
    public function endpoint(): string
    {
        $base = rtrim($this->url(), '/');

        $base = preg_replace('#/mobile/v1$#i', '', $base) ?? $base;
        $base = preg_replace('#/mobile$#i', '', $base) ?? $base;
        $base = rtrim($base, '/');

        $path = (string) config('academic.notifications.smsgate.messages_path', '/3rdparty/v1/messages');

        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Valor de un parametro del sistema por su clave de configuracion.
     */
    private function parameter(string $key, string $defaultCode): ?string
    {
        $code = trim((string) config('academic.notifications.smsgate.' . $key, $defaultCode));

        if ($code === '') {
            return null;
        }

        $value = trim((string) Parameter::where('parameter_code', $code)->value('value_default'));

        return $value === '' ? null : $value;
    }
}
