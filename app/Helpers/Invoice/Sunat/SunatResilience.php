<?php

namespace App\Helpers\Invoice\Sunat;

use App\Helpers\Invoice\Util;
use App\Models\SaleDocument;
use Greenter\Model\DocumentInterface;
use Greenter\See;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Capa de resiliencia para las llamadas a SUNAT con Greenter.
 *
 * Regla de oro: una venta NUNCA se bloquea por un problema de red. Si el
 * WebService no responde (timeout, cURL, 5xx, SOAP), el comprobante se guarda
 * localmente en cola y el cajero recibe un mensaje amigable; el comando
 * `sunat:retry-queue` lo reenvía cuando el servicio se restablece.
 */
class SunatResilience
{
    public const KIND_TIMEOUT = 'timeout';

    public const KIND_CURL = 'curl';

    public const KIND_SOAP = 'soap';

    public const KIND_HTTP = 'http';

    public const KIND_SUNAT_UNAVAILABLE = 'sunat_no_disponible';

    public const KIND_CERTIFICATE = 'certificado';

    public const KIND_BUSINESS = 'negocio';

    public const KIND_UNKNOWN = 'desconocido';

    /** Código interno cuando la excepción ocurrió antes de hablar con SUNAT. */
    public const CODE_CONNECTION = 'CONNECTION';

    /** Mensaje único que ve el cajero cuando el comprobante queda en cola. */
    public const FRIENDLY_QUEUED = 'La venta se registró correctamente. SUNAT no está respondiendo en este momento, '
        .'así que el comprobante quedó en cola y se enviará automáticamente en segundo plano. '
        .'Puedes entregar la constancia interna al cliente.';

    /**
     * Envía el comprobante a SUNAT sin permitir que una excepción escape.
     *
     * @return array{outcome: SunatSendOutcome, xml: string|null, see: See|null}
     */
    public function send(Util $util, DocumentInterface $document): array
    {
        $see = null;

        try {
            $see = $util->getSee();
            $result = $see->send($document);

            return [
                'outcome' => SunatSendOutcome::fromResult($result),
                'xml' => $this->lastXml($see),
                'see' => $see,
            ];
        } catch (Throwable $e) {
            $classification = $this->classifyException($e);

            Log::warning('SUNAT: fallo de comunicación al enviar comprobante', [
                'document' => method_exists($document, 'getName') ? $document->getName() : null,
                'kind' => $classification['kind'],
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return [
                'outcome' => SunatSendOutcome::fromException($e, $classification),
                'xml' => $see ? $this->lastXml($see) : null,
                'see' => $see,
            ];
        }
    }

    /**
     * Describe un fallo normalizado, sea excepción o error devuelto por Greenter.
     *
     * @return array{retryable: bool, kind: string, technical: string, friendly: string}
     */
    public function describeFailure(SunatSendOutcome $outcome): array
    {
        if ($outcome->hadException()) {
            return [
                'retryable' => $outcome->isRetryable(),
                'kind' => $outcome->kind(),
                'technical' => $outcome->technicalDetail(),
                'friendly' => self::FRIENDLY_QUEUED,
            ];
        }

        $error = $outcome->getError();
        $code = $error?->getCode();
        $message = (string) ($error?->getMessage() ?? '');
        $classified = $this->classifyError($code, $message);

        return [
            'retryable' => $classified['retryable'],
            'kind' => $classified['kind'],
            'technical' => trim('Código '.($code ?? '-').'. '.$message),
            'friendly' => $classified['retryable'] ? self::FRIENDLY_QUEUED : trim($message),
        ];
    }

    /**
     * Clasifica un fallo de red. Todo lo que no sea HTTP/red/SUNAT caído se
     * considera rechazo de negocio (no reintentable automáticamente).
     *
     * @return array{retryable: bool, kind: string}
     */
    public function classifyError(?string $code, ?string $message): array
    {
        $code = trim((string) $code);
        $haystack = mb_strtolower($code.' '.trim((string) $message));

        if ($haystack === ' ') {
            return ['retryable' => true, 'kind' => self::KIND_UNKNOWN];
        }

        // Códigos numéricos de indisponibilidad de SUNAT (incluye los de la capa SOAP).
        $numeric = (int) $code;

        if (in_array($numeric, [109, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 1033], true)) {
            return ['retryable' => true, 'kind' => self::KIND_SUNAT_UNAVAILABLE];
        }

        foreach ([
            self::KIND_TIMEOUT => ['timeout', 'timed out', 'time out', 'tiempo de espera'],
            self::KIND_CERTIFICATE => ['certificate', 'certificado', 'cert.pem', '.pem'],
            self::KIND_CURL => ['curl', 'could not connect', 'failed to open stream', 'connection refused', 'connection reset', 'getaddrinfo', 'network is unreachable', 'ssl'],
            self::KIND_SOAP => ['soap', 'wsdl', 'fault', 'looks like we got no xml'],
            self::KIND_SUNAT_UNAVAILABLE => ['0109', 'no disponible', 'no se encuentra disponible', 'mantenimiento', 'servicio no'],
            self::KIND_HTTP => ['http', 'bad request', 'internal server error', 'service unavailable', 'gateway', '502', '503', '504'],
        ] as $kind => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    return ['retryable' => true, 'kind' => $kind];
                }
            }
        }

        return ['retryable' => false, 'kind' => self::KIND_BUSINESS];
    }

