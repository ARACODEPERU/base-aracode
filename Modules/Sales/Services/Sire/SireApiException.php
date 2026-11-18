<?php

namespace Modules\Sales\Services\Sire;

use RuntimeException;

/**
 * Error controlado de una llamada al SIRE: el mensaje ya viene listo para
 * mostrarse al usuario y conserva el código HTTP, la respuesta de SUNAT y el
 * ticket cuando existen.
 */
class SireApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        string $message,
        private readonly ?int $status = null,
        private readonly array $payload = [],
        private readonly ?string $ticket = null
    ) {
        parent::__construct($message);
    }

    public function status(): ?int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function ticket(): ?string
    {
        return $this->ticket;
    }
}
