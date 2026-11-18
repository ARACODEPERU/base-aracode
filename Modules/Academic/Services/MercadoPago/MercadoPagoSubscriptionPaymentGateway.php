<?php

namespace Modules\Academic\Services\MercadoPago;

use MercadoPago\Client\Invoice\InvoiceClient;
use MercadoPago\MercadoPagoConfig;

/**
 * Implementación con el SDK oficial: GET /authorized_payments/{id}.
 */
class MercadoPagoSubscriptionPaymentGateway implements SubscriptionPaymentGateway
{
    public function authorizedPayment(int $id): array
    {
        MercadoPagoConfig::setAccessToken((string) config('services.mercadopago.token'));

        $invoice = (new InvoiceClient())->get($id);

        $raw = [];
        try {
            $raw = $invoice->getResponse()->getContent();
        } catch (\Throwable $e) {
            $raw = ['id' => $invoice->id, 'status' => $invoice->status];
        }

        // El pago viene como recurso anidado; se normaliza a arreglo.
        $payment = $invoice->payment;
        if (is_object($payment)) {
            $payment = json_decode(json_encode($payment), true) ?: [];
        }

        return [
            'id' => (int) $invoice->id,
            'preapproval_id' => (string) $invoice->preapproval_id,
            'status' => (string) $invoice->status,
            'transaction_amount' => (float) $invoice->transaction_amount,
            'currency_id' => $invoice->currency_id,
            'debit_date' => $invoice->debit_date,
            'next_retry_date' => $invoice->next_retry_date,
            'payment' => is_array($payment) ? $payment : [],
            'raw' => is_array($raw) ? $raw : [],
        ];
    }
}
