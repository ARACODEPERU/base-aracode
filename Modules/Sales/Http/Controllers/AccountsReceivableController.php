<?php

namespace Modules\Sales\Http\Controllers;

use App\Helpers\NumberLetter;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\District;
use App\Models\ExcelExportJob;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\PettyCash;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use App\Models\SaleDocumentType;
use App\Models\SaleProduct;
use App\Models\Serie;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use DataTables;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Sales\Entities\SalePaymentSchedule;
use Modules\Sales\Entities\SalePaymentScheduleDestination;
use Modules\Sales\Jobs\PaymentDestinations;
use Modules\Sales\Rules\ValidationRuleCourseSubscriptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Modules\Sales\Emails\CuotasMail;
use Illuminate\Support\Facades\Mail;
class AccountsReceivableController extends Controller
{
    private $ubl;
    private $igv;
    private $top;
    private $icbper;

    public function __construct()
    {
        $this->ubl = Parameter::where('parameter_code', 'P000003')->value('value_default');
        $this->igv = Parameter::where('parameter_code', 'P000001')->value('value_default');
        $this->top = Parameter::where('parameter_code', 'P000002')->value('value_default');
        $this->icbper = Parameter::where('parameter_code', 'P000004')->value('value_default');
    }

    public function index()
    {
        $payments = PaymentMethod::all();
        return Inertia::render('Sales::AccountsReceivable/List',[
             'payments' => $payments,
        ]);
    }

    public function tableDocument()
    {
        $sales = (new Sale())->newQuery();

        $sales = $sales->with('client')
            ->where('physical', 2)
            ->whereHas('document', function ($query) {
                $query->whereIn('invoice_type_doc', ['03','01'])
                    ->where('status', 1)
                    ->whereNotIn('invoice_status', ['Rechazada'])
                    ->where('forma_pago','Credito'); // Estado de la factura
            })
            ->with('document.serie.documentType')
            ->with(['document.items','document.note','document.quotas.payments'])
            ->orderBy('sales.id', 'DESC');

        return DataTables::of($sales)
            ->addColumn('payment_status_text', function (Sale $sale) {
                // Accede al documento de venta asociado a la venta
                $document = $sale->document; // Asume que la relación 'document' está cargada

                // Realiza la misma lógica que tenías en el frontend
                if ($document) { // Asegúrate de que el documento exista
                    if ($document->overdue_fee && !$document->status_pay) {
                        return 'Vencido';
                    } elseif (!$document->overdue_fee && $sale->total == $sale->advancement) {
                        return 'Pagado';
                    } else {
                        return 'Atiempo';
                    }
                }
                return 'N/A'; // En caso de que el documento no exista, o necesites un valor por defecto
            })
            ->toJson();
    }


    public function specialRates(){

        $search = request()->input('search');
        $issueDateRange = request()->input('issue_date');

        $query = Sale::query()->with(['client','saleProduct','documents.items','schedules'])
            ->where('payment_installments', true);

        if (isset($issueDateRange)) {

            $dates = explode(' a ', $issueDateRange);

            try {
                if (count($dates) === 2) {
                    // Es un rango de fechas
                    $startDate = Carbon::parse($dates[0])->startOfDay();
                    $endDate = Carbon::parse($dates[1])->endOfDay();
                } else {
                    // Es una sola fecha
                    $startDate = Carbon::parse($dates[0])->startOfDay();
                    $endDate = Carbon::parse($dates[0])->endOfDay();
                }
                $query->whereHas('documents', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('invoice_broadcast_date', [$startDate, $endDate]);
                });

            } catch (\Exception $e) {
                Log::error("Error al parsear fecha " . $e->getMessage());
            }
        }
        // --- FIN DE LA LÓGICA DE FECHA MODIFICADA ---

        if (isset($search)) {
            $query->whereHas('client', function (Builder $q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('number', $search);
            });
        }
        // ----------------------------------------------------
        $sales = $query->addSelect([
                'next_payment_date' => SalePaymentSchedule::select('payment_date')
                    ->whereColumn('sale_payment_schedules.sale_id', 'sales.id')
                    ->whereColumn('sale_payment_schedules.amount_to_pay', '>', 'sale_payment_schedules.amount_paid')
                    ->orderBy('payment_date', 'ASC')
                    ->limit(1)
            ])
            ->orderByRaw('
                CASE
                    WHEN sales.total = sales.advancement THEN 1
                    ELSE 0
                END ASC
            ')
            ->orderBy('next_payment_date', 'ASC')
            ->orderBy('sales.id', 'DESC')
            ->paginate(20);

        return Inertia::render('Sales::AccountsReceivable/ListSpecialRates', [
            'sales' => $sales,
            'filters' => request()->all('search'),
        ]);
    }

