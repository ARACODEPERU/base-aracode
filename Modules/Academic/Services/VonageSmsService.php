<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use RuntimeException;
use Vonage\Client as VonageClient;
use Vonage\Client\Credentials\Basic;
use Vonage\Client\Credentials\Keypair;
use Vonage\Client\Exception\Exception as VonageClientException;
use Vonage\Messages\Channel\SMS\SMSText;

/**
 * Envio de SMS con la Messages API de Vonage (https://api.nexmo.com/v1/messages).
 *
 * Se autentica con el API Key + API Secret del panel de Vonage: el parametro
 * del sistema SC-00001 guarda el API Secret y la API Key vive en
 * config/env (VONAGE_API_KEY). Tambien se acepta el formato
 * "api_key:api_secret" o un JSON con api_key, api_secret y sender dentro del
 * propio parametro, y una aplicacion (application_id + private_key) como
 * alternativa JWT.
 *
 * Si no hay credenciales utilizables, isConfigured() devuelve false y la opcion
 * "SMS via Vonage" no se muestra en la interfaz.
 */
class VonageSmsService
{
    /**
     * Credenciales ya resueltas, por codigo de parametro.
     *
     * Se memoiza por instancia (no en el cache de la aplicacion): en una
     * campana el servicio se reutiliza para cientos de envios, pero una edicion
     * del parametro SC-00001 se refleja de inmediato al recargar la pantalla.
     *
     * @var array<string, array{api_key: string|null, api_secret: string|null, application_id: string|null, private_key: string|null, sender: string}|null>
     */
    private array $resolved = [];

    /**
     * true solo si el parametro del sistema tiene credenciales utilizables.
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
     * @param  string $to   Telefono en E.164 sin "+" (lo entrega PhoneNumberFormatter)
     * @param  string $text Mensaje a enviar
     * @return array        Respuesta de Vonage (message_uuid, status, ...)
     *
     * @throws RuntimeException cuando falta configuracion o Vonage rechaza el envio.
     */
    public function send(string $to, string $text): array
    {
        $credentials = $this->credentials();

        if ($credentials === null) {
            throw new RuntimeException(
                'Falta configurar las credenciales de Vonage (Messages API) en el parametro '
                . config('academic.notifications.vonage.parameter', 'SC-00001') . ' del sistema.'
            );
        }

        // Vonage no acepta "+" ni "00" al inicio: solo digitos con codigo de pais.
        $to = ltrim(trim($to), '+');
        $to = str_starts_with($to, '00') ? substr($to, 2) : $to;
        $text = trim($text);

        if ($to === '') {
            throw new RuntimeException('El telefono del destinatario esta vacio.');
        }

        if ($text === '') {
            throw new RuntimeException('El mensaje del SMS esta vacio.');
        }

        try {
            if ($credentials['application_id'] && $credentials['private_key']) {
                // Alternativa JWT: aplicacion de Vonage.
                $client = new VonageClient(new Keypair($credentials['private_key'], $credentials['application_id']));
            } else {
                // Camino normal: API Key + API Secret del panel de Vonage.
                $client = new VonageClient(new Basic($credentials['api_key'], $credentials['api_secret']));
            }

            $response = $client->messages()->send(new SMSText($to, $credentials['sender'], $text));
        } catch (VonageClientException $exception) {
            throw new RuntimeException('Vonage rechazo el SMS: ' . $exception->getMessage(), 0, $exception);
        }

        return is_array($response) ? $response : (array) $response;
    }

    /**
     * Credenciales normalizadas del parametro del sistema.
     *
     * @return array{api_key: string|null, api_secret: string|null, application_id: string|null, private_key: string|null, sender: string}|null
     */
    public function credentials(): ?array
    {
        $parameterCode = (string) config('academic.notifications.vonage.parameter', 'SC-00001');

        if ($parameterCode === '') {
            return null;
        }

        if (! array_key_exists($parameterCode, $this->resolved)) {
            $raw = Parameter::where('parameter_code', $parameterCode)->value('value_default');

            $this->resolved[$parameterCode] = $this->parseCredentials($raw);
        }

        return $this->resolved[$parameterCode];
    }

