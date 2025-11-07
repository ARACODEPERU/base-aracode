<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\CMS\Entities\CmsSection;
use Modules\Onlineshop\Entities\OnliItem;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Onlineshop\Entities\OnliSale;
use Modules\Onlineshop\Entities\OnliSaleDetail;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use Illuminate\Support\Facades\Validator;
use App\Mail\StudentRegistrationMailable;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmPurchaseMail;
use Carbon\Carbon;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaCapRegistration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class WebPageController extends Controller
{
    protected $listcard;

    public function __construct()
    {

        $this->listcard = CmsSection::where('component_id', 'cursos_area_5')
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();
    }

    public function home()
    {
        return view('pages.home');
    }
    
    public function about()
    {
        return view('pages.about');
    }
    
    public function billing()
    {
        return view('pages.billing');
    }
    
    public function lms()
    {
        return view('pages.lms');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function nosotros()
    {

        $banner = CmsSection::where('component_id', 'nosotros_banner_area_11')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();


        $visions = CmsSection::where('component_id', 'nosotros_vision_area_12')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $lider = CmsSection::where('component_id', 'nosotros_lider_area_13')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('pages.nosotros', [
            'banner' => $banner,
            'visions' => $visions,
            'lider' => $lider
        ]);
    }


    public function shopcart()
    {
        return view('pages.shop-cart');
    }

    public function accounts()
    {
        return view('pages.accounts');
    }

    public function servicios()
    {

        $banner = CmsSection::where('component_id', 'cursos_banner_area_14')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $title = CmsSection::where('component_id', 'cursos_titulo_area_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('pages.servicios', [
            'banner' => $banner,
            'title' => $title
        ]);
    }

    public function capacitacion()
    {

        $banner = CmsSection::where('component_id', 'cursos_banner_area_14')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $title = CmsSection::where('component_id', 'cursos_titulo_area_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('pages.capacitacion', [
            'banner' => $banner,
            'title' => $title
        ]);
    }

    public function suscripcion()
    {

        $banner = CmsSection::where('component_id', 'cursos_banner_area_14')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $title = CmsSection::where('component_id', 'cursos_titulo_area_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('pages.suscripcion', [
            'banner' => $banner,
            'title' => $title
        ]);
    }

    public function automatizacion()
    {

        $banner = CmsSection::where('component_id', 'cursos_banner_area_14')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $title = CmsSection::where('component_id', 'cursos_titulo_area_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('pages.automatizacion', [
            'banner' => $banner,
            'title' => $title
        ]);
    }

    public function agencia()
    {

        $banner = CmsSection::where('component_id', 'cursos_banner_area_14')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $title = CmsSection::where('component_id', 'cursos_titulo_area_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('pages.agencia', [
            'banner' => $banner,
            'title' => $title
        ]);
    }

    public function imagenprofesional()
    {

        $banner = CmsSection::where('component_id', 'cursos_banner_area_14')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $title = CmsSection::where('component_id', 'cursos_titulo_area_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        return view('pages.imagen-profesional', [
            'banner' => $banner,
            'title' => $title
        ]);
    }

    public function contacto()
    {
        $banner = CmsSection::where('component_id', 'nosotros_banner_area_11')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        $title = CmsSection::where('component_id', 'header_area_1')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();


        return view('pages.contacto', [
            'banner' => $banner,
            'title' => $title
        ]);
    }

    public function carrito()
    {

        return view('pages.carrito');
    }


    public function pay()
    {
        return view('pages.pay');
    }

    public function pagar_auth(Request $request){ //pago cuando es usuario autenticado

        $personInvoice = $request->only([
            'names',
            'ruc',
            'dni',
            'nombreCompleto',
            'document_type',
            'razonSocial',
            'email',
            'statusRuc',
            'conditionRuc'
        ]);

        // Convertir a JSON
        $personInvoice = json_encode($personInvoice);
        $productids = $request->get('item_id');
        $person = Person::where('id', Auth::user()->person_id)->first();
        $comprador_nombre = $person->full_name;
        $comprador_telefono = $person->telephone;
        $comprador_email = $request->get('email');
        $student = AcaStudent::where('person_id', $person->id)->first();

        $preference_id = null;

        try {
            DB::beginTransaction();
            MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
            $client = new PreferenceClient();
            $items = [];
            $products = [];
            $total = 0;
            $type_doc = "";
            if(Auth::user()->document_type_id == 1) $type_doc = "DNI";
            if(Auth::user()->document_type_id == 2) $type_doc = "RUC";
            if(Auth::user()->document_type_id == 3) $type_doc = "PASAPORTE";
            if(Auth::user()->document_type_id == 4) $type_doc = "CARNET DE EXTRANJERIA";

            $user = Auth::user();
            $person = Person::where('id', $user->person_id)->first();

            $sale = OnliSale::create([
                'module_name'                   => 'Onlineshop',
                'person_id'                     => $person->id,
                'clie_full_name'                => $comprador_nombre,
                'phone'                         => $comprador_telefono,
                'email'                         => $comprador_email,
                'response_status'               => 'pendiente',
            ]);

            $productquantity = 1;

            foreach ($productids as $key => $id) {

                $product = OnliItem::find($id);

                //$this->matricular_curso($product, $student); //poner esto al final de pagar!!!!! revisar

                array_push($items, [
                    'id' => $id,
                    'title' => $product->name,
                    'quantity'      => floatval($productquantity),
                    'currency_id'   => 'PEN',
                    'unit_price'    => floatval($product->price)
                ]);

                array_push($products, [
                    'image' => $product->image,
                    'name' => $product->name,
                    'item_id' => $product->item_id,
                    'student_id' => $student->id,
                    'price' => floatval($product->price),
                    'quantity'      => floatval($productquantity),
                    'total' => (floatval($productquantity) * floatval($product->price))
                ]);

                $total = $total + (floatval($productquantity) * floatval($product->price));

                OnliSaleDetail::create([
                    'sale_id'       => $sale->id,
                    'item_id'       => $product->item_id,
                    'entitie'       => $product->entitie,
                    'price'         => $product->price,
                    'quantity'      => floatval($productquantity),
                    'onli_item_id'  => $id
                ]);
            }

            $preference = $client->create([
                "items" => $items,
            ]);

            $preference_id =  $preference->id;
            ///dd($preference);
            DB::commit();
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            // Manejar la excepción
            DB::rollback();
            $response = $e->getApiResponse();
            dd($response); // Mostrar la respuesta para obtener más detalles
        }


        return view('pages/pagar', [
            'preference' => $preference_id,
            'products' => $products,
            'total' => $total,
            'sale_id' => $sale->id,
            'student_id' => $student->id,
            'personInvoice' => $personInvoice,
        ]);
    }

    public function pagar(Request $request)
    {
        $personInvoice = $request->only([
            'names',
            'ruc',
            'dni',
            'nombreCompleto',
            'document_type',
            'razonSocial',
            'email',
            'statusRuc',
            'conditionRuc'
        ]);

        // Convertir a JSON
        $personInvoice = json_encode($personInvoice);

        $validator = Validator::make($request->all(), [
            'names' => 'required|string|max:255',
            'app' => 'required|string|max:255',
            'apm' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'dni' => 'required|numeric',
            'phone' => 'required|string|max:255',
            'email' => 'required|unique:people,email',
            'password' => 'required|string|min:8',
            'password2' => 'required|string|min:8|same:password',
        ], [
            // Mensajes personalizados
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password2.required' => 'El campo repetir contraseña es obligatorio.',
            'password2.same' => 'Las contraseñas no coinciden.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }




        $productids = $request->get('item_id');

        $comprador_nombre = $request->get('names');
        $comprador_telefono = $request->get('phone');
        $comprador_email = $request->get('email');

        $preference_id = null;

        try {
            DB::beginTransaction();
            MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));
            $client = new PreferenceClient();
            $items = [];
            $products = [];
            $total = 0;

            $person = Person::create([
                'document_type_id' => $request->get('type'),
                'short_name' => $comprador_nombre,
                'full_name' => $comprador_nombre . ' ' . $request->get('app') . ' ' . $request->get('apm'),
                'number' => $request->get('dni'),
                'telephone' => $comprador_telefono,
                'email' => $comprador_email,
                'is_provider' => false,
                'is_client' => true,
                'names' => $comprador_nombre,
                'father_lastname' => $request->get('app'),
                'mother_lastname' => $request->get('apm'),
                'gender' => 'M',
                'status' => true
            ]);

            $user = User::firstOrNew(['email' => $person->email]);

            if ($user->exists) {
                // El usuario ya existe, redirige al usuario a iniciar sesión
                if (Auth::check()) {
                } else {
                    return redirect()->route('login')->with('message', 'Este correo electrónico ya está registrado. Por favor, inicia sesión.');
                }
            } else {
                $user = User::create([
                    'name' => $person->names,
                    'email' => $person->email,
                    'password' => Hash::make($request->get('password')),
                    'person_id' => $person->id
                ]);
                Auth::login($user);
                //asignar el rol de estudiante....
                if (!$user->hasRole('Alumno')) {
                    $role = Role::where('name', 'Alumno')->first();
                    $user->assignRole($role);
                }
            }

            $sale = OnliSale::create([
                'module_name'                   => 'Onlineshop',
                'person_id'                     => $person->id,
                'clie_full_name'                => $comprador_nombre,
                'phone'                         => $comprador_telefono,
                'email'                         => $comprador_email,
                'response_status'               => 'pendiente',
            ]);

            $productquantity = 1;

            $student = AcaStudent::firstOrCreate(
                ['person_id' => $person->id],
                ['student_code' => $person->number, 'status' => true]
            );

            foreach ($productids as $key => $id) {

                $product = OnliItem::find($id);

                //$this->matricular_curso($product, $student); //poner esto al final de pagar!!!!! revisar

                array_push($items, [
                    'id' => $id,
                    'title' => $product->name,
                    'quantity'      => floatval($productquantity),
                    'currency_id'   => 'PEN',
                    'unit_price'    => floatval($product->price)
                ]);

                array_push($products, [
                    'image' => $product->image,
                    'name' => $product->name,
                    'price' => floatval($product->price),
                    'student_id' => $student->id,
                    'item_id'       => $product->item_id,
                    'quantity'      => floatval($productquantity),
                    'total' => (floatval($productquantity) * floatval($product->price))
                ]);

                $total = $total + (floatval($productquantity) * floatval($product->price));

                OnliSaleDetail::create([
                    'sale_id'       => $sale->id,
                    'item_id'       => $product->item_id,
                    'entitie'       => $product->entitie,
                    'price'         => $product->price,
                    'quantity'      => floatval($productquantity),
                    'onli_item_id'  => $id
                ]);
            }

            $preference = $client->create([
                "items" => $items,
            ]);

            // $preference->back_urls = array(
            //     "success" => route('web_gracias_por_comprar_tu_entrada', $sale->id),
            //     // "failure" => "http://www.tu-sitio/failure",
            //     // "pending" => "http://www.tu-sitio/pending"
            // );

            $preference_id =  $preference->id;
            ///dd($preference);
            DB::commit();
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            // Manejar la excepción
            Auth::logout();
            DB::rollback();
            $response = $e->getApiResponse();
            dd($response); // Mostrar la respuesta para obtener más detalles
        }

        return view('pages/pagar', [
            'preference' => $preference_id,
            'products' => $products,
            'total' => $total,
            'sale_id' => $sale->id,
            'student_id' => $student->id,
            'personInvoice' => $personInvoice,
        ]);
    }

    public function thanks($id)
    {


            $sale = OnliSale::with('details.item')->where('id', $id)->first();

            if ($sale) {
                // Obtener los onli_item_id de los detalles de la venta
                $itemIds = $sale->details->pluck('onli_item_id')->toArray();

                // Obtener los cursos (OnliItem) que coincidan con los onli_item_id
                $courses = OnliItem::whereIn('id', $itemIds)->get();
            } else {
                // Si no se encuentra la venta, inicializar cursos como una colección vacía
                $courses = collect();
            }

            return view('pages.thanks', [
                'sale' => $sale,
                'courses' => $courses,
            ]);
    }

    public function email()
    {
        return view('layouts.email_gratitude');
    }


    public function privacidad()
    {

        $banner = CmsSection::where('component_id', 'nosotros_banner_area_11')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->first();

        return view('pages.politicas-de-privacidad', [
            'banner' => $banner
        ]);
    }

    public function claims()
    {

        return view('pages/complaints-book');
    }

    public function eclaims()
    {

        return view('emails/e-complaints_book');
    }

    public function construction()
    {
        return view('pages.construction');
    }

    public function processPayment(Request $request, $id)
    {
        MercadoPagoConfig::setAccessToken(env('MERCADOPAGO_TOKEN'));

        $client = new PaymentClient();
        $sale = OnliSale::find($id);
        // dd($request->get('transaction_amount'));
        if ($sale->response_status == 'approved') {
            return response()->json(['error' => 'el pedido ya fue procesado, ya no puede volver a pagar'], 412);
        } else {
            try {

                $payment = $client->create([
                    "token" => $request->get('token'),
                    "issuer_id" => $request->get('issuer_id'),
                    "payment_method_id" => $request->get('payment_method_id'),
                    "transaction_amount" => (float) $request->get('transaction_amount'),
                    "installments" => $request->get('installments'),
                    "payer" => $request->get('payer')
                ]);

                if ($payment->status == 'approved') {

                    $sale->email = $request->get('payer')['email'];
                    $sale->total = $request->get('transaction_amount');
                    $sale->identification_type = $request->get('payer')['identification']['type'];
                    $sale->identification_number = $request->get('payer')['identification']['number'];
                    $sale->response_status = $payment->status;
                    $sale->response_id = $request->get('collection_id');
                    $sale->response_date_approved = Carbon::now()->format('Y-m-d');
                    $sale->response_payer = json_encode($request->all());
                    $sale->response_payment_method_id = $request->get('payment_type');
                    $sale->mercado_payment_id = $payment->id;
                    $sale->mercado_payment = json_encode($payment);

                    ///enviar correo
                    Mail::to($sale->email)
                        ->send(new ConfirmPurchaseMail(OnliSale::with('details.item')->where('id', $id)->first()));

                    $sale->save();
                    $this->enviar_correo_con_cursos($id);

                    return response()->json([
                        'status' => $payment->status,
                        'message' => $payment->status_detail,
                        'url' => route('web_gracias_por_cursos', $sale->id) // AQUI solo la ruta q muestre datos de la compra
                    ]);
                } else {

                    return response()->json([
                        'status' => $payment->status,
                        'message' => $payment->status_detail,
                        'url' => route('web_pagar')
                    ]);

                    $sale->delete();
                }
            } catch (\MercadoPago\Exceptions\MPApiException $e) {
                // Manejar la excepción
                $response = $e->getApiResponse();
                $content  = $response->getContent();

                $message = $content['message'];
                return response()->json(['error' => 'Error al procesar el pago: ' . $message], 412);
            }
        }
    }

    public function graciasCompra($id)
    {
        $sale = OnliSale::where('id', $id)->with('details.item')->first();
        $person = Person::where('id', $sale->person_id)->first();
        $details = $sale->details;
        $itemIds = $details->pluck('item_id')->toArray();
        $products = OnliItem::whereIn('item_id', $itemIds)->get();
        //$student = AcaStudent::where('person_id', $person->id)->first();

        $courses = [];
        foreach ($details as $k => $detail) {
            $item = OnliItem::find($detail->onli_item_id);
            $courses[$k] = [
                'image'       => $item->image,
                'name'        => $item->name,
                'description' => $item->description,
                'type'        => $item->additional,
                'modality'    => $item->additional1,
                'price'      => $item->price
            ];
        }

        return view('pages.gracias', [
            'products' => $products,
            'sale' => $sale,
            'person' => $person,
        ]);
    }

    private function enviar_correo_con_cursos($sale_id)
    {
        $sale = OnliSale::where('id', $sale_id)->with('details.item')->first();
        $person = Person::where('id', $sale->person_id)->first();
        $details = $sale->details;
        //$itemIds = $details->pluck('item_id')->toArray();
        //        $products = OnliItem::whereIn('item_id', $itemIds)->get();
        // $student = AcaStudent::where('person_id', $person->id)->first();

        $courses = [];
        foreach ($details as $k => $detail) {
            $item = OnliItem::find($detail->onli_item_id);
            $courses[$k] = [
                'image'       => $item->image,
                'name'        => $item->name,
                'description' => $item->description,
                'type'        => $item->additional,
                'modality'    => $item->additional1,
                'price'      => $item->price
            ];
        }

        //////////codigo enviar correo /////
        Mail::to($person->email)
            ->send(new StudentRegistrationMailable([
                'courses'   => $courses,
                'names'     => $person->names,
                'email'      => $person->email,
                'password'  => $person->number
            ]));
    }

    private function matricular_curso($producto, $student)
    {

        $course_id = $producto->item_id;

        $registration = AcaCapRegistration::create([
            'student_id' => $student->id,
            'course_id' => $course_id,
            'status' => true,
            'modality_id' => 3,
            'unlimited' => true
        ]);
    }
	
	    public function storeCourseFree(Request $request)
    {
        // 🔹 VALIDACIÓN
        $validator = Validator::make($request->all(), [
            'courseFree' => 'required',
            'courseInterest' => 'required',
            'nombres' => 'required|string|max:255',
            'apaterno' => 'required|string|max:255',
            'amaterno' => 'required|string|max:255',
            'tidocumento' => 'required',
            'numero' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($request) {
                    // Validar combinación única de tipo documento + número
                    $exists = DB::table('people')
                        ->where('document_type_id', $request->tidocumento)
                        ->where('number', $value)
                        ->exists();

                    if ($exists) {
                        $fail('El número de documento ya está registrado para este tipo de documento.');
                    }
                },
            ],
            'email' => 'required|email|unique:people,email',
            'phone' => 'required|string|max:20',
            'pais' => 'required',
            'ciudad' => 'required',
            'genero' => 'required',
            'fecha_nacimiento' => 'required',
            'politicas' => 'accepted', // debe estar marcado
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.unique' => 'El correo electrónico ya está registrado.',
            'politicas.accepted' => 'Debe aceptar las políticas para continuar.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // 🔹 REGISTRO EN TABLA people
            $person = Person::create([
                'short_name' => $request->nombres,
                'full_name' => $request->apaterno. ' '.$request->amaterno.' '.$request->nombres,
                'document_type_id' => $request->tidocumento,
                'names' => $request->nombres,
                'father_lastname' => $request->apaterno,
                'mother_lastname' => $request->amaterno,
                'number' => $request->numero,
                'telephone' => $request->phone,
                'email' => $request->email,
                'country_id' => $request->pais,
                'status' => true,
                'ubigeo' => $request->ubigeo ?? null,
                'ubigeo_description' => $request->ciudad ?? null,
                'gender' => $request->genero ?? null,
                'birthdate' => $request->fecha_nacimiento ?? null
            ]);

            // 🔹 REGISTRO EN TABLA aca_students
            $student = AcaStudent::create([
                'student_code' => $request->numero,
                'person_id' => $person->id,
                'new_student' => true,
                'arrival_source_id' => 1,
                'arrival_source_information' => '01'
            ]);

            // 🔹 REGISTRO EN TABLA aca_cap_registrations
            AcaCapRegistration::create([
                'student_id' => $student->id,
                'course_id' => $request->courseFree,
                'status' => true,
                'certificate_date' => Carbon::now(),
                'arrival_source_id' => 1,
                'arrival_source_information' => '01'
            ]);

            AcaStudentCoursesInterest::create([
                'student_id' => $student->id,
                'course_id' => $request->courseInterest,
                'status' => 0
            ]);

            // 🔹 REGISTRO EN TABLA users
            User::create([
                'name' => $request->nombres,
                'email' => $request->email,
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make($request->numero),
                'local_id' => 1,
                'person_id' => $person->id,
                'status' => true,
                'updated_information' => false,
                'tour_completed' => true,
            ]);

            $courses = [];
            $item = OnliItem::where('item_id', '=', $request->courseInterest)->first();
            $courses[0] = [
                'image'       => $item->image,
                'name'        => $item->name,
                'description' => $item->description,
                'type'        => $item->additional,
                'modality'    => $item->additional1,
                'price'      => "Gratis",
            ];

            //////////codigo enviar correo /////
            Mail::to($request->email)->send(new StudentRegistrationMailable([
                'courses'   => $courses,
                'names'     => $request->nombres,
                'user'      => $request->email,
                'password'  => $request->numero,
            ]));
            // 3. CONFIRMACIÓN (COMMIT)
            DB::commit();
            // 🔹 MENSAJE DE ÉXITO
            return redirect()->back()->with('success', 'Registro completado exitosamente.');

        } catch (\Throwable $th) {
             // 5. REVERSIÓN (ROLLBACK) si algo falla
             DB::rollBack();
             dd($th);
            return redirect()->back()->with('fail', 'Registro fallido Reintentar.');
        }

    }

}