    public function specialRatesCreate(){
        $courses = AcaCourse::where(function ($query) {
            $query->where('price', '>', 0)
            ->whereNotNull('price');
        })->get();
        $identityDocumentTypes = DB::table('identity_document_type')->get();
        $types = getEnumValues('aca_courses','type_description', true);
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $countries = Country::orderBy('description')->get();

        $payments = PaymentMethod::all();
        $saleDocumentTypes = DB::table('sale_document_types')->whereIn('sunat_id', ['01', '03'])->get();

        $igv = Parameter::where('parameter_code', 'P000001')->value('value_default');

        $subscriptionTypes = AcaSubscriptionType::where('status',true)->get();

        //dd($courses);
        return Inertia::render('Sales::AccountsReceivable/CreateSpecialRates',[
            'courses'               => $courses,
            'types'                 => $types,
            'identityDocumentTypes' => $identityDocumentTypes,
            'countries'             => $countries,
            'ubigeo'                => $ubigeo,
            'saleDocumentTypes'     => $saleDocumentTypes,
            'payments'              => $payments,
            'igv'                   => (int) $igv,
            'subscriptionTypes'     => $subscriptionTypes
        ]);
    }

    public function specialRatesStore(Request $request){

        $update_id = $request->get('person_id');

        $user_id = optional(
            User::where('person_id', $update_id)->first()
        )->id;
            //dd($request->all());
        $this->validate($request, [
            // Validar que al menos venga un curso
            'courses' => ['array', new ValidationRuleCourseSubscriptions],
            'subscriptions' => ['array', new ValidationRuleCourseSubscriptions],
            // Datos personales del alumno
            'names' => ['required', 'string', 'max:255'],
            'alu_number' => ['required', 'string', 'max:20'],
            'alu_number' => 'unique:people,number,' . $update_id . ',id,document_type_id,' . $request->get('alu_document_type'),
            'alu_document_type' => ['required', 'integer'],
            'afather' => ['required', 'string', 'max:255'],
            'amother' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'ubigeo' => ['nullable', 'string', 'max:10'],
            'ubigeo_description' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'numeric'],
            'email' => ['required', 'email', 'max:255'],
            'email' => 'unique:people,email,' . $update_id . ',id',
            'email' => 'unique:users,email,' . $user_id . ',id',
            'gender' => ['nullable', 'string', 'in:M,F'],
            'country_id' => ['nullable', 'integer'],

            // Datos de venta
            'sale_document_type' => ['required', 'integer'],
            'document_type' => ['required', 'integer'],
            'sale_full_name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],

            // Campos obligatorios si es factura (sale_document_type = 1)
            'sale_address' => [
                Rule::requiredIf(fn () => $request->sale_document_type == 1),
                'nullable',
                'string',
                'max:255'
            ],
            'sale_ubigeo_description' => [
                Rule::requiredIf(fn () => $request->sale_document_type == 1),
                'nullable',
                'string',
                'max:255'
            ],
            'sale_ubigeo' => [
                Rule::requiredIf(fn () => $request->sale_document_type == 1),
                'nullable',
                'string',
                'max:10'
            ],
        ]);

        $sale_note = null;