    /**
     * Interpreta el valor del parametro.
     *
     * Casos aceptados, en orden:
     *   1. JSON con api_key / api_secret / sender.
     *   2. JSON con application_id / private_key (autenticacion JWT).
     *   3. Texto "API_KEY:API_SECRET".
     *   4. El API Secret solo (lo habitual): la API Key se toma de config/env.
     *
     * La lectura es tolerante a un JSON mal formado (por ejemplo con saltos de
     * linea reales dentro de la clave privada) o pegado con texto alrededor.
     */
    private function parseCredentials(?string $raw): ?array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        $data = json_decode($raw, true);

        if (! is_array($data)) {
            $data = $this->extractFromRawText($raw);
        }

        $pairKey = null;
        $pairSecret = null;

        // Texto plano "API_KEY:API_SECRET" o "API_KEY=API_SECRET".
        if (! isset($data['api_key']) && ! isset($data['api_secret'])
            && preg_match('/^([A-Za-z0-9_-]{4,})\s*[:=]\s*([^\s]+)$/', $raw, $matches) === 1) {
            [$pairKey, $pairSecret] = [$matches[1], $matches[2]];
        }

        $apiKey = $this->cleanText($data['api_key'] ?? $pairKey)
            ?? $this->cleanText(config('academic.notifications.vonage.api_key'));
        $apiSecret = $this->cleanText($data['api_secret'] ?? $pairSecret);
        $applicationId = $this->cleanText($data['application_id'] ?? null)
            ?? $this->cleanText(config('academic.notifications.vonage.application_id'));
        $privateKey = $this->normalizePrivateKey($data['private_key'] ?? (str_contains($raw, 'PRIVATE KEY') ? $raw : null));

        // Sin claves explicitas ni clave privada, el parametro contiene tal cual
        // el API Secret que entrega el panel de Vonage.
        if ($apiSecret === null && $privateKey === null) {
            $apiSecret = $this->cleanText($raw);
        }

        $sender = $this->cleanText($data['sender'] ?? null)
            ?? $this->cleanText(config('academic.notifications.vonage.sender'))
            ?? 'Vonage APIs';

        $hasBasic = $apiKey !== null && $apiSecret !== null;
        $hasKeypair = $applicationId !== null && $privateKey !== null;

        if (! $hasBasic && ! $hasKeypair) {
            return null;
        }

        return [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'application_id' => $applicationId,
            'private_key' => $privateKey,
            'sender' => $sender,
        ];
    }

    /**
     * Lectura tolerante de un JSON mal formado o pegado con texto alrededor.
     *
     * @return array<string, string>
     */
    private function extractFromRawText(string $raw): array
    {
        $data = [];

        foreach (['application_id', 'api_key', 'api_secret', 'sender'] as $key) {
            if (preg_match('/"' . $key . '"\s*:\s*"([^"]*)"/', $raw, $matches) === 1) {
                $data[$key] = $matches[1];
            }
        }

        // La clave privada puede traer saltos de linea reales: se toma hasta la
        // comilla que cierra el valor (la siguen una coma o la llave final).
        if (preg_match('/"private_key"\s*:\s*"(.*?)"\s*(?:,|\}|$)/s', $raw, $matches) === 1) {
            $data['private_key'] = $matches[1];
        }

        return $data;
    }

    /**
     * Limpia la clave privada sin tocar su estructura: los saltos de linea del
     * PEM son obligatorios, asi que aqui no se colapsa el espacio interno.
     *
     * Convierte los "\n" pegados como texto en saltos reales y, si el valor
     * viene con texto alrededor, se queda unicamente con la clave PEM.
     */
    private function normalizePrivateKey(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $key = str_replace(['\\r\\n', '\\n', '\\r'], "\n", (string) $value);
        $key = trim($key, " \t\n\r\0\x0B\"'");
        $key = str_replace("\r\n", "\n", $key);

        if (preg_match('/-----BEGIN [A-Z ]*PRIVATE KEY-----.*?-----END [A-Z ]*PRIVATE KEY-----/s', $key, $matches) === 1) {
            $key = $matches[0];
        }

        return trim($key) === '' ? null : trim($key);
    }

    /**
     * Texto utilizable: recorta espacios y caracteres invisibles de la copia.
     */
    private function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[\s\x{200B}-\x{200D}\x{FEFF}\x{00A0}]+/u', ' ', (string) $value) ?? '';

        $clean = trim($clean, " \t\n\r\0\x0B\"'");

        return $clean === '' ? null : $clean;
    }
}
