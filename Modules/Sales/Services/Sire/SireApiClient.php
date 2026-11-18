<?php

namespace Modules\Sales\Services\Sire;

use App\Models\Company;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Sales\Entities\SireSubmission;
use Modules\Sales\Entities\SireSubmissionLog;

/**
 * Cliente HTTP del SIRE de SUNAT.
 *
 * - Obtiene el token OAuth2 con las credenciales de la empresa y lo cachea
 *   hasta un minuto antes de vencer.
 * - Ante un 401 renueva el token una sola vez; ante 429/5xx reintenta con
 *   espera creciente.
 * - Cada llamada deja una fila en `sire_submission_logs`, sin tokens ni
 *   credenciales en el payload.
 *
 * El SIRE no tiene ambiente de pruebas: este cliente apunta al host de
 * producción y las pruebas se hacen con `Http::fake` y fixtures.
 */
class SireApiClient
{
    public const API_BASE = 'https://api-sire.sunat.gob.pe';

    public const TOKEN_BASE = 'https://api-seguridad.sunat.gob.pe/v1/clientessol';

    public const SCOPE = 'https://api-sire.sunat.gob.pe';

    private const MAX_INTENTOS = 3;

    /** Campos cifrados que forman una credencial completa. */
    private const CAMPOS_CREDENCIAL = [
        'sire_client_id',
        'sire_client_secret',
        'sire_sol_user',
        'sire_sol_key',
    ];

