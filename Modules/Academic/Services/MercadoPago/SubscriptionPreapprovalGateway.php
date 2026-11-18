<?php

namespace Modules\Academic\Services\MercadoPago;

/**
 * Puerta de salida hacia POST /preapproval de MercadoPago.
 *
 * Se abstrae para poder probar la lógica de suscripción sin llamar a la API
 * real; la implementación concreta usa el SDK oficial.
 */
interface SubscriptionPreapprovalGateway
{
    /**
     * Crea la suscripción en MercadoPago.
     *
     * @param  array<string, mixed>  $payload  Cuerpo enviado a /preapproval.
     * @return array{id: string, status: string, next_payment_date: string|null, init_point: string|null, raw: array<string, mixed>}
     */
    public function create(array $payload): array;
}
