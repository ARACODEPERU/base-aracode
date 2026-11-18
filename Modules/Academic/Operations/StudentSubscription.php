<?php

namespace Modules\Academic\Operations;

use App\Models\Person;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Onlineshop\Entities\OnliSaleDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\ConfirmPurchaseSubscription;
use Modules\Treasury\Services\TreasuryHooks;

class StudentSubscription
{
    protected $subscription_id;

    public function __construct($subscription_id = null)
    {
        $this->subscription_id = $subscription_id;
    }

    /**
     * Registra la compra de una suscripción.
     *
     * Cuando el plan es recurrente, $preapproval trae la suscripción autorizada
     * en MercadoPago y sustituye al pago único como origen de la venta.
     */
    public function process($response, $payment, ?AcaSubscriptionPreapproval $preapproval = null)
    {
        //dd($response);
        $subscription = AcaSubscriptionType::find($this->subscription_id);
        ///se registra la venta en linea
        ///en la tabla onli_sale

        $personInvoice = $response['personInvoice'];

        $dateStart = Carbon::today(); // Solo fecha sin hora
        $dateEnd = null;

        // Calcular fecha de fin
        switch ($subscription->period) {
            case 'Mensual':
                $dateEnd = $dateStart->copy()->addMonth();
                break;

            case 'Trimestral':
                $dateEnd = $dateStart->copy()->addMonths(3); // 3 meses
                break;

            case 'Semestral':
                $dateEnd = $dateStart->copy()->addMonths(6); // 6 meses
                break;

            case 'Anual':
                $dateEnd = $dateStart->copy()->addYear();
                break;

            case 'Semanal':
                $dateEnd = $dateStart->copy()->addWeek();
                break;

            case 'Diario':
                $dateEnd = $dateStart->copy()->addDay();
                break;

            case 'Prueba gratuita': // Caso para fechas nulas
            case 'Única Vez':
                $dateEnd = null;
                break;

            default:
                $dateEnd = null;
        }

        // El precio puede venir como arreglo (cast del modelo) o como JSON.
        $amount = 0;
        if ($subscription->prices) {
            $precios = $subscription->prices;

            if (is_string($precios)) {
                $precios = json_decode($precios) ?: [];
            }

            foreach ($precios as $price) {
                $currency = is_array($price) ? ($price['currency'] ?? null) : ($price->currency ?? null);
                $monto = is_array($price) ? ($price['amount'] ?? 0) : ($price->amount ?? 0);

                if ($currency == 'PEN') {
                    $amount = $monto;
                }
            }
        }

        if (Auth::check()) {
            // El usuario está autenticado
            $user = User::find(Auth::id());
            $person = Person::find($user->person_id);
            $student = AcaStudent::firstOrCreate(
                [
                    'person_id' => $person->id,
                    'student_code' => $person->number
                ]
            );

            $mercadoPaymentId = $preapproval?->preapproval_id ?? ($payment->id ?? null);
            $mercadoPaymentJson = json_encode($preapproval?->raw ?? $payment);

            $sale = OnliSale::create([
                'module_name' => 'Academic',
                'person_id' => $user->person_id,
                'clie_full_name' => $person->full_name,
                'phone' => $person->telephone,
                'email' => $person->email,
                'total' => $response['transaction_amount'],
                'identification_type' => $response['payer']['identification']['type'],
                'identification_number' => $response['payer']['identification']['number'],
                'response_status' => $payment->status,
                'response_id' => $response['issuer_id'],
                'response_date_approved' => Carbon::now()->format('Y-m-d'),
                'response_payer' => json_encode($response),
                'response_payment_method_id' => $response['payment_method_id'],
                'utm_source'     => $response['utm_source'] ?? null,
                'utm_medium'     => $response['utm_medium'] ?? null,
                'utm_campaign'   => $response['utm_campaign'] ?? null,
                'utm_term'       => $response['utm_term'] ?? null,
                'utm_content'    => $response['utm_content'] ?? null,
                'utm_id'         => $response['utm_id'] ?? null,
                'fbclid'         => $response['fbclid'] ?? null,
                'gclid'          => $response['gclid'] ?? null,
                'referer'        => $response['referer'] ?? null,
                'landing_url'    => $response['landing_url'] ?? null,
                'traffic_source' => $response['traffic_source'] ?? null,
            ]);


            $stsubscription = AcaStudentSubscription::where('student_id', $student->id)
                ->where('subscription_id', $this->subscription_id)
                ->first();

            if ($stsubscription) {
                // Actualizar el registro existente
                //dd($amount);
                AcaStudentSubscription::where('student_id', $student->id)
                    ->where('subscription_id', $this->subscription_id)
                    ->update([
                        'date_start' => $dateStart->toDateString(), // "Y-m-d"
                        'date_end' => $dateEnd ? $dateEnd->toDateString() : null,
                        'status' => true,
                        'notes' => null,
                        'renewals' => true,
                        'registration_user_id' => Auth::id(),
                        'onli_sale_id' => $sale->id,
                        'amount_paid' => floatval($amount)
                    ]);
            } else {
                AcaStudentSubscription::create(
                    [
                        'student_id' => $student->id,
                        'subscription_id' => $this->subscription_id,
                        'date_start' => $dateStart->toDateString(), // "Y-m-d"
                        'date_end' => $dateEnd ? $dateEnd->toDateString() : null,
                        'status' => true,
                        'notes' => null,
                        'renewals' => 0,
                        'registration_user_id' => Auth::id(),
                        'onli_sale_id' => $sale->id,
                        'amount_paid' => floatval($amount)
                    ]
                );
            }

            $payments = [array("type" => 6, "reference" => null, "amount" => floatval($amount))];

            $sale_note = Sale::create([
                'sale_date' => Carbon::now()->format('Y-m-d'),
                'user_id' => Auth::id(),
                'client_id' => $person->id,
                'local_id' => 1,
                'total' => $response['transaction_amount'],
                'advancement' => $response['transaction_amount'],
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

            $sale->nota_sale_id = $sale_note->id;

            // Estos dos campos no están en $fillable: se asignan directo para que
            // la venta quede ligada al pago (o al preapproval) de MercadoPago.
            $sale->mercado_payment_id = $mercadoPaymentId;
            $sale->mercado_payment = $mercadoPaymentJson;

            $sale->save();

            OnliSaleDetail::create([
                'sale_id'       => $sale->id,
                'item_id'       => $this->subscription_id,
                'entitie'       => AcaSubscriptionType::class,
                'price'         => floatval($amount),
                'quantity'      => 1,
                //'onli_item_id'  => $id
            ]);

            SaleProduct::create([
                'sale_id' => $sale_note->id,
                'product_id' => $subscription->id,
                'product' => json_encode($subscription),
                'saleProduct' => json_encode($subscription),
                'price' => floatval($amount),
                'discount' => 0,
                'quantity' => 1,
                'total' => floatval($amount),
                'entity_name_product' => AcaSubscriptionType::class
            ]);

            // Tesorería: el cobro aprobado por MercadoPago entra a su cuenta. Se
            // registra como ingreso del carrito online para que el comprobante que
            // se emita después no vuelva a sumar el mismo dinero.
            TreasuryHooks::recordOnlineSaleIncome(
                $sale,
                'Suscripción online · ' . mb_substr(trim((string) ($subscription->title ?: $subscription->name)), 0, 200)
            );

            // Cobro automático: se deja registrado el primer periodo; el cobro real
            // de MercadoPago se concilia luego con su autorized_payment_id.
            if ($preapproval) {
                AcaSubscriptionPayment::firstOrCreate(
                    [
                        'preapproval_id' => $preapproval->preapproval_id,
                        'date_start' => $dateStart->toDateString(),
                    ],
                    [
                        'student_id' => $student->id,
                        'subscription_id' => $this->subscription_id,
                        'date_end' => $dateEnd ? $dateEnd->toDateString() : null,
                        'amount' => floatval($amount),
                        'status' => 'authorized',
                        'source' => 'recurring',
                        'paid_at' => Carbon::now(),
                    ]
                );
            }

            Mail::to($sale->email)
                ->queue(new ConfirmPurchaseSubscription($sale));

            return $sale;
        } else {
            return false;
        }
    }
}
