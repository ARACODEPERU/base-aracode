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
 * WebService no responde, el comprobante se guarda como pendiente para que el
 * usuario lo reenvíe manualmente; no se encola ni se reenvía automáticamente.
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

    /** El comprobante ya existe en SUNAT con otros datos (código 1033). */
    public const KIND_DUPLICATE = 'duplicado';

    public const KIND_UNKNOWN = 'desconocido';

    /** Código interno cuando la excepción ocurrió antes de hablar con SUNAT. */
    public const CODE_CONNECTION = 'CONNECTION';

    /** Mensaje alternativo cuando SUNAT no responde por red o indisponibilidad. */
    public const FRIENDLY_QUEUED = 'El comprobante se registró correctamente, pero no se pudo enviar a SUNAT en este '
        .'momento. Quedó pendiente para que lo reenvíes manualmente. Puedes entregar la constancia interna al cliente.';

    /**
     * El certificado digital es un problema local, no de SUNAT. El comprobante
     * igual queda en cola para reenviarlo cuando se restaure el archivo.
     */
    public const FRIENDLY_CERTIFICATE = 'El comprobante se registró correctamente, pero el problema no es SUNAT: '
        .'no se pudo cargar el certificado digital del emisor. Revisa que el certificado esté cargado en la '
        .'configuración de la empresa; el comprobante quedó pendiente para que lo reenvíes manualmente.';

    /**
     * Fallo reintentable que no se pudo identificar como caída de SUNAT. No se
     * asegura que SUNAT esté caído: solo que no se confirmó la respuesta.
     */
    public const FRIENDLY_UNCONFIRMED = 'El comprobante se registró correctamente, pero no se pudo completar el envío '
        .'a SUNAT. Quedó pendiente para que lo reenvíes manualmente. Puedes entregar la constancia interna al cliente.';

    /** Código 1033: SUNAT rechaza porque el comprobante ya estaría registrado. */
    public const FRIENDLY_DUPLICATE = 'El comprobante se registró correctamente, pero SUNAT indica que '
        .'posiblemente ya fue registrado antes con otros datos. No se reenviará automáticamente: revisa la '
        .'constancia (CDR) en SUNAT antes de emitir otro comprobante.';

    /** Falla al preparar el envío (certificado/configuración): no es SUNAT. */
    public const FRIENDLY_LOCAL_SETUP = 'La guía se registró correctamente, pero no se pudo preparar el envío a SUNAT '
        .'por un problema de configuración local (certificado o datos de la empresa). No es una caída de SUNAT: '
        .'corrige la configuración y reenvía la guía.';

    /**
     * Envía el comprobante a SUNAT sin permitir que una excepción escape.
     *
     * @return array{outcome: SunatSendOutcome, xml: string|null, see: See|null}
     */
    public function send(Util $util, DocumentInterface $document): array
    {
        try {
            $see = $util->getSee();
        } catch (Throwable $e) {
            return $this->failure($e, null, $document);
        }

        return $this->sendWith($see, $document, $util);
    }

    /**
     * Igual que send(), pero con un See ya configurado (por ejemplo guías de
     * remisión, que usan otro WebService).
     *
     * @return array{outcome: SunatSendOutcome, xml: string|null, see: See|null}
     */
    public function sendWith(See $see, DocumentInterface $document, ?Util $util = null): array
    {
        try {
            $result = $see->send($document);
        } catch (Throwable $e) {
            return $this->failure($e, $see, $document);
        }

        return [
            'outcome' => SunatSendOutcome::fromResult($result),
            'xml' => $this->lastXml($see),
            'see' => $see,
        ];
    }

    /**
     * @return array{outcome: SunatSendOutcome, xml: string|null, see: See|null}
     */
    protected function failure(Throwable $e, ?See $see, DocumentInterface $document): array
    {
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

    /**
     * Describe un fallo normalizado, sea excepción o error devuelto por Greenter.
     *
     * @return array{retryable: bool, kind: string, technical: string, friendly: string}
     */
    public function describeFailure(SunatSendOutcome $outcome): array
    {
        if ($outcome->hadException()) {
            $kind = $outcome->kind();
            $technical = $outcome->technicalDetail();

            return [
                'retryable' => $outcome->isRetryable(),
                'kind' => $kind,
                'technical' => $technical,
                'friendly' => $this->friendlyForFailure($kind, $technical),
            ];
        }

        $error = $outcome->getError();
        $code = $error?->getCode();
        $message = (string) ($error?->getMessage() ?? '');
        $technical = trim('Código '.($code ?? '-').'. '.$message);

        // 1033 no es una caída: SUNAT ya tendría el comprobante con otros datos.
        if ($this->isDuplicate($code, $message)) {
            return [
                'retryable' => false,
                'kind' => self::KIND_DUPLICATE,
                'technical' => $technical,
                'friendly' => self::FRIENDLY_DUPLICATE,
            ];
        }

        $classified = $this->classifyError($code, $message);

        return [
            'retryable' => $classified['retryable'],
            'kind' => $classified['kind'],
            'technical' => $technical,
            'friendly' => $classified['retryable']
                ? $this->friendlyForFailure($classified['kind'], $message)
                : trim($message),
        ];
    }

    /**
     * Solo los fallos de red o de indisponibilidad de SUNAT usan el mensaje
     * "SUNAT no está respondiendo". Un SOAP fault, un HTTP 400 o un error
     * desconocido reciben un mensaje neutral.
     */
    public function friendlyForFailure(string $kind, string $technical): string
    {
        if ($kind === self::KIND_CERTIFICATE) {
            return self::FRIENDLY_CERTIFICATE;
        }

        return $this->isAvailabilityFailure($kind, $technical)
            ? self::FRIENDLY_QUEUED
            : self::FRIENDLY_UNCONFIRMED;
    }

    public function isAvailabilityFailure(string $kind, string $technical): bool
    {
        if (in_array($kind, [self::KIND_TIMEOUT, self::KIND_CURL, self::KIND_SUNAT_UNAVAILABLE], true)) {
            return true;
        }

        if ($kind !== self::KIND_HTTP) {
            return false;
        }

        $texto = mb_strtolower($technical);

        foreach (['http 500', '502', '503', '504', 'bad gateway', 'gateway timeout', 'service unavailable', 'internal server error', 'servidor no disponible'] as $needle) {
            if (str_contains($texto, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function isDuplicate(?string $code, ?string $message): bool
    {
        if (preg_match('/(?<!\d)1033(?!\d)/', (string) $code) === 1) {
            return true;
        }

        $texto = mb_strtolower((string) $message);

        return str_contains($texto, 'registrado previamente')
            || str_contains($texto, 'registrado con otros datos');
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
        // El 1033 NO va aquí: significa «El comprobante fue registrado previamente con
        // otros datos», un rechazo de negocio. Reintentarlo solo repite el error.
        $numeric = (int) $code;

        if (in_array($numeric, [109, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139], true)) {
            return ['retryable' => true, 'kind' => self::KIND_SUNAT_UNAVAILABLE];
        }

        foreach ([
            self::KIND_TIMEOUT => ['timeout', 'timed out', 'time out', 'tiempo de espera'],
            self::KIND_CERTIFICATE => ['certificate', 'certificado', 'cert.pem', '.pem'],
            self::KIND_CURL => ['curl', 'could not connect', 'failed to open stream', 'connection refused', 'connection reset', 'getaddrinfo', 'network is unreachable', 'ssl'],
            self::KIND_SOAP => ['soap', 'wsdl', 'soapfault', 'looks like we got no xml'],
            self::KIND_SUNAT_UNAVAILABLE => ['0109', 'no disponible', 'no se encuentra disponible', 'mantenimiento', 'servicio no'],
            self::KIND_HTTP => ['bad request', 'internal server error', 'service unavailable', 'gateway', 'http 500', '502', '503', '504'],
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

        if ($this->isDuplicate(null, $message)) {
            return [
                'retryable' => false,
                'kind' => self::KIND_DUPLICATE,
                'technical' => mb_substr(get_class($exception).': '.$message, 0, 2000),
                'code' => self::CODE_CONNECTION,
            ];
        }

        $classified = $this->classifyError(null, $message);

        return [
            'retryable' => true,
            'kind' => $classified['kind'] === self::KIND_BUSINESS ? self::KIND_UNKNOWN : $classified['kind'],
            'technical' => mb_substr(get_class($exception).': '.$message, 0, 2000),
            'code' => self::CODE_CONNECTION,
        ];
    }

    /**
     * Deja el comprobante pendiente para reenvío manual: fuera de la cola
     * automática y sin próximo intento programado.
     */
    public function markPendingManual(SaleDocument $document, string $kind, string $technical): void
    {
        if (! $this->supportsQueueColumns()) {
            return;
        }

        $document->forceFill([
            'sunat_send_status' => 'manual',
            'sunat_last_attempt_at' => now(),
            'sunat_next_retry_at' => null,
            'sunat_last_error' => mb_substr($technical, 0, 2000),
            'sunat_response_kind' => mb_substr($kind, 0, 30),
        ]);
    }

    /**
     * Deja el comprobante en cola de reintento (documentos antiguos).
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
     * Código 1033: el comprobante posiblemente ya está registrado en SUNAT.
     * Sale de la cola automática (queda como "revisar") para no reenviarlo.
     */
    public function markDuplicate(SaleDocument $document, string $code): void
    {
        if (! $this->supportsQueueColumns()) {
            return;
        }

        $document->forceFill([
            'sunat_send_status' => 'revisar',
            'sunat_last_attempt_at' => now(),
            'sunat_next_retry_at' => null,
            'sunat_last_error' => mb_substr(
                'SUNAT '.$code.': el comprobante posiblemente ya está registrado con otros datos.',
                0,
                2000
            ),
            'sunat_response_kind' => self::KIND_DUPLICATE,
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
            'sunat_response_kind' => $accepted ? null : self::KIND_BUSINESS,
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
