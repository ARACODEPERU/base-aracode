<?php

namespace Modules\Academic\Services\MercadoPago;

use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

/**
 * Implementación con el SDK oficial de MercadoPago.
 */
class MercadoPagoPreapprovalGateway implements SubscriptionPreapprovalGateway
{
    public function create(array $payload): array
    {
        MercadoPagoConfig::setAccessToken((string) config('services.mercadopago.token'));

        $preapproval = (new PreApprovalClient())->create($payload);

        $raw = [];
        try {
            $raw = $preapproval->getResponse()->getContent();
        } catch (\Throwable $e) {
            // El recurso puede no traer respuesta asociada; se guarda lo básico.
            $raw = ['id' => $preapproval->id, 'status' => $preapproval->status];
        }

        return [
            'id' => (string) $preapproval->id,
            'status' => (string) ($preapproval->status ?: 'pending'),
            'next_payment_date' => $preapproval->next_payment_date ?: null,
            'init_point' => $preapproval->init_point ?: null,
            'raw' => is_array($raw) ? $raw : [],
        ];
    }
}
