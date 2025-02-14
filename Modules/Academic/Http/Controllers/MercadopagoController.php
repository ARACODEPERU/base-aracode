<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Operations\StudentSubscription;
use Modules\Onlineshop\Entities\OnliSale;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;

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

    public function createPreference(Request $request)
    {
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
        $client = new PreferenceClient();
        $items = [];
        $msg = null;
        $success = true;
        $preference_id = null;

        if ($request->get('price')) {
            array_push($items, [
                'id' => $request->get('id'),
                'title' => trim($request->get('description')),
                'quantity'      => floatval(1),
                'currency_id'   => 'PEN',
                'unit_price'    => floatval($request->get('price'))
            ]);

            $preference = $client->create([
                "items" => $items,
            ]);
            $success = true;
            $preference_id =  $preference->id;
        } else {
            $success = false;
        }

        return response()->json([
            'success' => $success,
            'message' => $msg,
            'preference_id' => $preference_id
        ]);
    }

    public function createItemsPreference(Request $request)
    {
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
        $client = new PreferenceClient();
        $items = [];
        $msg = null;
        $success = true;
        $preference_id = null;
        $products = $request->get('items');

        if (count($products) > 0) {
            foreach ($products as $product) {
                $xpro = AcaCourse::find($product['id']);
                array_push($items, [
                    'id' => $xpro->id,
                    'title' => trim($xpro->description),
                    'quantity'      => floatval(1),
                    'currency_id'   => 'PEN',
                    'unit_price'    => floatval($xpro->price)
                ]);
            }

            $preference = $client->create([
                "items" => $items,
            ]);

            $success = true;
            $preference_id =  $preference->id;
        } else {
            $success = false;
        }

        return response()->json([
            'success' => $success,
            'message' => $msg,
            'preference_id' => $preference_id
        ]);
    }

    public function processPaymentCourses(Request $request)
    {
        \MercadoPago\MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));

        $client = new \MercadoPago\Client\Payment\PaymentClient();
        $payment_server = $request->get('payment_method_id');
        $products = $request->get('products');
        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();

        try {

            if ($request->get('payment_method_id') == 'yape') {

                $createRequest = [
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

            $url = null;
            $person = Person::find(Auth::user()->person_id);
            $sale = new OnliSale();
            $sale->module_name = 'Academic';
            $sale->person_id = Auth::user()->person_id;
            $sale->clie_full_name = $person->full_name;
            $sale->phone = $person->telephone;
            $sale->email = $person->email;

            switch ($payment->status) {
                case "approved":
                    if (count($products) > 0) {
                        foreach ($products as $product) {
                            $xpro = AcaCourse::find($product['id']);
                            $true = AcaCapRegistration::where('student_id', $student->id)->where('course_id', $xpro->id)->doesntExist();
                            if ($true) {
                                AcaCapRegistration::create([
                                    'student_id'        => $student->id,
                                    'course_id'         => $xpro->id,
                                    'status'             => true
                                ]);
                            }
                        }
                    }
                    $sale->total = $request->get('transaction_amount');
                    $sale->identification_type = $request->get('payer')['identification']['type'];
                    $sale->identification_number = $request->get('payer')['identification']['number'];
                    $sale->response_status = $payment->status;
                    $sale->response_id = $request->get('issuer_id');
                    $sale->response_date_approved = Carbon::now()->format('Y-m-d');
                    $sale->response_payer = json_encode($request->all());
                    $sale->response_payment_method_id = $request->get('payment_method_id');
                    $sale->mercado_payment_id = $payment->id;
                    $sale->mercado_payment = json_encode($payment);


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

            $sale->save();
            //$url = route('web_gracias_por_comprar', $sale->id);
            $url = route('aca_mycourses');
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
