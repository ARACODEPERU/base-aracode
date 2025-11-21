<?php

namespace Modules\Onlineshop\Http\Controllers;

use App\Models\Country;
use App\Models\District;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\PettyCash;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;
use Modules\Onlineshop\Entities\OnliItem;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Onlineshop\Entities\OnliSaleDetail;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudentSubscription;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Onlineshop\Rules\ValidationRuleCourseSubscriptions;

class OnliSaleController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $inputs = request()->has('search');
        $search = request()->input('search');

        $sales = DB::table('onli_sales')
            ->join('people', 'onli_sales.person_id', '=', 'people.id')
            ->leftJoin('aca_students', 'aca_students.person_id', '=', 'people.id')
            ->leftJoin('sales', 'sales.id', 'onli_sales.nota_sale_id')
            ->select(
                'onli_sales.*',
                'people.telephone',
                'people.email',
                'people.full_name AS student_name',
                'people.number AS student_number',
                'aca_students.id AS student_id',
                DB::raw("(
                    SELECT JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', osd.id,
                            'price', osd.price,
                            'quantity', osd.quantity,
                            'product', CASE
                                WHEN osd.entitie = 'Modules\\\Academic\\\Entities\\\AcaCourse' THEN
                                    (SELECT JSON_OBJECT(
                                        'id', aca_courses.id,
                                        'description', aca_courses.description,
                                        'title', NULL,
                                        'origin', 'ACA'
                                    )
                                    FROM aca_courses
                                    WHERE aca_courses.id = osd.item_id)
                                WHEN osd.entitie = 'Modules-Academic-Entities-AcaCourse' THEN
                                    (SELECT JSON_OBJECT(
                                        'id', aca_courses.id,
                                        'description', aca_courses.description,
                                        'title', NULL,
                                        'origin', 'ACA'
                                    )
                                    FROM aca_courses
                                    WHERE aca_courses.id = osd.item_id)
                                WHEN osd.entitie = 'Modules\\\Academic\\\Entities\\\AcaSubscriptionType' THEN
                                    (SELECT JSON_OBJECT(
                                        'id', aca_subscription_types.id,
                                        'description', aca_subscription_types.description,
                                        'title', aca_subscription_types.title,
                                        'origin', 'ACA'
                                    )
                                    FROM aca_subscription_types
                                    WHERE aca_subscription_types.id = osd.item_id)
                                WHEN osd.entitie = 'App\\\Models\\\Product' THEN
                                    (SELECT JSON_OBJECT(
                                        'id', products.id,
                                        'description', products.description,
                                        'title', products.interne,
                                        'origin', 'PRO'
                                    )
                                    FROM products
                                    WHERE products.id = osd.item_id)
                                ELSE NULL
                            END
                        )
                    )
                    FROM onli_sale_details osd
                    WHERE osd.sale_id = onli_sales.id
                ) AS details"),
                'sales.invoice_type',
                'sales.invoice_razon_social',
                'sales.invoice_ruc',
            )
            ->when($inputs, function ($query) use ($search) {
                $query->whereDate('created_at', '=', $search);
            })
            ->orderBy('onli_sales.id', 'DESC')
            ->paginate(20);

        return Inertia::render('Onlineshop::Sales/List', [
            'sales' => $sales,
            'filters' => request()->all('search'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function shoppingCart($mo)
    {
        return Inertia::render('Onlineshop::Sales/ShoppingCart', [
            'personInvoice' => Person::find(Auth::user()->person_id),
            'payTipe' => $mo
        ]);
    }

    public function formMercadopago(Request $request)
    {
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
        $client = new PreferenceClient();
        $items = [];
        $msg = null;
        $success = true;
        $preference_id = null;
        $personInvoice = $request->get('person');
        $products = $request->get('items');
        $student_id = AcaStudent::where('person_id', Auth::user()->person_id)->value('id');
        $studentSubscribed = AcaStudentSubscription::where('student_id', $student_id)
            ->where('status', true)
            ->first();

        if (count($products) > 0) {
            foreach ($products as $product) {
                $xpro = AcaCourse::find($product['id']);
                $price = 0;

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
                } else {
                    $price = number_format($xpro->price, 2, '.', '');
                }

                array_push($items, [
                    'id' => $xpro->id,
                    'title' => trim($xpro->description),
                    'quantity'      => floatval(1),
                    'currency_id'   => 'PEN',
                    'unit_price'    => floatval($price)
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

        return Inertia::render('Onlineshop::Sales/MercadopagoForm', [
            'preference_id' => $preference_id,
            'personInvoice' => $personInvoice
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request): RedirectResponse
    {
        $ids = $request->get('item_id');

        $validator = Validator::make($request->all(), [
            'names' => 'required',
            'app' => 'required',
            'apm' => 'required',
            'phone' => 'required',
            'email' => 'required|unique:users,email',
            'document_type' => 'required',
            'number' => 'required',
        ], [
            'names.required' => 'El nombre es requerido',
            'app.required' => 'El apellido paterno requerido',
            'apm.required' => 'El apellido materno requerido',
            'phone.required' => 'El teléfono es requerido',
            'email.required' => 'El email es requerido',
            'document_type.required' => 'El tipo de documento es requerido',
            'number.required' => 'El numero de documento es requerido'
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $person = Person::firstOrCreate(
            [
                'document_type_id' => $request->get('document_type'),
                'number' => $request->get('number')
            ],
            [
                'description'           => 'Estudiante',
                'email'                 => $request->get('email'),
                'short_name'            => $request->get('names'),
                'full_name'             => $request->get('names') . ' ' . $request->get('app') . ' ' . $request->get('apm'),
                'telephone'             => $request->get('phone'),
                'is_client'             => true,
                'names'                 => $request->get('names'),
                'father_lastname'       => $request->get('app'),
                'mother_lastname'       => $request->get('apm')

            ]
        );

        $student = AcaStudent::create([
            'student_code'  => $request->get('number'),
            'person_id'     => $person->id
        ]);

        $user = User::firstOrCreate(
            [
                'email' => $request->get('email')
            ],
            [
                'name'          => $request->get('names'),
                'password'      => Hash::make($request->get('number')),
                'local_id'      => 1,
                'person_id'     => $person->id
            ]
        );

        $user->assignRole('Alumno');

        $sale = OnliSale::create([
            'module_name'                   => 'Academic',
            'person_id'                     => $person->id,
            'email'                         => $request->get('email'),
            'clie_full_name'                => $request->get('app') . ' ' . $request->get('apm') . ' ' . $request->get('names'),
            'identification_type'           => $request->get('document_type'),
            'identification_number'         => $request->get('number'),
            'response_status'               => 'pendiente',
            'installments'                  => 'not'
        ]);

        foreach ($ids as $id) {

            $item = OnliItem::find($id);
            OnliSaleDetail::create([
                'sale_id'       => $sale->id,
                'item_id'       => $item->item_id,
                'entitie'       => $item->entitie,
                'price'         => $item->price,
                'quantity'      => 1,
                'onli_item_id'  => $id
            ]);

            AcaCapRegistration::create([
                'student_id'        => $student->id,
                'course_id'         => $item->item_id,
                'status'            => false
            ]);
        }

        return to_route('web_pagar', [
            'sale'     => $sale->id
        ]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function getPreference($id)
    {
        //dd(env('MERCADOPAGO_TOKEN'));
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
        try {
            $client = new PreferenceClient();
            $preference = $client->get($id);

            //dd($preference);
        } catch (MPApiException $e) {

            $response = $e->getApiResponse();
            $content  = $response->getContent();

            $message = $content['message'];
            $status = $content['status'];
            $error = $content['error'];

            // Mostrar o manejar los valores obtenidos
            echo "Mensaje: " . $message . PHP_EOL;
            echo "Estado: " . $status . PHP_EOL;
            echo "Error: " . $error . PHP_EOL;
        }
    }

    public function create(){
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

        //dd($subscriptionTypes);
        return Inertia::render('Onlineshop::Sales/Create',[
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

    public function saveFinishSale (Request $request) {

        $update_id = null;
        $user_id = null;

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
                Rule::requiredIf(fn () => $request->document_type == 6),
                'string',
                'max:255'
            ],
            'sale_ubigeo_description' => [
                Rule::requiredIf(fn () => $request->document_type == 6),
                'string',
                'max:255'
            ],
            'sale_ubigeo' => [
                Rule::requiredIf(fn () => $request->document_type == 6),
                'string',
                'max:10'
            ],
        ]);

        $person = Person::create([
            'document_type_id' => $request->get('alu_document_type'),
            'short_name' => $request->get('names'),
            'full_name' => $request->get('apather') . ' ' . $request->get('amother') . ' ' . $request->get('names'),
            'number' => $request->get('alu_number'),
            'telephone' => $request->get('telephone'),
            'email' => $request->get('email'),
            'is_provider' => false,
            'is_client' => true,
            'address' => $request->get('address'),
            'names' => $request->get('names'),
            'father_lastname' => $request->get('apather'),
            'mother_lastname' => $request->get('amother'),
            'gender' => $request->get('gender'),
            'status' => true,
            'ubigeo' => $request->get('ubigeo'),
            'ubigeo_description' => $request->get('ubigeo_description'),
            'country_id' => $request->get('country_id')
        ]);

        $student = AcaStudent::create([
            'student_code'  => $request->get('alu_number'),
            'person_id'     => $person->id
        ]);

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
            'payments' => $request->get('aplasos') ? null : $payments,
            'petty_cash_id' => null,
            'physical' => 1,
            'invoice_razon_social' => $request->get('sale_full_name'),
            'invoice_ruc' => $request->get('number'),
            'invoice_direccion' => $request->get('sale_address') ?? null,
            'invoice_ubigeo' => $request->get('sale_ubigeo') ?? null,
            'invoice_type' => $request->get('sale_document_type') ?? 2,
            'payment_installments' => $request->get('aplasos') ? true : false
        ]);

        $sale = OnliSale::create([
            'module_name'                   => 'Onlineshop',
            'person_id'                     => $person->id,
            'clie_full_name'                => $request->get('apather') . ' ' . $request->get('amother') . ' ' . $request->get('names'),
            'phone'                         => $request->get('telephone'),
            'email'                         => $request->get('email'),
            'response_status'               => $request->get('aplasos') ? 'pago_en_cuotas' :  $request->get('estado_pago'),
            'response_status_detail'        => $request->get('aplasos') ? 'pago en cuotas' :  null,
            'nota_sale_id'                  => $sale_note->id,
            'email_sent'                    => false,
            'installments'                  => $request->get('aplasos') ? 'yes' : 'not'
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
                    'entity_name_product' => AcaCourse::class
                ]);

                OnliSaleDetail::create([
                    'sale_id'       => $sale->id,
                    'item_id'       => $course['id'],
                    'entitie'       => AcaCourse::class,
                    'price'         => $course['price'],
                    'quantity'      => 1,
                    'onli_item_id'  => null
                ]);

                AcaCapRegistration::create([
                    'student_id'        => $student->id,
                    'course_id'         => $course['id'],
                    'status'            => true,
                    'sale_note_id'      => $sale_note->id,
                    'modality_id'       => 3,
                    'unlimited'         => $request->get('aplasos') ? false : true,
                    'date_start'        => Carbon::now()->format('Y-m-d'),
                    'date_end'          => $request->get('date_end') ?? null,
                    'payment_installments' => $request->get('aplasos') ? true : false
                ]);
            }
        }

        if(count($suscriptions) > 0){

            foreach ($suscriptions as $suscription) {
                $xSuscription = AcaSubscriptionType::find($suscription['id']);

                SaleProduct::create([
                    'sale_id' => $sale_note->id,
                    'product_id' => $suscription['id'],
                    'product' => json_encode($xSuscription),
                    'saleProduct' => json_encode($suscription),
                    'price' => $suscription['price'],
                    'discount' => 0,
                    'quantity' => 1,
                    'total' => round($suscription['price'], 2),
                    'entity_name_product' => AcaSubscriptionType::class
                ]);

                OnliSaleDetail::create([
                    'sale_id'       => $sale->id,
                    'item_id'       => $suscription['id'],
                    'entitie'       => AcaSubscriptionType::class,
                    'price'         => $suscription['price'],
                    'quantity'      => 1,
                    'onli_item_id'  => null
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


                AcaStudentSubscription::create([
                    'student_id' => $student->id,
                    'subscription_id' => $suscription['id'],
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
        return to_route('onlineshop_sales');
    }
}
