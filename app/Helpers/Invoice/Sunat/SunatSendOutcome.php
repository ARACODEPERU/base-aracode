<?php

namespace App\Helpers\Invoice\Sunat;

use Greenter\Model\Response\CdrResponse;
use Greenter\Model\Response\Error;
use Throwable;

/**
 * Resultado normalizado del envio a SUNAT.
 *
 * Si Greenter devolvio un resultado, se delega en el; si la llamada lanzo una
 * excepcion (timeout, cURL, SOAP, certificado), se sintetiza un error con el
 * mismo contrato que usa el codigo existente (isSuccess/getError/getCdrResponse/
 * getCdrZip/getTicket), de modo que el flujo de venta nunca se rompe.
 */
class SunatSendOutcome
{
    public function __construct(
        private readonly ?object $result = null,
        private readonly ?Throwable $exception = null,
        private readonly bool $retryable = false,
        private readonly string $kind = SunatResilience::KIND_UNKNOWN,
        private readonly string $technical = '',
        private readonly ?string $errorCode = null,
        private readonly ?string $errorMessage = null,
    ) {
    }

    public static function fromResult(object $result): self
    {
        return new self(result: $result);
    }

    public static function fromException(Throwable $exception, array $classification): self
    {
        return new self(
            exception: $exception,
            retryable: (bool) ($classification['retryable'] ?? true),
            kind: (string) ($classification['kind'] ?? SunatResilience::KIND_UNKNOWN),
            technical: (string) ($classification['technical'] ?? $exception->getMessage()),
            errorCode: (string) ($classification['code'] ?? SunatResilience::CODE_CONNECTION),
            errorMessage: (string) ($classification['technical'] ?? $exception->getMessage()),
        );
    }

    public function hadException(): bool
    {
        return $this->exception !== null;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function technicalDetail(): string
    {
        return $this->technical;
    }

    public function isSuccess(): bool
    {
        return $this->result !== null && (bool) $this->result->isSuccess();
    }

    public function getError(): ?Error
    {
        if ($this->errorCode !== null || $this->errorMessage !== null) {
            return new Error($this->errorCode ?? '', $this->errorMessage ?? '');
        }

        return $this->result && method_exists($this->result, 'getError')
            ? $this->result->getError()
            : null;
    }

    public function getCdrResponse(): ?CdrResponse
    {
        return $this->result && method_exists($this->result, 'getCdrResponse')
            ? $this->result->getCdrResponse()
            : null;
    }

    public function getCdrZip(): ?string
    {
        return $this->result && method_exists($this->result, 'getCdrZip')
            ? $this->result->getCdrZip()
            : null;
    }

    public function getTicket(): ?string
    {
        return $this->result && method_exists($this->result, 'getTicket')
            ? $this->result->getTicket()
            : null;
    }
}