    /**
     * @return array{retryable: bool, kind: string, technical: string, code: string}
     */
    protected function classifyException(Throwable $exception): array
    {
        $message = $exception->getMessage();
        $classified = $this->classifyError(null, $message);

        return [
            'retryable' => true,
            'kind' => $classified['kind'] === self::KIND_BUSINESS ? self::KIND_UNKNOWN : $classified['kind'],
            'technical' => mb_substr(get_class($exception).': '.$message, 0, 2000),
            'code' => self::CODE_CONNECTION,
        ];
    }

    /**
     * Deja el comprobante en cola de reintento (sin tocar el estado de SUNAT).
     */
    public function markQueued(SaleDocument $document, string $kind, string $technical): void
    {
        if (! $this->supportsQueueColumns()) {
            return;
        }

        $attempts = ((int) ($document->sunat_retry_attempts ?? 0)) + 1;

        $document->forceFill([
            'sunat_send_status' => 'reintento',
            'sunat_retry_attempts' => $attempts,
            'sunat_last_attempt_at' => now(),
            'sunat_next_retry_at' => now()->addMinutes($this->backoffMinutes($attempts)),
            'sunat_last_error' => mb_substr($technical, 0, 2000),
            'sunat_response_kind' => mb_substr($kind, 0, 30),
        ]);
    }

    /**
     * Marca el comprobante como enviado (aceptado o rechazado por SUNAT).
     */
    public function markSent(SaleDocument $document, bool $accepted): void
    {
        if (! $this->supportsQueueColumns()) {
            return;
        }

        $document->forceFill([
            'sunat_send_status' => $accepted ? 'aceptado' : 'rechazado',
            'sunat_last_attempt_at' => now(),
            'sunat_next_retry_at' => null,
            'sunat_last_error' => null,
        ]);
    }

    /**
     * Backoff exponencial acotado: 5, 10, 20, 40, 80, 160 y 240 minutos.
     */
    public function backoffMinutes(int $attempts): int
    {
        return (int) min(240, 5 * (2 ** min(max($attempts - 1, 0), 6)));
    }

    public function supportsQueueColumns(): bool
    {
        try {
            return Schema::hasColumn('sale_documents', 'sunat_send_status');
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function lastXml(See $see): ?string
    {
        try {
            return $see->getFactory()->getLastXml();
        } catch (Throwable $e) {
            return null;
        }
    }
}
