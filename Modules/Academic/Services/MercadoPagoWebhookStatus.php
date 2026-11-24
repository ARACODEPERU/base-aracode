<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Facades\Route;
use Modules\Integrationhub\Entities\Integration;

/**
 * Estado del secreto del webhook de MercadoPago para las pantallas de
 * administración (IntegrationHub y edición de planes).
 */
class MercadoPagoWebhookStatus
{
    /**
     * @return array{
     *     configured: bool,
     *     source: string|null,
     *     integration_id: int|null,
     *     edit_url: string,
     *     has_secret_field: bool,
     *     webhook_url: string,
     *     document_id: string|null
     * }
     */
    public function state(): array
    {
        $integration = Integration::query()
            ->whereRaw('LOWER(name) = ?', ['mercadopago'])
            ->first();

        $auth = $integration?->auths()
            ->whereRaw('LOWER(field_name) = ?', ['webhook_secret'])
            ->first();

        $desdeHub = trim((string) ($auth?->field_value ?? '')) !== '';
        $desdeEnv = trim((string) config('services.mercadopago.webhook_secret')) !== '';

        return [
            'configured' => $desdeHub || $desdeEnv,
            'source' => $desdeHub ? 'integrationhub' : ($desdeEnv ? 'env' : null),
            'integration_id' => $integration?->id,
            'integration_name' => $integration?->name ?? 'MercadoPago',
            'edit_url' => $integration
                ? route('integrationhub_editar', $integration->id)
                : route('integrationhub_listado'),
            'has_secret_field' => $auth !== null,
            // La ruta existe solo cuando el cobro recurrente esta habilitado: en
            // un proyecto sin esa funcion el panel debe seguir cargando.
            'webhook_url' => Route::has('aca_mercadopago_subscription_webhook')
                ? route('aca_mercadopago_subscription_webhook')
                : null,
            'document_id' => null,
        ];
    }
}
