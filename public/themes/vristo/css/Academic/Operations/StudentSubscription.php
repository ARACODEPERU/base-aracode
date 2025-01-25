<?php

namespace Modules\Academic\Operations;

use App\Models\Person;
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
        $sale = new OnliSale();
        $sale->module_name = 'Academic';

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

            $sale->person_id = $user->person_id;
            $sale->clie_full_name = $person->full_name;
            $sale->phone = $person->telephone;
            $sale->email = $person->email;

            $stsubscription = AcaStudentSubscription::where('student_id', $student->id)
                ->where('subscription_id', $this->subscription_id])
                ->first();

            if ($stsubscription) {
                // Actualizar el registro existente
                $stsubscription->update(
                    [
                        'student_id' => $student->id,
                        'subscription_id' => $this->subscription_id,
                        'date_start' => $dateStart,
                        'date_end' => $dateEnd,
                        'status' => true,
                        'notes' => null,
                        'renewals' => true,
                        'registration_user_id' => Auth::id(),
                        'onli_sale_id' => null
                    ]
                );
            } else {
                AcaStudentSubscription::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'subscription_id' => $this->subscription_id,
                        'date_start' => $dateStart,
                        'date_end' => $dateEnd,
                        'status' => true,
                        'notes' => null,
                        'renewals' => 0,
                        'registration_user_id' => Auth::id(),
                        'onli_sale_id' => null
                    ]
                );
            }

            
        } else {
            // El usuario NO está autenticado

            $person = Person::firstOrCreate(
                [
                    'document_type_id' => 1,
                    'email' => $response['payer']['email'],
                    'number' => $response['payer']['identification']['number'],
                ],
                [

                    'short_name' => 'Usuario Nuevo',
                    'full_name' => 'Usuario Academico Nuevo',
                    'gender' => 'M',
                    'status' => true,
                ]
            );
            //dd($response['payer']['identification']['number']);
            $user = User::firstOrCreate(
                [
                    'email' => $response['payer']['email'],
                    'person_id' => $person->id
                ],
                [
                    'name' => 'Usuario Nuevo',
                    'password' => Hash::make($response['payer']['identification']['number']),
                    'local_id' => 1,
                ]
            );

            $sale->person_id = $user->person_id;
            $sale->clie_full_name = $person->full_name;
            $sale->phone = $person->telephone;
            $sale->email = $person->email;

            $student = AcaStudent::firstOrCreate([
                'student_code' => $person->number,
                'person_id' => $person->id
            ]);

            $stsubscription = AcaStudentSubscription::where('student_id', $student->id)
                ->where('subscription_id', $this->subscription_id])
                ->first();

            if ($stsubscription) {
                $stsubscription->update([
                    'student_id' => $student->id,
                    'subscription_id' => $this->subscription_id,
                    'date_start' => $dateStart,
                    'date_end' => $dateEnd,
                    'status' => true,
                    'notes' => null,
                    'renewals' => true,
                    'registration_user_id' => $user->id,
                    'onli_sale_id' => null
                ]);
            } else {
                AcaStudentSubscription::create([
                    'student_id' => $student->id,
                    'subscription_id' => $this->subscription_id,
                    'date_start' => $dateStart,
                    'date_end' => $dateEnd,
                    'status' => true,
                    'notes' => null,
                    'renewals' => 0,
                    'registration_user_id' => $user->id,
                    'onli_sale_id' => null
                ]);
            }
            
        }

        $sale->total = $response['transaction_amount'];
        $sale->identification_type = $response['payer']['identification']['type'];
        $sale->identification_number = $response['payer']['identification']['number'];
        $sale->response_status = $payment->status;
        $sale->response_id = $response['issuer_id'];
        $sale->response_date_approved = Carbon::now()->format('Y-m-d');
        $sale->response_payer = json_encode($response);
        $sale->response_payment_method_id = $response['payment_method_id'];
        $sale->mercado_payment_id = $payment->id;
        $sale->mercado_payment = json_encode($payment);

        $sale->save();

        OnliSaleDetail::create([
            'sale_id'       => $sale->id,
            'item_id'       => $this->subscription_id,
            'entitie'       => AcaSubscriptionType::class,
            'price'         => floatval($amount),
            'quantity'      => 1,
            //'onli_item_id'  => $id
        ]);

        Mail::to($sale->email)
            ->send(new ConfirmPurchaseSubscription($sale));

        return $sale;
    }
}
