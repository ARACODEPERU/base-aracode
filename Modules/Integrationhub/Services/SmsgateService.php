<?php

namespace Modules\Integrationhub\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Http;
use Modules\Integrationhub\Exceptions\SmsgateRejectedException;
use Modules\Integrationhub\Support\PhoneNumberFormatter;
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
 * Las credenciales se leen de parametros del sistema cuyo codigo vive en la
 * configuracion del canal que se le pase en el constructor. Asi el mismo
 * servicio sirve al Academico y a Salud sin duplicar el transporte:
 *
 *   - Academic (prefijo por defecto 'academic.notifications.smsgate'):
 *     SC-00003 (URL), SC-00004 (usuario) y SC-00005 (contrasena).
 *   - Health ('health.notifications.smsgate'):
 *     SC-00006 (URL), SC-00007 (usuario) y SC-00008 (contrasena).
 *
 * Si falta el usuario o la contrasena, isConfigured() devuelve false y la
 * opcion de enviar SMS no se ofrece en la pantalla que use el canal.
 *
 * El telefono se normaliza con PhoneNumberFormatter antes de enviarlo: el
 * sistema guarda los numeros sin el codigo de pais (por ejemplo los 9 digitos
 * del celular peruano) y SMSGate exige E.164 completo. Un numero que no se
 * puede normalizar, o un rechazo 4xx del servidor, se informa con
 * SmsgateRejectedException: reintentar no sirve, hay que corregir el dato.
 */
class SmsgateService
{
    /**
     * Prefijo de configuracion del canal (por defecto, el del modulo Academico).
     *
     * @param string $configPrefix clave base de config, por ejemplo
     *                             'academic.notifications.smsgate'
     */
    public function __construct(
        private readonly string $configPrefix = 'academic.notifications.smsgate',
    ) {
    }

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
     * @param  string $to   Telefono (con o sin "+", con o sin codigo de pais)
     * @param  string $text Mensaje a enviar
     * @return array        Respuesta de SMSGate (id, state, ...)
     *
     * @throws SmsgateRejectedException cuando el numero o el mensaje no son
     *                                  utilizables, o el servidor responde 4xx.
     * @throws RuntimeException cuando falta configuracion o el fallo es
     *                          transitorio (red, timeout o 5xx).
     */
    public function send(string $to, string $text): array
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            throw new RuntimeException(
                'Falta configurar SMSGate (URL, usuario y contrasena) en los parametros del sistema '
                . $this->config('url_parameter', 'SC-00003') . ', '
                . $this->config('username_parameter', 'SC-00004') . ' y '
                . $this->config('password_parameter', 'SC-00005') . '.'
            );
        }

        // SMSGate espera E.164 con "+"; se completa el codigo de pais cuando el
        // numero se guardo sin el (el celular peruano de 9 digitos, por ejemplo).
        $number = PhoneNumberFormatter::toE164($to, null, $this->countryCode());
        $text = trim($text);

        if ($number === null) {
            throw new SmsgateRejectedException(
                'El telefono del destinatario no es utilizable para SMS: "' . mb_substr(trim($to), 0, 30) . '".'
            );
        }

        $to = '+' . $number;

        if ($text === '') {
            throw new SmsgateRejectedException('El mensaje del SMS esta vacio.');
        }

        try {
            $response = Http::withBasicAuth($credentials['username'], $credentials['password'])
                ->timeout((int) $this->config('timeout', 30))
                ->acceptJson()
                ->post($this->endpoint(), [
                    'phoneNumbers' => [$to],
                    'textMessage' => ['text' => $text],
                ]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('No se pudo conectar con SMSGate: ' . $exception->getMessage(), 0, $exception);
        }

        if (! $response->successful()) {
            $message = 'SMSGate rechazo el SMS (HTTP ' . $response->status() . '): '
                . mb_substr(trim((string) $response->body()), 0, 300);

            // 4xx (por ejemplo el 400 "invalid phone number"): el numero o el
            // mensaje no sirven, reintentar no cambia nada. Los 5xx y los
            // timeouts si son transitorios y se reintentan en la cola.
            if ($response->clientError()) {
                throw new SmsgateRejectedException($message);
            }

            throw new RuntimeException($message);
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

        return $value ?? trim((string) $this->config('default_url', 'https://api.sms-gate.app/mobile/v1'));
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

        $path = (string) $this->config('messages_path', '/3rdparty/v1/messages');

        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Valor de un parametro del sistema por su clave de configuracion.
     */
    private function parameter(string $key, string $defaultCode): ?string
    {
        $code = trim((string) $this->config($key, $defaultCode));

        if ($code === '') {
            return null;
        }

        $value = trim((string) Parameter::where('parameter_code', $code)->value('value_default'));

        return $value === '' ? null : $value;
    }

    /**
     * Codigo de pais que se antepone a los numeros guardados sin el.
     */
    private function countryCode(): string
    {
        $code = preg_replace('/\D+/', '', (string) $this->config('country_code', '51')) ?? '';

        return $code === '' ? '51' : $code;
    }

    /**
     * Valor de configuracion del canal (con el prefijo del constructor).
     */
    private function config(string $key, mixed $default = null): mixed
    {
        return config($this->configPrefix . '.' . $key, $default);
    }
}