        try {
            $res = DB::transaction(function () use ($request) {

                $person_id = $request->get('person_id');
                $person = [];
                //dd($request->all());
                if($person_id) {
                    $person = Person::find($person_id);

                    $person->update([
                        //'document_type_id' => $request->get('alu_document_type'),
                        'short_name' => $request->get('names'),
                        'full_name' => $request->get('afather') . ' ' . $request->get('amother') . ' ' . $request->get('names'),
                        //'number' => $request->get('alu_number'),
                        'telephone' => $request->get('telephone'),
                        'email' => $request->get('email'),
                        'address' => $request->get('address'),
                        'names' => $request->get('names'),
                        'father_lastname' => $request->get('afather'),
                        'mother_lastname' => $request->get('amother'),
                        'birthdate' => $request->get('birthdate'),
                        'gender' => $request->get('gender'),
                        'status' => true,
                        'ubigeo' => $request->get('ubigeo'),
                        'ubigeo_description' => $request->get('ubigeo_description'),
                        'country_id' => $request->get('country_id')
                    ]);


                } else {
                    $person = Person::create([
                        'document_type_id' => $request->get('alu_document_type'),
                        'short_name' => $request->get('names'),
                        'full_name' => $request->get('afather') . ' ' . $request->get('amother') . ' ' . $request->get('names'),
                        'number' => $request->get('alu_number'),
                        'telephone' => $request->get('telephone'),
                        'email' => $request->get('email'),
                        'is_provider' => false,
                        'is_client' => true,
                        'address' => $request->get('address'),
                        'names' => $request->get('names'),
                        'father_lastname' => $request->get('afather'),
                        'mother_lastname' => $request->get('amother'),
                        'birthdate' => $request->get('birthdate'),
                        'gender' => $request->get('gender'),
                        'status' => true,
                        'ubigeo' => $request->get('ubigeo'),
                        'ubigeo_description' => $request->get('ubigeo_description'),
                        'country_id' => $request->get('country_id')
                    ]);
                }

                $student = AcaStudent::firstOrCreate(
                    ['person_id' => $person->id],
                    ['student_code'  => $request->get('alu_number')]
                );

                $user = User::firstOrCreate(
                    [
                        'email' => $request->get('email')
                    ],
                    [
                        'name'          => $request->get('names'),
                        'password'      => Hash::make($request->get('alu_number')),
                        'local_id'      => 1,
                        'person_id'     => $person->id
                    ]
                );

                $user->assignRole('Alumno');

                $payments = json_encode($request->get('payments'));

                $sale_note = Sale::create([
                    'sale_date' => Carbon::now()->format('Y-m-d'),
                    'user_id' => Auth::id(),
                    'client_id' => $person->id,
                    'local_id' => 1,
                    'total' => $request->get('total'),
                    'advancement' => $request->get('aplasos') ? 0 :  $request->get('total'),
                    'total_discount' => 0,
                    //'payments' => $request->get('aplasos') ? null : $payments,
                    'petty_cash_id' => null,
                    'physical' => 1,
                    'invoice_razon_social' => $request->get('sale_full_name'),
                    'invoice_ruc' => $request->get('number'),
                    'invoice_direccion' => $request->get('sale_address') ?? null,
                    'invoice_ubigeo' => $request->get('sale_ubigeo') ?? null,
                    'invoice_ubigeo_description' => $request->get('sale_ubigeo_description') ?? null,
                    'invoice_type' => $request->get('sale_document_type') ?? 2,
                    'payment_installments' => $request->get('aplasos') ? true : false
                ]);


                $courses = $request->get('courses');
                $suscriptions = $request->get('subscriptions');

                if(count($courses) > 0){
                    //dd($courses);
                    foreach ($courses as $course) {
                        $xcourse = AcaCourse::find($course['id']);
                        SaleProduct::create([
                            'sale_id' => $sale_note->id,
                            'product_id' => $xcourse->id,
                            'product' => json_encode($xcourse),
                            'saleProduct' => json_encode($course),
                            'price' => $course['price'],
                            'discount' => 0,
                            'quantity' => 1,
                            'total' => round($course['price'], 2),
                            'entity_name_product' => AcaCourse::class,
                            'advancement' => $request->get('aplasos') ? 0 :  round($course['price'], 2),
                        ]);

                        AcaCapRegistration::updateOrCreate([
                            'student_id'        => $student->id,
                            'course_id'         => $course['id']
                        ],
                        [
                            'status'            => true,
                            'sale_note_id'      => $sale_note->id,
                            'modality_id'       => 3,
                            'unlimited'         => $request->get('aplasos') ? false : true,
                            'date_start'        => Carbon::now()->format('Y-m-d'),
                            'date_end'          => $request->get('date_end') ?? null,
                            'payment_installments' => $request->get('aplasos') ? true : false,
                            'amount_paid' => $course['price']
                        ]);
                    }
                }

                if(count($suscriptions) > 0){

                    foreach ($suscriptions as $suscription) {
                        $xSuscription = AcaSubscriptionType::find($suscription['id']);

                        SaleProduct::create([
                            'sale_id' => $sale_note->id,
                            'product_id' => $suscription['id'],
                            'product' => json_encode($xSuscription), //producto original
                            'saleProduct' => json_encode($suscription), //producto modificado o no(este va)
                            'price' => $suscription['price'],
                            'discount' => 0,
                            'quantity' => 1,
                            'total' => round($suscription['price'], 2),
                            'entity_name_product' => AcaSubscriptionType::class,
                            'advancement' => $request->get('aplasos') ? 0 :  round($course['price'], 2),
                        ]);

                        $dateStart = Carbon::today(); // Solo fecha sin hora
                        $dateEnd = null;

                        // Calcular fecha de fin
                        switch ($xSuscription->period) {
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


                        AcaStudentSubscription::updateOrCreate([
                            'student_id' => $student->id,
                            'subscription_id' => $suscription['id']
                        ],
                        [
                            'date_start' => $dateStart->format('Y-m-d'),
                            'date_end' => $request->get('date_end') ?? $dateEnd->format('Y-m-d'),
                            'status' => true,
                            'notes' => null,
                            'renewals' => 0,
                            'registration_user_id' => $user->id,
                            'onli_sale_id' => null,
                            'amount_paid' => round($suscription['price'], 2),
                            'xsale_note_id' => $sale_note->id
                        ]);

                    }
                }

                $totalAmount = $request->get('total');
                $installments = $request->get('number_installments');
                $date = Carbon::parse($request->get('date_end'));

                $baseAmount = intdiv($totalAmount, $installments);   // 166
                $remainder  = $totalAmount % $installments;

                // Crear cuotas
                for ($i = 1; $i <= $installments; $i++) {

                    // Si todavía hay residuo, esta cuota es +1 sol
                    if ($i <= $remainder) {
                        $amount = $baseAmount + 1;
                    } else {
                        $amount = $baseAmount;
                    }

                    SalePaymentSchedule::create([
                        'sale_id'          => $sale_note->id,
                        'installment_number' => $i,
                        'payment_date'       => $date->copy(),
                        'amount_to_pay'      => $amount,
                        'amount_paid'        => 0,
                        'remaining_amount'   => $amount,
                    ]);

                    // Siguiente mes
                    $date->addMonth();
                }


                 //enviar correo con credenciales y cronograma de pagos

                //  $sale = Sale::with(['saleProduct', 'client'])
                //  ->where('id', $sale_note->id)
                //  ->first();
                 $name = Person::where('id', $sale_note->client_id)->first()->short_name;
                 $cronograma = SalePaymentSchedule::where('sale_id', $sale_note->id)->get();

                 Mail::to($request->get('email'))->send(new CuotasMail($sale_note, $name, $cronograma));

            });





            //return response()->json($res);
            return to_route('acco_sales_special_rates');
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function spaceSalesCreate($id, $fromId){

        $payments = PaymentMethod::all();

        $saleDocumentTypes = DB::table('sale_document_types')->whereIn('sunat_id', ['01', '03'])->get();

        $standardIdentityDocument = DB::table('identity_document_type')->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                'districts.name AS district_name',
                'provinces.name AS province_name',
                'departments.name AS department_name',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS city_name")
            )
            ->get();

         // Cargar venta + productos + cliente
        $sale_note = Sale::with('saleProduct')
            ->with('client')
            ->where('id', $id)
            ->firstOrFail();

        // ===============================
        // Obtener cuota pendiente
        // ===============================
        $installment = $this->getNextPendingInstallment($id);
        $description = null;
        $feeItem = [];
        $msg = 'Esta venta no tiene cuotas pendientes por pagar.';

        if ($installment) {
            $msg = null;

            $isLast = $this->isLastInstallment($id, $installment->installment_number);

            // Descripción
            $description = $this->buildInstallmentDescription(
                $installment->installment_number,
                $sale_note
            );

            // Fecha de la siguiente cuota
            $nextPaymentDate = $isLast ? null : $this->getNextInstallmentDate($id, $installment->installment_number);

            // Total cuotas
            $totalQuotas = SalePaymentSchedule::where('sale_id', $id)->count();

            $feeItem = [
                'description'         => $description,
                'installment_id'      => $installment->id,
                'installment_number'  => $installment->installment_number,
                'total_installments'  => $totalQuotas,
                'amount_to_pay'       => $installment->amount_to_pay,
                'amount_paid'         => $installment->amount_paid,
                'remaining_amount'    => $installment->remaining_amount,
                'payment_date'        => $installment->payment_date,
                'next_payment_date'   => $nextPaymentDate,
                'is_last_installment' => $isLast
            ];
        }

        $student = AcaStudent::where('person_id', $sale_note->client->id)->first();

        return Inertia::render('Sales::AccountsReceivable/CreateFeeDocument', [
            'payments' => $payments,
            'saleDocumentTypes' => $saleDocumentTypes,
            'saleNote' => $sale_note,
            'feeItem' => $feeItem,
            'taxes' => array(
                'igv' => $this->igv,
                'icbper' => $this->icbper
            ),
            'standardIdentityDocument' => $standardIdentityDocument,
            'departments' => $ubigeo,
            'student' => $student,
            'message' => $msg,
            'fromId' => $fromId
        ]);
    }
    private function getNextPendingInstallment($saleId)
    {
        return SalePaymentSchedule::where('sale_id', $saleId)
            ->where('remaining_amount', '>', 0) // cuota aún pendiente
            ->orderBy('installment_number', 'ASC')
            ->first();
    }
    private function getNextInstallmentDate($saleId, $currentInstallmentNumber)
    {
        return SalePaymentSchedule::where('sale_id', $saleId)
            ->where('installment_number', '>', $currentInstallmentNumber)
            ->orderBy('installment_number', 'ASC')
            ->value('payment_date'); // solo devuelve la fecha
    }
    private function isLastInstallment($saleId, $currentInstallmentNumber)
    {
        $maxNumber = SalePaymentSchedule::where('sale_id', $saleId)
            ->max('installment_number');

        return $currentInstallmentNumber == $maxNumber;
    }

    private function buildInstallmentDescription($installmentNumber, $sale)
    {
        $names = [];

        foreach ($sale->saleProduct as $item) {

            if ($item->product) {
                $productData = json_decode($item->product, true);

                if (isset($productData['description'])) {
                    $names[] = $productData['description'];
                }
            }
        }

        $totalItems = count($names);

        // Si hay más de 2 cursos/ítems, resumen
        if ($totalItems > 2) {
            return "Pago de la cuota {$installmentNumber} correspondiente a: {$totalItems} cursos.";
        }

        // Si hay 1 o 2, mostrar la lista completa
        $list = implode(', ', $names);

        return "Pago de la cuota {$installmentNumber} correspondiente a: {$list}.";
    }

    public function storeSpacePayments(Request $request, $id)
    {
        ///se validan los campos requeridos
        //dd($request->get('payments'));
        $rules = [
            'serie' => 'required',
            'client_number' => 'required',
            'client_rzn_social' => 'required',
            'date_issue' => 'required',
            'date_end' => 'required',
            'next_payment_date' => 'required_unless:is_last_installment,true',
            'sale_documenttype_id' => 'required',
            'total' => 'required|numeric|min:0|not_in:0',
            'payments.*.type' => 'required',
            'payments.*.amount' => 'required|numeric|min:0|not_in:0|regex:/^[\d]{0,11}(\.[\d]{1,2})?$/',
            'items.*.quantity' => 'required|numeric|min:0|not_in:0|regex:/^[\d]{0,11}(\.[\d]{1,2})?$/',
            'items.*.amount' => 'required|numeric|min:0|not_in:0|regex:/^[\d]{0,11}(\.[\d]{1,2})?$/',
            'items.*.total' => 'required|numeric|min:0|not_in:0|regex:/^[\d]{0,11}(\.[\d]{1,2})?$/',
            'client_id' => 'required',
        ];

        $messages = [
            'items.*.quantity.required' => 'Ingrese Cantidad',
            'items.*.unit_price.required' => 'Ingrese precio',
            'items.*.unit_price.numeric' => 'Solo Numeros',
            'items.*.quantity.numeric' => 'Solo Numeros',
            'items.*.total.required' => 'Ingrese total',
            // --- MENSAJES PERSONALIZADOS PARA LA NUEVA REGLA ---
            'quotas.amounts.required_if' => 'Debe configurar al menos una cuota de pago para la forma de pago "Crédito".',
            'quotas.amounts.min' => 'Debe configurar al menos una cuota de pago para la forma de pago "Crédito".',
            'quotas.amounts.array' => 'Los montos de las cuotas deben ser un formato válido.', // Mensaje si no es un array
            // ----------------------------------------------------
        ];

        $this->validate(
            $request,
            $rules,
            $messages
        );

        try {
            $res = DB::transaction(function () use ($request) {

                ///si no existe una caja abierta para el usuario logueado en la tienda donde inicio session
                ///se crea una caja para poder hacer la venta
                $student_id = $request->get('student_id');
                $local_id = Auth::user()->local_id;
                $scheduleId = null;
                $petty_cash = PettyCash::firstOrCreate([
                    'user_id' => Auth::id(),
                    'state' => 1,
                    'local_sale_id' => $local_id
                ], [
                    'date_opening' => Carbon::now()->format('Y-m-d'),
                    'time_opening' => date('H:i:s'),
                    'income' => 0
                ]);
                ///se crea la venta

                $sale = Sale::with('saleProduct')->findOrFail($request->get('sale_id'));

                $forma_pago = $request->get('forma_pago');

                $existingPayments = $sale->payments ? json_decode($sale->payments , true) : [];

                // Obtener el nuevo pago enviado desde el request
                $newPayments = $request->get('payments');

                // Asegurar que sea un array
                if (!is_array($newPayments)) {
                    $newPayments = [$newPayments];
                }

                if ($forma_pago && $forma_pago === 'Contado') {
                    // Mezclar pagos anteriores + nuevos
                    $updatedPayments = array_merge($existingPayments, $newPayments);

                    // Guardar nuevamente como JSON
                    $sale->payments = json_encode($updatedPayments);
                    $sale->save();
                }

                ///obtenemos la serie elejida para hacer la venta
                ///para traer tambien su numero correlativo

                $serie = Serie::find($request->get('serie'));

                ///se convierte el total de la venta a letras
                $numberletters = new NumberLetter();
                $tido = SaleDocumentType::find($request->get('sale_documenttype_id'));
                ///creamos el documento de la venta para enviar a sunat

                $typeOperation = '0101';
                if ($request->get('total') > 700) {
                    $typeOperation = '1001';
                }

                $document = SaleDocument::create([
                    'sale_id'                       => $sale->id,
                    'serie_id'                      => $request->get('serie'),
                    'number'                        => str_pad($serie->number, 9, '0', STR_PAD_LEFT),
                    'status'                        => true,
                    'client_type_doc'               => $request->get('client_dti'),
                    'client_number'                 => $request->get('client_number'),
                    'client_rzn_social'             => $request->get('client_rzn_social'),
                    'client_address'                => $request->get('client_direction'),
                    'client_ubigeo_code'            => $request->get('client_ubigeo'),
                    'client_ubigeo_description'     => $request->get('client_ubigeo_description'),
                    'client_phone'                  => $request->get('client_phone'),
                    'client_email'                  => $request->get('client_email'),
                    'invoice_ubl_version'           => $this->ubl,
                    'invoice_type_operation'        => $typeOperation,
                    'invoice_type_doc'              => $tido->sunat_id,
                    'invoice_serie'                 => $serie->description,
                    'invoice_correlative'           => $serie->number,
                    'invoice_type_currency'         => 'PEN',
                    'invoice_broadcast_date'        => $request->get('date_issue'),
                    'invoice_due_date'              => $request->get('date_end'),
                    'invoice_send_date'             => Carbon::now()->format('Y-m-d'),
                    'invoice_legend_code'           => '1000',
                    'invoice_legend_description'    => $numberletters->convertToLetter($request->get('total')),
                    'invoice_status'                => 'registrado',
                    'user_id'                       => Auth::id(),
                    'additional_description'        => $request->get('additional_description'),
                    'overall_total'                 => $request->get('total')
                ]);

                $forceUnlimited = $request->get('force_unlimited') ?? false;
                $nextPaymentDate = $request->get('next_payment_date');

                $this->updateRegistrosEstudiante($sale, $student_id, $request->get('total'), $document, $forceUnlimited, $nextPaymentDate);

                ///obtenemos los productos o servicios para insertar en los
                ///detalles de la venta y el documento
                $products = $request->get('items');

                ///totales de la cabecera
                $mto_oper_taxed = 0;
                $mto_igv = 0;
                $total_icbper = 0;
                $porcentage_icbper = $this->icbper;
                $total_discount = 0;
                $total = 0;


                foreach ($products as $produc) {
                    $actualPrice = $produc['actualPrice'];
                    /// ahora tenemos que saber si es un producto o servicio ya existente
                    /// o si sera creado para esta venta, verificaremos esto por el id del producto
                    /// si el id es nulo quiere decir que es un producto nuevo y procedemos a crearlo
                    $product_id = $produc['id'];

                    /// imiciamos las variables para hacer los calculos por item;
                    $percentage_igv = $this->igv;
                    $mto_base_igv = 0;
                    $porcentage_item_icbper = 0;

                    $price_sale = $produc['amount'];
                    $nfactorIGV = round(($percentage_igv / 100) + 1, 2);
                    $ifactorIGV = round($percentage_igv / 100, 2);
                    $quantity = $produc['quantity'];
                    $value_unit = 0;
                    $igv = 0;
                    $total_tax = 0;
                    $icbper = 0;
                    $value_sale = 0;
                    $total_item = 0;
                    $mto_discount = 0;
                    $array_discounts = [];

                    if ($produc['afe_igv'] == '10') {
                        //valor unitario presio de venta / 1.IGV para quitarle el igv
                        //se tiene que quitar el igv porque el sistema trabaja con los precios
                        //incluido el igv
                        $value_unit = round($price_sale / $nfactorIGV, 2);
                        //la base para hacer el descuento
                        $base = round($value_unit * $quantity, 2);
                        //el sistema resive un monto fijo como descuento y lo convierte a un porcentaje
                        $factor = (($produc['discount'] * 100) / $price_sale) / 100;
                        //el descuento se aplica por unidad vendida
                        $descuento_monto = $factor * $value_unit * $quantity;
                        //a la base igv le restamos el descuento
                        $mto_base_igv = ($value_unit * $quantity) - $descuento_monto;
                        //una ves restada la vase lo multiplicamos por el 18% vigente para sacar
                        //el valor total igv
                        $igv = ($mto_base_igv * $ifactorIGV);
                        //total del item
                        $total_item = (($value_unit * $quantity) - $descuento_monto) + $igv;
                        //el valor de la venta
                        $value_sale = ($value_unit * $quantity) - $descuento_monto;
                        //si tiene descuento creamos el array de descuento
                        //2023-07-20 el sistema solo trabaja con un descuento
                        if ($produc['discount'] > 0) {
                            //el precio unitario se calcula
                            //(Valor venta + Total Impuestos) / Cantidad
                            $unit_price = round(($value_sale + $igv) / $quantity, 2);
                            $array_discounts[0] = array(
                                'value'     => $produc['discount'],
                                'type'      => '00',
                                'base'      => round($base, 2),
                                'factor'    => $factor,
                                'monto'     => round($descuento_monto, 2)
                            );
                        } else {
                            //el precio unitario es el mismo
                            $unit_price = $price_sale;
                        }

                        $mto_discount = round($descuento_monto, 2);
                    }
                    if ($produc['afe_igv'] == '20') { //Exonerated

                    }
                    if ($produc['afe_igv'] == '30') { //Unaffected

                    }


                    $total_tax = $igv;

                    //se inserta los datos al detalle del documento
                    SaleDocumentItem::create([
                        'document_id'           => $document->id,
                        'product_id'            => $product_id,
                        'cod_product'           => $product_id,
                        'decription_product'    => $produc['description'],
                        'unit_type'             => 'ZZ',
                        'quantity'              => $produc['quantity'],
                        'mto_base_igv'          => $mto_base_igv,
                        'percentage_igv'        => $this->igv,
                        'igv'                   => $igv,
                        'total_tax'             => $total_tax,
                        'type_afe_igv'          => $produc['afe_igv'],
                        'icbper'                => $icbper,
                        'factor_icbper'         => $porcentage_item_icbper,
                        'mto_value_sale'        => $value_sale,
                        'mto_value_unit'        => $value_unit,
                        'mto_price_unit'        => $unit_price,
                        'price_sale'            => $price_sale,
                        'mto_total'             => round($unit_price * $produc['quantity'], 2),
                        'mto_discount'          => $mto_discount ?? 0,
                        'json_discounts'        => json_encode($array_discounts),
                        'entity_name_product'   => SalePaymentSchedule::class
                    ]);

                    $mto_igv = $mto_igv + $igv; //total del igv
                    $total_icbper = $total_icbper + $icbper; //total del impuesto a la bolsa plastica
                    $mto_oper_taxed = $mto_oper_taxed + $value_sale; // total operaciones gravadas
                    $total = $total + $total_item; // total de la venta general

                    /////actualizar el cronograma de pago

                    $paySche = SalePaymentSchedule::findOrFail($produc['id']);
                    $scheduleId = $paySche->id;
                    $amountPaid = $paySche->amount_paid ?? 0;
                    $remaining  = $paySche->remaining_amount ?? 0;

                    // Nuevo avance
                    $newAmountPaid = $amountPaid + $price_sale;

                    // Evitar negativos en el saldo
                    $newRemaining  = max(0, $remaining - $price_sale);

                    // Determinar si la cuota quedó totalmente pagada
                    $isPaid = ($newRemaining <= 0);

                    $paySche->update([
                        'amount_paid'      => $newAmountPaid,
                        'remaining_amount' => $newRemaining,
                        'document_id'      => $document->id,
                        'is_paid'          => $isPaid,
                    ]);

                }

                //totales de la cabesera del documento
                $total_taxes = $mto_igv + $total_icbper;
                $subtotal = $total_taxes + $mto_oper_taxed;
                $ttotal = round($total, 1);
                $difference = abs($ttotal - $subtotal);
                $rounding = number_format($difference, 2);

                $document->update([
                    'invoice_mto_oper_taxed'    => $mto_oper_taxed,
                    'invoice_mto_igv'           => $mto_igv,
                    'invoice_icbper'            => $total_icbper,
                    'invoice_total_taxes'       => $total_taxes,
                    'invoice_value_sale'        => $mto_oper_taxed,
                    'invoice_subtotal'          => $subtotal,
                    'invoice_rounding'          => $rounding,
                    'invoice_mto_imp_sale'      => $ttotal,
                    'invoice_sunat_points'      => null,
                    'invoice_status'            => 'Pendiente',
                    'forma_pago'                => $forma_pago,
                    'status_pay'                => true,
                    'schedule_id'               => $scheduleId
                ]);

                $serie->increment('number', 1);


                //aca una ves que crea modificar la venta princiapal para actualizar la deuda

                $amount = (float) $request->input('total'); // convierte a número flotante

                // Si quieres asegurar que sea >= 0:
                $amount = max(0, $amount);

                $sale->advancement = ($sale->advancement ?? 0) + $amount;
                $sale->petty_cash_id = $petty_cash->id;
                $sale->save();


                $this->paymentDestinationsStore($request->get('payments'), $scheduleId, $document->id, $sale->id);

                return $document;
            });

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
            // Devuelve una respuesta de error
        }
    }

    public function updateRegistrosEstudiante($sale, $student_id, $price_sale, $document, $forceUnlimited = false, $nextPaymentDate = null)
    {
        $products = $sale->saleProduct;
        $total = $price_sale; // monto que llega desde el request (lo que está pagando el alumno)

        foreach ($products as $product) {

            /* ===============================================================
                1️⃣  SI ES UN CURSO
            =============================================================== */
            if ($product->entity_name_product === 'Modules\Academic\Entities\AcaCourse') {

                $registration = AcaCapRegistration::where('student_id', $student_id)
                    ->where('course_id', $product->product_id)
                    ->first();

                if (!$registration) {
                    throw new \Exception("No existe registro del alumno para el curso ID: {$product->product_id}");
                }

                $toPay = $registration->amount_paid - ($registration->advancement ?? 0); // lo que falta pagar

                // Validar si se paga todo o parcialmente
                if ($forceUnlimited || $total >= $toPay) {
                    // Cancela todo lo que falta
                    $registration->advancement = $registration->amount_paid;
                    $total -= $toPay;
                    $registration->unlimited = true;
                    $registration->date_start = null;
                    $registration->date_end = null;
                } else {
                    // Pago parcial
                    $registration->advancement = ($registration->advancement ?? 0) + $total;
                    $total = 0;
                    $registration->unlimited = false;
                    $registration->date_end = $nextPaymentDate;
                }

                // Registrar documento
                $registration->sale_note_id = $sale->id;
                $registration->document_id = $document->id;

                $registration->save();
            }


            /* ===============================================================
                2️⃣  SI ES UNA SUSCRIPCIÓN
            =============================================================== */
            if ($product->entity_name_product === 'Modules\Academic\Entities\AcaSubscriptionType') {

                $studentSubscription = AcaStudentSubscription::with('subscription')
                    ->where('student_id', $student_id)
                    ->where('subscription_id', $product->product_id)
                    ->first();

                if (!$studentSubscription) {
                    throw new \Exception("No existe suscripción del alumno para ID: {$product->product_id}");
                }

                $toPay = $studentSubscription->amount_paid - ($studentSubscription->advancement ?? 0);

                // Pago total o parcial
                if ($forceUnlimited || $total >= $toPay) {

                    // Cancela la suscripción completa
                    $adv = $studentSubscription->amount_paid;
                    $total -= $toPay;

                    // Calcular fecha de finalización según periodo
                    $dateStart = Carbon::parse($studentSubscription->date_start);
                    $dateEnd = $this->calculateDateEnd($studentSubscription->subscription->period, $dateStart);

                } else {

                    // Pago parcial
                    $adv = ($studentSubscription->advancement ?? 0) + $total;
                    $total = 0;

                    // La fecha de fin será la próxima fecha de pago
                    $dateEnd = $nextPaymentDate;
                }

                // Actualización
                $studentSubscription->advancement = $adv;
                $studentSubscription->date_end = $dateEnd;
                $studentSubscription->status = true;
                $studentSubscription->xdocument_id = $document->id;

                $studentSubscription->save();
            }

        } // END FOREACH

    }

    public function paymentDestinationsStore($payments, $schedule_id, $document_id, $sale_id){
        foreach($payments as $payment){
            SalePaymentScheduleDestination::create([
                'method_id' => $payment['type'],
                'date_payment' => $payment['date_payment'],
                'reference' => $payment['reference'],
                'amount' => $payment['amount'],
                'schedule_id' => $schedule_id,
                'document_id' => $document_id,
                'sale_id' => $sale_id,
                'status' => 'paid',
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function paymentDestinationsExportExcel(Request $request)
    {
        $this->validate($request, [
            'search'           => 'nullable|string|max:255',
            'issue_date'       => [
                'required',
                'string',
                // Cambiamos la expresión regular para aceptar un solo YYYY-MM-DD O el rango
                'regex:/^\d{4}-\d{2}-\d{2}( a \d{4}-\d{2}-\d{2})?$/'
            ]
        ]);

        $filters = [
            'issue_date' => $request->get('issue_date'),
            'search' => $request->get('search'),
        ];

        $excelExportJob = ExcelExportJob::create([
            'user_id' => Auth::id(),
            'report_type' => 'VENTAS',
            'status' => 'pending',
            'filters' => json_encode($filters), // Guardar los filtros para referencia
        ]);

        // 2. Despachar el Job de exportación a la cola
        PaymentDestinations::dispatch(
            $filters,
            $excelExportJob->id,
            Auth::id()
        )->onQueue('exports'); // O la cola que uses para exportaciones

        return response()->json([
            'message' => 'La exportación se ha iniciado. Se le notificará cuando esté lista para descargar.',
            'job_id' => $excelExportJob->id,
        ], 202); // 202 Accepted, indica que la petición ha sido aceptada para procesamiento.
    }

    public function exportStatus($id)
    {
       // Busca el job por ID y verifica que pertenezca al usuario
        $excelExportJob = ExcelExportJob::where('id', $id)
                                        ->where('user_id', Auth::id())
                                        ->first();

        if (!$excelExportJob) {
            return response()->json(['message' => 'Estado de exportación no encontrado o no autorizado.'], 404);
        }

        return response()->json($excelExportJob);
    }
}
