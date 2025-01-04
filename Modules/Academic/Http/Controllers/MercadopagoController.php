<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Entities\AcaSubscriptionType;

class MercadopagoController extends Controller
{
    public function processPayment(Request $request, $id)
    {
        \MercadoPago\MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));

        $client = new \MercadoPago\Client\Payment\PaymentClient();
        $payment_server = null;

        $sus = AcaSubscriptionType::find($id);

        try {

            if ($request->get('payment_method_id') == 'yape') {

                $createRequest = [
                    "description" => 'suscripcion ' . $sus->title,
                    "installments" => 1,
                    "payer" => $request->get('payer'),
                    "payment_method_id" => "yape",
                    "token" => $request->get('token'),
                    "transaction_amount" => (float) $sus->price,
                ];
                $payment = $client->create($createRequest);
                $payment_server = 'yape';
            } else {
                $createRequest = [
                    "issuer_id" => $request->get('issuer_id'),
                    "description" => 'suscripcion ' . $sus->name,
                    "installments" => $request->get('installments'),
                    "payer" => $request->get('payer'),
                    "payment_method_id" => $request->get('payment_method_id'),
                    "token" => $request->get('token'),
                    "transaction_amount" => (float) $sus->price
                ];
                $payment = $client->create($createRequest);

                $payment_server = 'mercadopago';
            }


            $url = route('web_gracias_por_comprar', $sus->id);
            $message = null;

            switch ($payment->status) {
                case "approved":

                    $message = 'Pago aprobado';
                    break;
                case "rejected":
                    $message = 'Rechazado por error general';
                    break;
                case "in_process":
                    $message = 'Pendiente de pago';
                    break;
                default:
                    $message = 'Pendiente de pago';
                    break;
            }
            return response()->json([
                'status' => $payment->status,
                //'message' => $payment->status_detail,
                'message' => $message,
                'url' => $url
            ]);
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            // Manejar la excepción
            $response = $e->getApiResponse();
            $content  = $response->getContent();
            //dd($content);
            $message = $content['message'];
            return response()->json(['error' => 'Error al procesar el pago: ' . $message], 412);
        }
    }
}
