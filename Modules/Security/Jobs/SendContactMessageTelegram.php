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
 * Envía por Telegram el aviso de un mensaje nuevo del formulario de contacto.
 *
 * Corre en la cola para no frenar la respuesta del formulario. El texto ya viene
 * renderizado y escapado desde ContactMessageTelegramNotifier, así que el job
 * solo entrega; así el mensaje que ve el chat es el mismo que se probó.
 *
 * `$tries = 1`: reintentar duplicaría el aviso en el chat. Cualquier fallo se
 * registra en nivel warning con el marcador interno de las alertas, de modo que
 * no genere a su vez una alerta de error.
 */
class SendContactMessageTelegram implements ShouldQueue
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
     * El aviso no debe a su vez generar una alerta de error.
     */
    public function failed(?Throwable $exception): void
    {
        Log::warning('El aviso de contacto por Telegram no se pudo procesar en la cola', [
            ErrorAlertService::CONTEXT_MARKER => true,
            'error' => $exception?->getMessage(),
        ]);
    }
}
