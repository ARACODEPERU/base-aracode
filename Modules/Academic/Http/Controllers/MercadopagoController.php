<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\PettyCash;
use App\Models\Sale;
use App\Models\SaleProduct;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Operations\StudentSubscription;
use Modules\Onlineshop\Entities\OnliSale;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use Modules\Academic\Emails\CratitudeCoursePurchase;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Onlineshop\Entities\OnliSaleDetail;
use Illuminate\Support\Facades\Mail;

class MercadopagoController extends Controller
{
    public function formPay(Request $request, $id)
    {
        $personInvoice = $request->get('personInvoice');
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
        $client = new PreferenceClient();
        $items = [];

        $subscription = AcaSubscriptionType::find($id);

        $amount = 0;
        if ($subscription->prices) {
            foreach (json_decode($subscription->prices) as $price) {
                if ($price->currency == 'PEN') {
                    $amount = $price->amount;
                }
            }
        }

        array_push($items, [
            'id' => '2',
            'title' => $subscription->title,
            'quantity'      => floatval(1),
            'currency_id'   => 'PEN',
            'unit_price'    => floatval($amount)
        ]);

        $preference = $client->create([
            "items" => $items,
        ]);

        $preference_id =  $preference->id;


        return Inertia::render('Landing/Academic/StepsPayCheckout', [
            'preference' => $preference_id,
            'subscription' => $subscription,
            'samount' => floatval($amount),
            'personInvoice' => $personInvoice
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
        //dd($request->all());
        $products = $request->get('products');
        $personInvoice = $request->get('personInvoice');

        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();

        $amount = (float) $request->get('transaction_amount');

        try {
            $res = DB::transaction(function () use ($request, $client, $amount, $products, $student, $personInvoice) {

                if ($request->get('payment_method_id') == 'yape') {

                    $createRequest = [
                        "installments" => 1,
                        "payer" => $request->get('payer'),
                        "payment_method_id" => "yape",
                        "token" => $request->get('token'),
                        "transaction_amount" => $amount,
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
                        "transaction_amount" => $amount
                    ];
                    $payment = $client->create($createRequest);

                    $payment_server = 'mercadopago';
                }

                $message = null;

                $person = Person::find(Auth::user()->person_id);
                $payments = [array("type" => 6, "reference" => null, "amount" => $amount)];

                $sale_note = Sale::create([
                    'sale_date' => Carbon::now()->format('Y-m-d'),
                    'user_id' => Auth::id(),
                    'client_id' => $person->id,
                    'local_id' => 1,
                    'total' => $request->get('transaction_amount'),
                    'advancement' => $request->get('transaction_amount'),
                    'total_discount' => 0,
                    'payments' => json_encode($payments),
                    'petty_cash_id' => null,
                    'physical' => 1,
                    'invoice_razon_social' => $personInvoice ? $personInvoice['razonSocial'] : null,
                    'invoice_ruc' => $personInvoice ? $personInvoice['ruc'] : null,
                    'invoice_direccion' => null,
                    'invoice_ubigeo' => null,
                    'invoice_type' => $personInvoice ? $personInvoice['document_type'] : null
                ]);

                $sale = OnliSale::create([
                    'module_name' => 'Academic',
                    'person_id' => Auth::user()->person_id,
                    'clie_full_name' => $person->full_name,
                    'phone' => $person->telephone,
                    'email' => $person->email,
                    'nota_sale_id' => $sale_note->id
                ]);

                $studentSubscribed = AcaStudentSubscription::where('student_id', $student->id)
                    ->where('status', true)
                    ->first();

                switch ($payment->status) {
                    case "approved":
                        if (count($products) > 0) {
                            foreach ($products as $product) {
                                $xpro = AcaCourse::find($product['id']);
                                $true = AcaCapRegistration::where('student_id', $student->id)->where('course_id', $xpro->id)->doesntExist();

                                $price = $xpro->price;
                                if ($xpro->discount && $xpro->discount > 0) {
                                    if ($xpro->discount_applies == '01') {
                                        $price = number_format($xpro->price - ($xpro->price * $xpro->discount / 100), 2, '.', '');
                                    } elseif ($xpro->discount_applies == '02') {
                                        if ($studentSubscribed && $studentSubscribed->status == 1) {
                                            $price = number_format($xpro->price - ($xpro->price * $xpro->discount / 100), 2, '.', '');
                                        } else {
                                            $price = number_format($xpro->price, 2, '.', '');
                                        }
                                    }
                                }

                                OnliSaleDetail::create([
                                    'sale_id' => $sale->id,
                                    'item_id' => $xpro->id,
                                    'entitie' => AcaCourse::class,
                                    'price' => $price,
                                    'quantity' => 1
                                ]);

                                SaleProduct::create([
                                    'sale_id' => $sale_note->id,
                                    'product_id' => $xpro->id,
                                    'product' => json_encode($xpro),
                                    'saleProduct' => json_encode($product),
                                    'price' => $price,
                                    'discount' => 0,
                                    'quantity' => 1,
                                    'total' => round($price, 2),
                                    'entity_name_product' => AcaCourse::class
                                ]);

                                if ($true) {
                                    AcaCapRegistration::create([
                                        'student_id'    => $student->id,
                                        'course_id'     => $xpro->id,
                                        'status'        => true,
                                        'sale_note_id'   => $sale_note->id
                                    ]);
                                } else {
                                    AcaCapRegistration::whereColumn([
                                        ['student_id', '=', $student->id],
                                        ['course_id', '=', $xpro->id],
                                    ])->update([
                                        'status'        => true,
                                        'sale_note_id'   => $sale_note->id
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

                if($payment->status == 'approved'){
                    ////enviar correo de agradecimiento///
                    Mail::to($sale->email)
                        ->send(new CratitudeCoursePurchase(OnliSale::with('details.course')->where('id', $sale->id)->first()));

                }

                return [
                    'payment' => $payment,
                    'message' => $message
                ];
            });

            //$url = route('web_gracias_por_comprar', $sale->id);
            $url = route('aca_mycourses');

            $payment = $res['payment'];

            return response()->json([
                'status' => $payment->status,
                //'message' => $payment->status_detail,
                'message' => $res['message'],
                'url' => $url
            ]);
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            // Manejar la excepción

            $response = $e->getApiResponse();
            $content  = $response->getContent();
            //dd($content);
            $message = $content['message'];
            return response()->json(['error' => 'Error al procesar el pago: ' . $message], 412);
        } catch (\Exception $e) {
            // Manejar cualquier otro error
            return response()->json(['error' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }
}
