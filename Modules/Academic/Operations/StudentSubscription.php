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

class StudentSubscription
{
    protected $subscription_id;
    protected $response;

    public function __construct($subscription_id, $response)
    {
        $this->subscription_id = $subscription_id;
        $this->response = $response;
    }
    public function process()
    {
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

        if (Auth::check()) {
            // El usuario está autenticado
            $user = User::find(Auth::id());
            $person = Person::find($user->person_id);
            $student = AcaStudent::where('person_id', $person->id)->first();

            $subscription = AcaSubscriptionType::find($this->subscription_id);

            $amount = 0;
            if ($subscription->prices) {
                foreach (json_decode($subscription->prices) as $price) {
                    if ($price['currency'] == 'PEN') {
                        $amount = $price['amount'];
                    }
                }
            }

            $sale->person_id = $user->person_id;
            $sale->clie_full_name = $person->full_name;
            $sale->phone = $person->telephone;
            $sale->email = $person->email;


            OnliSaleDetail::create([
                'sale_id'       => $sale->id,
                'item_id'       => $subscription->item_id,
                'entitie'       => AcaSubscriptionType::class,
                'price'         => floatval($amount),
                'quantity'      => 1,
                //'onli_item_id'  => $id
            ]);

            // Fecha actual como fecha de inicio

            AcaStudentSubscription::create([
                'student_id' => $student->id,
                'subscription_id' => $this->subscription_id,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => true,
                'notes' => null,
                'renewals' => 0,
                'registration_user_id' => Auth::id(),
                'onli_sale_id' => null
            ]);
        } else {
            // El usuario NO está autenticado

            $person = Person::create([
                'document_type_id' => 1,
                'short_name',
                'full_name',
                'number',
                'email',
                'gender' => 'M',
                'status' => true,
            ]);

            $user = User::create([
                'name',
                'email',
                'email_verified_at',
                'pasword',
                'local_id' => 1,
                'person_id' => $person->id
            ]);

            $sale->person_id = $user->person_id;
            $sale->clie_full_name = $person->full_name;
            $sale->phone = $person->telephone;
            $sale->email = $person->email;

            $student = AcaStudent::create([
                'student_code' => $person->number,
                'person_id' => $person->id
            ]);

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

        // $sale->total = $response->get('transaction_amount');
        // $sale->identification_type = $request->get('payer')['identification']['type'];
        // $sale->identification_number = $request->get('payer')['identification']['number'];
        // $sale->response_status = $payment->status;
        // $sale->response_id = $request->get('collection_id');
        // $sale->response_date_approved = Carbon::now()->format('Y-m-d');
        // $sale->response_payer = json_encode($request->all());
        // $sale->response_payment_method_id = $request->get('payment_type');
        // $sale->mercado_payment_id = $payment->id;
        // $sale->mercado_payment = json_encode($payment);

        $sale->save();

        // Mail::to($sale->email)
        //     ->send(new ConfirmPurchaseMail(OnliSale::with('details.item')->where('id', $id)->first()));
    }
}
