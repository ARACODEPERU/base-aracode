<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Commercial\Entities\CommercialNegotiation;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

class CommercialNegotiationPaymentController extends Controller
{
    /**
     * Procesa el pago con tarjeta (Mercado Pago) de una negociacion publica.
     * Solo guarda el resultado del pago en la negociacion; el administrador
     * verifica el pago y continua con el proceso de aprobacion.
     */
    public function processPayment(Request $request, $token)
    {
        $negotiation = CommercialNegotiation::where('token', $token)->firstOrFail();

        // Enlace vencido: se marca como "No hubo respuesta" y se bloquea el pago.
        if ($negotiation->status === 'pendiente'
            && $negotiation->link_expires_at
            && $negotiation->link_expires_at->isPast()) {
            $negotiation->update(['status' => 'sin_respuesta']);
        }

        if (in_array($negotiation->status, ['sin_respuesta', 'aprobada', 'cancelada', 'completada'])) {
            return response()->json([
                'error' => 'El enlace de pago ya no esta vigente. Contacta al asesor para generar uno nuevo.',
            ], 422);
        }

        $amount = $this->expectedAmount($negotiation);

        try {
            // Validar que Mercado Pago haya generado el token de tarjeta.
            if (! $request->filled('token')) {
                return response()->json([
                    'error' => 'Mercado Pago no genero el token de tarjeta. Recarga el formulario y vuelve a ingresar los datos de la tarjeta.',
                ], 422);
            }

            // Evita un monto distinto al acordado.
            $postedAmount = (float) $request->get('transaction_amount');
            if (abs($postedAmount - $amount) > 0.01) {
                return response()->json([
                    'error' => 'El monto del pago no coincide con el acordado.',
                ], 422);
            }

            MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));
            $client = new PaymentClient();

            $createRequest = [
                'issuer_id'             => $request->get('issuer_id'),
                'installments'          => $request->get('installments'),
                'payer'                 => $request->get('payer'),
                'payment_method_id'     => $request->get('payment_method_id'),
                'token'                 => $request->get('token'),
                'transaction_amount'    => $amount,
            ];

            $payment = $client->create($createRequest);

            $message = 'Pendiente de pago';
            switch ($payment->status) {
                case 'approved':
                    $message = 'Pago aprobado';
                    break;
                case 'rejected':
                    $message = 'Rechazado por error general';
                    break;
                case 'in_process':
                    $message = 'Pendiente de pago';
                    break;
            }

            DB::transaction(function () use ($negotiation, $payment, $amount, $request, $message) {
                $negotiation->update([
                    'mercado_payment_id'     => $payment->id,
                    'mercado_payment_status' => $payment->status,
                    'mercado_payment_data'   => [
                        'payment'            => $payment,
                        'payer'              => $request->get('payer'),
                        'payment_method_id'  => $request->get('payment_method_id'),
                        'installments'       => $request->get('installments'),
                        'transaction_amount' => $amount,
                        'message'            => $message ?? null,
                    ],
                ]);
            });

            return response()->json([
                'status' => $payment->status,
                'message' => $message,
                'mercado_payment_id' => $payment->id,
            ]);
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            $response = $e->getApiResponse();
            $content = $response ? $response->getContent() : [];
            $message = $content['message'] ?? $e->getMessage();

            if ($message === 'Invalid card_token_id') {
                $message .= '. Verifica que MERCADOPAGO_KEY y MERCADOPAGO_TOKEN sean de prueba y pertenezcan a la misma cuenta, y recarga el formulario para generar un token nuevo.';
            }

            return response()->json(['error' => 'Error al procesar el pago: ' . $message], 412);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Monto que se cobra: en pagos en cuotas es la primera cuota del cronograma;
     * en pago unico es el total acordado.
     */
    private function expectedAmount(CommercialNegotiation $negotiation): float
    {
        if ($negotiation->payment_type === 'installments') {
            $schedule = is_array($negotiation->schedule) ? $negotiation->schedule : [];
            $first = (float) ($schedule[0]['amount'] ?? 0);

            if ($first > 0) {
                return $first;
            }
        }

        return (float) $negotiation->total_price;
    }
}
