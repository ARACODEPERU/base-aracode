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
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Onlineshop\Entities\OnliSaleDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\ConfirmPurchaseSubscription;

class StudentSubscription
{
    protected $subscription_id;

    public function __construct($subscription_id = null)
    {
        $this->subscription_id = $subscription_id;
    }

    public function process($response, $payment)
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

        $amount = 0;
        if ($subscription->prices) {
            foreach (json_decode($subscription->prices) as $price) {
                if ($price->currency == 'PEN') {
                    $amount = $price->amount;
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
                'mercado_payment_id' => $payment->id,
                'mercado_payment' => json_encode($payment)
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

            Mail::to($sale->email)
                ->send(new ConfirmPurchaseSubscription($sale));

            return $sale;
        } else {
            return false;
        }
    }
}
