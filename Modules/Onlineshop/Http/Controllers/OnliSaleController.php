<?php

namespace Modules\Onlineshop\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
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

class OnliSaleController extends Controller
{
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
            ->leftJoin('sales', 'sales.id', 'onli_sales.nota_sale_id')
            ->select(
                'onli_sales.*',
                'people.telephone',
                'people.email',
                'people.full_name AS student_name',
                'people.number AS student_number',
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

            dd($preference);
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
}
