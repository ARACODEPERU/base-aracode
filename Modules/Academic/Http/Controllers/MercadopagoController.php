<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Operations\StudentSubscription;
use Modules\Onlineshop\Entities\OnliSale;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

class MercadopagoController extends Controller
{
    public function formPay($id)
    {
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
        $client = new PreferenceClient();
        $items = [];

        array_push($items, [
            'id' => '2',
            'title' => 'PRIMIUN',
            'quantity'      => floatval(1),
            'currency_id'   => 'PEN',
            'unit_price'    => floatval(240)
        ]);

        $preference = $client->create([
            "items" => $items,
        ]);

        $preference_id =  $preference->id;

        return Inertia::render('Landing/Academic/StepsPayCheckout', [
            'preference' => $preference_id,
            'subscription' => AcaSubscriptionType::find($id)
        ]);
    }

    public function processPayment(Request $request, $id)
    {
        \MercadoPago\MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));

        $client = new \MercadoPago\Client\Payment\PaymentClient();
        $payment_server = null;

        $sus = AcaSubscriptionType::find($id);
        //dd($request->all());
        try {

            if ($request->get('payment_method_id') == 'yape') {

                $createRequest = [
                    "description" => 'suscripcion ' . $sus->title,
                    "installments" => 1,
                    "payer" => $request->get('payer'),
                    "payment_method_id" => "yape",
                    "token" => $request->get('token'),
                    "transaction_amount" => (float) $request->get('transaction_amount'),
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
                    "transaction_amount" => (float) $request->get('transaction_amount')
                ];
                $payment = $client->create($createRequest);

                $payment_server = 'mercadopago';
            }


            $message = null;

            $ssale = 0;

            switch ($payment->status) {
                case "approved":
                    $pro = new StudentSubscription($id);
                    $ssale = $pro->process($request->all(), $payment);
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

            $url = route('web_gracias_por_comprar', $ssale->id);

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

    public function thankYou($id)
    {
        $sale = OnliSale::firstWhere('id', $id);

        return Inertia::render('Landing/Academic/StepsThankSubscribing', [
            'sale' => $sale
        ]);
    }
}
