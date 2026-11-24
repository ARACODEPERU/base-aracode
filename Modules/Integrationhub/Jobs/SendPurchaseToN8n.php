<?php

namespace Modules\Integrationhub\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Integrationhub\Support\IntegrationAlert;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Support\PurchaseWebhookPayload;

/**
 * Envía a n8n (endpoint n8n_post_negociacion) una compra hecha por una persona
 * que ya tenía cuenta, con la misma forma de payload que una negociación
 * culminada. Corre en cola: si n8n está caído, la cola reintenta y el fallo
 * queda en integration_errors.
 *
 * La cola es la configurada en QUEUE_CONNECTION; sin worker los jobs quedan
 * esperando en la tabla jobs.
 */
class SendPurchaseToN8n implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Reintentos antes de pasar por failed(). */
    public $tries = 3;

    /** Mayor que el timeout HTTP de la integración (default 30s). */
    public $timeout = 120;

    public function __construct(public int $onliSaleId)
    {
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        $payload = PurchaseWebhookPayload::forSale($this->onliSaleId);

        // La venta o su persona pudieron eliminarse entre el encolado y la ejecución.
        if (! $payload) {
            return;
        }

        $response = app(IntegrationhubController::class)
            ->runEndpoint('n8n_post_negociacion', [], ['body' => $payload], true);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(
                'El endpoint n8n_post_negociacion respondió HTTP '.$response->getStatusCode().'.'
            );
        }
    }

    /**
     * Se ejecuta cuando se agotan los reintentos: deja constancia en la
     * bitácora de errores del módulo.
     */
    public function failed(\Throwable $exception): void
    {
        IntegrationAlert::notify(
            'SendPurchaseToN8n (onli_sale_id='.$this->onliSaleId.'): '.$exception->getMessage(),
            'SendPurchaseToN8n'
        );
    }
}