    /**
     * Indica si la empresa ya cargó las cuatro piezas de la credencial SOL.
     */
    public function credencialesCompletas(Company $company): bool
    {
        foreach (self::CAMPOS_CREDENCIAL as $campo) {
            if (trim((string) $company->{$campo}) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Token vigente de la empresa, cacheado entre llamadas.
     */
    public function token(Company $company, ?SireSubmission $submission = null): string
    {
        $guardado = Cache::get($this->claveToken($company));

        if (is_string($guardado) && $guardado !== '') {
            return $guardado;
        }

        return $this->solicitarToken($company, $submission);
    }

    public function olvidarToken(Company $company): void
    {
        Cache::forget($this->claveToken($company));
    }

    /**
     * Llamada GET que devuelve el JSON decodificado.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(
        Company $company,
        string $path,
        array $query,
        string $action,
        ?SireSubmission $submission = null
    ): array {
        $json = $this->enviar($company, 'GET', $path, $query, $action, $submission)->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Llamada GET que devuelve el cuerpo tal cual (ZIP o TXT de la propuesta).
     *
     * @param  array<string, mixed>  $query
     */
    public function descargar(
        Company $company,
        string $path,
        array $query,
        string $action,
        ?SireSubmission $submission = null
    ): string {
        return $this->enviar($company, 'GET', $path, $query, $action, $submission)->body();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function enviar(
        Company $company,
        string $method,
        string $path,
        array $query,
        string $action,
        ?SireSubmission $submission
    ): Response {
        $renovado = false;

        for ($intento = 1; $intento <= self::MAX_INTENTOS; $intento++) {
            $token = $this->token($company, $submission);
            $inicio = microtime(true);

            try {
                $request = Http::withToken($token)->acceptJson()->timeout(60);

                $respuesta = $method === 'GET'
                    ? $request->get(self::API_BASE . $path, $query)
                    : $request->post(self::API_BASE . $path, $query);
            } catch (ConnectionException $e) {
                $this->registrar(
                    $company,
                    $submission,
                    $action,
                    $method,
                    $path,
                    null,
                    false,
                    'Sin conexión con SUNAT: ' . $e->getMessage(),
                    $this->duracion($inicio)
                );

                if ($intento < self::MAX_INTENTOS) {
                    usleep(250000 * $intento);

                    continue;
                }

                throw new SireApiException(
                    'No se pudo conectar con SUNAT. Revisa la salida a internet del servidor e inténtalo de nuevo.',
                    null,
                    [],
                    $submission?->ticket
                );
            }

            $status = $respuesta->status();
            $ok = $respuesta->successful();
            $mensaje = $this->mensaje($respuesta);

            $this->registrar(
                $company,
                $submission,
                $action,
                $method,
                $path,
                $status,
                $ok,
                $mensaje,
                $this->duracion($inicio)
            );

            // Token vencido o revocado: se renueva una vez y se repite.
            if ($status === 401 && ! $renovado) {
                $this->olvidarToken($company);
                $renovado = true;

                continue;
            }

            // SUNAT saturada o error temporal: espera creciente y reintento.
            if (in_array($status, [429, 500, 502, 503, 504], true) && $intento < self::MAX_INTENTOS) {
                usleep(250000 * $intento);

                continue;
            }

            if (! $ok) {
                throw new SireApiException(
                    $mensaje,
                    $status,
                    is_array($respuesta->json()) ? $respuesta->json() : [],
                    $submission?->ticket
                );
            }

            return $respuesta;
        }

        throw new SireApiException(
            'SUNAT no respondió después de varios intentos. Inténtalo más tarde.',
            null,
            [],
            $submission?->ticket
        );
    }

    private function solicitarToken(Company $company, ?SireSubmission $submission): string
    {
        if (! $this->credencialesCompletas($company)) {
            throw new SireApiException(
                'Faltan las credenciales del SIRE. Carga el client_id, el client_secret y el usuario y clave SOL en Configuración → SIRE.'
            );
        }

        $inicio = microtime(true);

        $respuesta = Http::asForm()
            ->acceptJson()
            ->timeout(30)
            ->post($this->urlToken($company), [
                'grant_type' => 'password',
                'scope' => self::SCOPE,
                'client_id' => (string) $company->sire_client_id,
                'client_secret' => (string) $company->sire_client_secret,
                'username' => $this->ruc($company) . (string) $company->sire_sol_user,
                'password' => (string) $company->sire_sol_key,
            ]);

        $this->registrar(
            $company,
            $submission,
            'token',
            'POST',
            'oauth2/token',
            $respuesta->status(),
            $respuesta->successful(),
            $this->mensaje($respuesta),
            $this->duracion($inicio)
        );

        if (! $respuesta->successful()) {
            throw new SireApiException(
                'SUNAT rechazó las credenciales: ' . $this->mensaje($respuesta),
                $respuesta->status(),
                is_array($respuesta->json()) ? $respuesta->json() : []
            );
        }

        $datos = (array) $respuesta->json();
        $token = (string) ($datos['access_token'] ?? '');

        if ($token === '') {
            throw new SireApiException('SUNAT no devolvió el token de acceso. Revisa las credenciales y el scope autorizado.');
        }

        // Se descuenta un minuto de margen para no usarlo en el límite.
        $segundos = max(60, (int) ($datos['expires_in'] ?? 3600) - 60);
        Cache::put($this->claveToken($company), $token, now()->addSeconds($segundos));

        return $token;
    }

    private function urlToken(Company $company): string
    {
        return self::TOKEN_BASE . '/' . rawurlencode((string) $company->sire_client_id) . '/oauth2/token/';
    }

    private function claveToken(Company $company): string
    {
        // El client_id forma parte de la clave: si se rota en SOL, el token
        // viejo deja de usarse.
        return 'sire:token:' . $company->getKey() . ':' . sha1((string) $company->sire_client_id);
    }

    private function ruc(Company $company): string
    {
        return (string) preg_replace('/\D/', '', (string) $company->ruc);
    }

    private function mensaje(Response $respuesta): string
    {
        $json = $respuesta->json();

        if (is_array($json)) {
            foreach (['message', 'error_description', 'error', 'mensaje', 'descripcion', 'detail'] as $clave) {
                if (isset($json[$clave]) && is_scalar($json[$clave]) && (string) $json[$clave] !== '') {
                    return Str::limit((string) $json[$clave], 400);
                }
            }

            $errores = $json['errors'] ?? null;

            if (is_array($errores)) {
                $primero = $errores[0] ?? null;

                if (is_array($primero) && isset($primero['message'])) {
                    return Str::limit((string) $primero['message'], 400);
                }
            }
        }

        return 'SUNAT respondió HTTP ' . $respuesta->status();
    }

    private function registrar(
        Company $company,
        ?SireSubmission $submission,
        string $action,
        string $method,
        string $endpoint,
        ?int $status,
        bool $success,
        string $message,
        int $durationMs
    ): void {
        try {
            SireSubmissionLog::create([
                'sire_submission_id' => $submission?->id,
                'company_id' => $company->getKey(),
                'action' => $action,
                'method' => $method,
                'endpoint' => Str::limit($endpoint, 480),
                'http_status' => $status,
                'success' => $success,
                'message' => Str::limit($message, 900),
                'duration_ms' => $durationMs,
                'payload' => ['mensaje' => Str::limit($message, 400)],
            ]);
        } catch (\Throwable) {
            // La bitácora no debe tumbar una llamada que ya se hizo a SUNAT.
        }
    }

    private function duracion(float $inicio): int
    {
        return (int) round((microtime(true) - $inicio) * 1000);
    }
}
