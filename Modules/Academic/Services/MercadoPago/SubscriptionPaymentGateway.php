<?php

namespace Modules\Academic\Services\MercadoPago;

/**
 * Consulta de cobros autorizados (invoices) de una suscripción.
 *
 * Se abstrae para poder probar el webhook sin llamar a MercadoPago.
 */
interface SubscriptionPaymentGateway
{
    /**
     * Cobro autorizado de una suscripción por su id en MercadoPago.
     *
     * @return array{
     *     id: int,
     *     preapproval_id: string,
     *     status: string,
     *     transaction_amount: float,
     *     currency_id: string|null,
     *     debit_date: string|null,
     *     next_retry_date: string|null,
     *     payment: array<string, mixed>,
     *     raw: array<string, mixed>
     * }
     */
    public function authorizedPayment(int $id): array;
}
