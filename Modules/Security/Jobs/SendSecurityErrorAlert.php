<?php

namespace Modules\Security\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Security\Services\ErrorAlertService;
use Throwable;

/**
 * Envía una alerta de error por Telegram a los chat_id indicados.
 *
 * Corre en la cola (QUEUE_CONNECTION=database y el worker de pm2) para no
 * frenar la petición donde ocurrió el error. El texto ya viene renderizado
 * desde ErrorAlertService, así que el job solo entrega.
 *
 * `$tries = 1`: reintentar volvería a enviar el mismo aviso y duplicaría el
 * mensaje en el chat. Cualquier fallo de entrega se registra en nivel warning
 * con el marcador interno, de modo que no dispare una nueva alerta.
 */
class SendSecurityErrorAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 1;

    /** @var int */
    public $timeout = 60;

    /**
     * @param array<int, string> $chatIds
     */
    public function __construct(
        public array $chatIds,
        public string $text
    ) {
    }

    public function handle(ErrorAlertService $alerts): void
    {
        if ($this->chatIds === [] || trim($this->text) === '') {
            return;
        }

        $alerts->deliverToChatIds($this->chatIds, $this->text);
    }

    /**
     * El envío no debe a su vez generar una alerta.
     */
    public function failed(?Throwable $exception): void
    {
        Log::warning('La alerta de error por Telegram no se pudo procesar en la cola', [
            ErrorAlertService::CONTEXT_MARKER => true,
            'error' => $exception?->getMessage(),
        ]);
    }
}
