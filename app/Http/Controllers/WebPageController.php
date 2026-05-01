<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Sale;
use App\Models\SaleProduct;
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
use Modules\Academic\Entities\AcaTeacher;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use Illuminate\Support\Facades\Validator;
use App\Mail\StudentRegistrationMailable;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmPurchaseMail;
use App\Models\Country;
use App\Models\Department;
use App\Models\District;
use Carbon\Carbon;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourseLanding;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaStudentCoursesInterest;
use Modules\CMS\Entities\CmsLanding;
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

    public function index()
    {
        return view('pages.home');
    }

    public function academy()
    {
        return view('pages.academy');
    }

    public function landing($slug)
    {
        $courses = OnliItem::whereHas('course')->with('course')->latest()->get();
        //$courses = $courses->shuffle();
        $categories = AcaCategoryCourse::all();
        $types = getEnumValues('onli_items', 'additional', 0, 1);

        //$landingPage = CmsLanding::where('slug', $slug)->first();
        $landingPage = CmsLanding::where('menu_id', '01')->first();


        $ids = $landingPage->data_related['items'] ?? [];

        $coursesFree = AcaCourse::whereIn('id', $ids)->get();

        $title = CmsSection::where('component_id', 'cursos_titulo_area_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $countries = Country::orderBy('description')->get();
        $documentTypes = DB::table('identity_document_type')->get();
        //$ubigeo = District::with('province.department')->get();
        $ubigeo = Department::get();
        //dd($ubigeo);

        $p = 12; //numero de cursos mostrados PAGINACION

        return view('pages.landing', [
            'courses' => $courses,
            'categories' => $categories,
            'title' => $title,
            'types' => $types,
            'p' => $p,
            'landingPage' => $landingPage,
            'coursesFree' => $coursesFree,
            'countries' => $countries,
            'documentTypes' => $documentTypes,
            'ubigeo' => $ubigeo
        ]);
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

    public function teachers()
    {
        return view('pages.teachers');
    }

    public function bookamauta()
    {
        return view('pages/book-description');
    }

    public function subscriptions()
    {
        return view('pages/subscriptions');
    }

    public function privacypolicies()
    {
        return view('pages/privacy-policies');
    }

    public function politicas_devoluciones()
    {
        return view('pages/politicas_devoluciones');
    }

    public function terms()
    {
        return view('pages/terms');
    }

    public function courses()
    {
        $courses = OnliItem::whereHas('course') // Filtra para que solo traiga items con curso existente
                    ->with('course')                  // Carga la relación para evitar el problema de N+1
                    ->latest()
                    ->get();

        //$courses = $courses->shuffle();
        //$categories = AcaCategoryCourse::all();
        $types = getEnumValues('onli_items', 'additional', 0, 1);

        // $banner = CmsSection::where('component_id', 'cursos_banner_area_14')  //siempre cambiar el id del componente
        //     ->join('cms_section_items', 'section_id', 'cms_sections.id')
        //     ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
        //     ->select(
        //         'cms_items.content',
        //         'cms_section_items.position'
        //     )
        //     ->orderBy('cms_section_items.position')
        //     ->first();

        $title = CmsSection::where('component_id', 'cursos_titulo_area_15')  //siempre cambiar el id del componente
            ->join('cms_section_items', 'section_id', 'cms_sections.id')
            ->join('cms_items', 'cms_section_items.item_id', 'cms_items.id')
            ->select(
                'cms_items.content',
                'cms_section_items.position'
            )
            ->orderBy('cms_section_items.position')
            ->get();

        $p = 12; //numero de cursos mostrados PAGINACION

        return view('pages.courses', [
            'courses' => $courses,
            //'categories' => $categories,
            // 'banner' => $banner,
            'title' => $title,
            'types' => $types,
            'p' => $p,
        ]);
    }

    public function coursedescription($id)
    {
        if(is_numeric($id) && $id >0){
            $item = OnliItem::find($id);

            $course = AcaCourse::with('category')
                ->with('modality')
                ->with('modules')
                ->with('teachers.teacher.person.resumes')
                ->with('brochure')
                ->with('agreements')
                ->where('id', $item->item_id)
                ->first();

            $latest_courses = OnliItem::with('course')
                ->orderBy('id', 'desc')
                ->where('id', '!=', $id)
                ->take(10)
                ->get()
                ->shuffle()
                ->take(3);


            return view('pages.course-description', [
                'course' => $course,
                'item' => $item,
                'latest_courses' => $latest_courses
            ]);
        }else{
            return redirect()->route('course_url_slug', $id);
        }

    }

public function course_url_slug($id){
        $landing = AcaCourseLanding::with('course')
            ->with('course.category')
            ->with('course.modality')
            ->with('course.brochure')
            ->where('url_slug', $id)->first();

        $teachersPremium = [];

        if ($landing && $landing->staff_section) {
            $staffData = is_array($landing->staff_section)
                ? $landing->staff_section
                : json_decode($landing->staff_section, true);

            if (is_array($staffData) && isset($staffData['teachers']) && is_array($staffData['teachers'])) {
                $teacherIds = array_column($staffData['teachers'], 'teacher_id');

                $teachers = AcaTeacher::whereIn('id', $teacherIds)
                    ->with('person', 'resumes')
                    ->get();

                    foreach ($teacherIds as $index => $teacherId) {
                        $teacher = $teachers->firstWhere('id', $teacherId);
                        if ($teacher && $teacher->person) {
                            $person = $teacher->person;
                            $resumes = $teacher->resumes;
                            $imageUrl = $person->image
                                ? asset('storage/' . $person->image)
                                : 'https://ui-avatars.com/api/?name=' . urlencode($person->formatted_name) . '&rounded=true&size=200';

                               //dd($landing->staff_section['teachers'][0]['teacher_ocupation']);
                            $teachersPremium[] = [
                                'name' => $landing->staff_section['teachers'][$index]['teacher_names'] ?? $person->formatted_name,
                                'role' => $landing->staff_section['teachers'][$index]['teacher_ocupation'] ?? $person->ocupacion,
                                'img' => $imageUrl,
                                'resumes' => $resumes,
                            ];
                        }
                    }
            }
        }

        $colors = [
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
        ];


        // Obtener OnliItem asociado al curso
        $onliItem = null;
        if ($landing && $landing->course) {
            $onliItem = OnliItem::where('item_id', $landing->course->id)->first();
        }

        return view ('pages.course-landing', [
            'landing' => $landing,
            'teachers_premium' => $teachersPremium,
            'colors' => $colors,
            'onli_item_id' => $onliItem ? $onliItem->id : null,
        ]);
    }

    public function course_landing_preview($id){
        $landing = AcaCourseLanding::with('course')
            ->with('course.category')
            ->with('course.modality')
            ->with('course.brochure')
            ->where('id', $id)->first();

        $teachersPremium = [];

        if ($landing && $landing->staff_section) {
            $staffData = is_array($landing->staff_section)
                ? $landing->staff_section
                : json_decode($landing->staff_section, true);

            if (is_array($staffData) && isset($staffData['teachers']) && is_array($staffData['teachers'])) {
                $teacherIds = array_column($staffData['teachers'], 'teacher_id');

                $teachers = AcaTeacher::whereIn('id', $teacherIds)
                    ->with('person', 'resumes')
                    ->get();

                foreach ($teacherIds as $index => $teacherId) {
                    $teacher = $teachers->firstWhere('id', $teacherId);
                    if ($teacher && $teacher->person) {
                        $person = $teacher->person;
                        $resumes = $teacher->resumes;
                        $imageUrl = $person->image
                            ? asset('storage/' . $person->image)
                            : 'https://ui-avatars.com/api/?name=' . urlencode($person->formatted_name) . '&rounded=true&size=200';

                           //dd($landing->staff_section['teachers'][0]['teacher_ocupation']);
                        $teachersPremium[] = [
                            'name' => $landing->staff_section['teachers'][$index]['teacher_names'] ?? $person->formatted_name,
                            'role' => $landing->staff_section['teachers'][$index]['teacher_ocupation'] ?? $person->ocupacion,
                            'img' => $imageUrl,
                            'resumes' => $resumes,
                        ];
                    }
                }
            }
        }
        $colors = [
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
            '#ff010b', // Rojo Institucional
            '#575555', // Gris Institucional
        ];


        // Obtener OnliItem asociado al curso
        $onliItem = null;
        if ($landing && $landing->course) {
            $onliItem = OnliItem::where('item_id', $landing->course->id)->first();
        }

        return view ('pages.course-landing', [
            'landing' => $landing,
            'teachers_premium' => $teachersPremium,
            'colors' => $colors,
            'onli_item_id' => $onliItem ? $onliItem->id : null,
        ]);
    }

    public function cursodescripcion($id)
    {
        $item = OnliItem::find($id);

        $course = AcaCourse::with('category')
            ->with('modality')
            ->with('modules')
            ->with('teachers.teacher.person.resumes')
            ->with('brochure')
            ->with('agreements')
            ->where('id', $item->item_id)
            ->first();

        $latest_courses = OnliItem::with('course')
            ->orderBy('id', 'desc')
            ->where('id', '!=', $id)
            ->take(10)
            ->get()
            ->shuffle()
            ->take(3);

        return view('pages.course-description', [
            'course' => $course,
            'item' => $item,
            'latest_courses' => $latest_courses
        ]);
    }



    public function index2()
    {
        return view('pages.home2');
    }



    public function shopcart()
    {
        return view('pages.shop-cart');
    }

    public function cartPreference(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'integer|distinct'
        ]);

        if (!config('services.mercadopago.token') || !config('services.mercadopago.key')) {
            return response()->json(['error' => 'MercadoPago no esta configurado correctamente.'], 422);
        }

        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));

        $items = $this->cartItemsFromIds($validated['item_id']);
        $mercadoItems = [];
        $products = [];
        $total = 0;

        foreach ($items as $item) {
            $price = round((float) $item->price, 2);
            $mercadoItems[] = [
                'id' => $item->id,
                'title' => $item->name,
                'quantity' => 1,
                'currency_id' => 'PEN',
                'unit_price' => $price
            ];

            $products[] = [
                'id' => $item->id,
                'image' => $item->image,
                'name' => $item->name,
                'item_id' => $item->item_id,
                'price' => $price,
                'quantity' => 1,
                'total' => $price,
                'additional' => $item->additional
            ];

            $total += $price;
        }

        try {
            $preference = (new PreferenceClient())->create([
                'items' => $mercadoItems,
            ]);
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            $response = $e->getApiResponse();
            $content = $response ? $response->getContent() : [];

            return response()->json([
                'error' => 'No se pudo crear la preferencia de MercadoPago: ' . ($content['message'] ?? $e->getMessage())
            ], 412);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'No se pudo crear la preferencia de MercadoPago.'
            ], 500);
        }

        return response()->json([
            'preference' => $preference->id,
            'products' => $products,
            'total' => round($total, 2),
            'public_key' => config('services.mercadopago.key'),
        ]);
    }

    public function cartProcessPayment(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|array|min:1',
            'item_id.*' => 'integer|distinct',
            'cardFormData' => 'required|array',
        ]);

        $cardData = $validated['cardFormData'];
        $items = $this->cartItemsFromIds($validated['item_id']);
        $expectedTotal = $this->cartTotal($items);
        $sentTotal = round((float) ($cardData['transaction_amount'] ?? 0), 2);

        if (abs($expectedTotal - $sentTotal) > 0.01) {
            return response()->json([
                'error' => 'El importe enviado no coincide con el precio actual de los productos.'
            ], 422);
        }

        if (!config('services.mercadopago.token')) {
            return response()->json(['error' => 'MercadoPago no esta configurado correctamente.'], 422);
        }

        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));
        $payer = $this->normalizeMercadoPagoPayer($cardData['payer'] ?? []);
        if (!$payer) {
            return response()->json(['error' => 'Ingresa un correo valido en el formulario de MercadoPago.'], 422);
        }

        try {
            $payment = (new PaymentClient())->create([
                'token' => $cardData['token'] ?? null,
                'issuer_id' => $cardData['issuer_id'] ?? null,
                'payment_method_id' => $cardData['payment_method_id'] ?? null,
                'transaction_amount' => $expectedTotal,
                'installments' => $cardData['installments'] ?? 1,
                'payer' => $payer,
            ]);
        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            $response = $e->getApiResponse();
            $content = $response ? $response->getContent() : [];
            return response()->json([
                'error' => 'Error al procesar el pago: ' . ($content['message'] ?? $e->getMessage())
            ], 412);
        }

        if ($payment->status !== 'approved') {
            return response()->json([
                'status' => $payment->status,
                'message' => $payment->status_detail,
            ], 422);
        }

        $identification = $payer['identification'] ?? [];

        DB::beginTransaction();
        try {
            $sale = OnliSale::create([
                'module_name' => 'Onlineshop',
                'person_id' => null,
                'clie_full_name' => trim(($payer['first_name'] ?? '') . ' ' . ($payer['last_name'] ?? '')),
                'phone' => $payer['phone']['number'] ?? null,
                'email' => $payer['email'] ?? null,
                'total' => $expectedTotal,
                'transaction_amount' => $expectedTotal,
                'installments' => $cardData['installments'] ?? 1,
                'identification_type' => $identification['type'] ?? null,
                'identification_number' => $identification['number'] ?? null,
                'token' => $cardData['token'] ?? null,
                'response_status' => $payment->status,
                'response_status_detail' => $payment->status_detail,
                'response_id' => null,
                'response_date_approved' => Carbon::now()->format('Y-m-d'),
                'response_payer' => json_encode($request->all()),
                'response_payment_method_id' => $cardData['payment_method_id'] ?? null,
            ]);

            $sale->mercado_payment_id = $payment->id;
            $sale->mercado_payment = json_encode($payment);

            foreach ($items as $item) {
                OnliSaleDetail::create([
                    'sale_id' => $sale->id,
                    'item_id' => $item->item_id,
                    'entitie' => $item->entitie,
                    'price' => $item->price,
                    'quantity' => 1,
                    'onli_item_id' => $item->id,
                ]);
            }

            $sale->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo registrar la venta aprobada.'], 500);
        }

        return response()->json([
            'status' => $payment->status,
            'message' => $payment->status_detail,
            'sale_id' => $sale->id,
            'payer' => [
                'names' => $sale->clie_full_name,
                'email' => $sale->email,
                'document_type' => $sale->identification_type,
                'document_number' => $sale->identification_number,
            ],
        ]);
    }

    public function cartFinalize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sale_id' => 'required|integer|exists:onli_sales,id',
            'account_mode' => 'required|in:login,create',
            'email' => 'required|email',
            'password' => 'nullable|string|min:6',
            'names' => 'required_if:account_mode,create|nullable|string|max:255',
            'dni' => 'required_if:account_mode,create|nullable|string|max:20',
            'invoice_type' => 'required|in:boleta,factura',
            'invoice_name' => 'required_if:invoice_type,boleta|nullable|string|max:255',
            'invoice_dni' => 'required_if:invoice_type,boleta|nullable|string|max:20',
            'invoice_email' => 'nullable|email',
            'invoice_ruc' => 'required_if:invoice_type,factura|nullable|string|max:20',
            'invoice_business_name' => 'required_if:invoice_type,factura|nullable|string|max:255',
            'invoice_address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $onliSale = OnliSale::with('details.item')->findOrFail($validated['sale_id']);

        if ($onliSale->response_status !== 'approved') {
            return response()->json(['error' => 'El pago aun no fue aprobado.'], 422);
        }

        if ($onliSale->nota_sale_id) {
            return response()->json(['error' => 'Esta venta ya fue finalizada.'], 422);
        }

        if ($validated['account_mode'] === 'create') {
            if (User::where('email', $validated['email'])->exists()) {
                return response()->json(['error' => 'Este email ya tiene una cuenta. Elige iniciar sesion.'], 422);
            }

            $existingPerson = Person::where('number', $validated['dni'])->first();
            if ($existingPerson && User::where('person_id', $existingPerson->id)->exists()) {
                return response()->json(['error' => 'Este DNI ya tiene una cuenta. Elige iniciar sesion.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            if ($validated['account_mode'] === 'login') {
                if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password'] ?? ''])) {
                    DB::rollBack();
                    return response()->json(['error' => 'El email o la contrasena no son correctos.'], 422);
                }

                $request->session()->regenerate();
                $user = Auth::user();
                $person = Person::findOrFail($user->person_id);
            } else {
                $person = Person::firstOrCreate(
                    ['number' => $validated['dni']],
                    [
                        'document_type_id' => 1,
                        'short_name' => $validated['names'],
                        'full_name' => $validated['names'],
                        'number' => $validated['dni'],
                        'telephone' => null,
                        'email' => $validated['email'],
                        'is_provider' => false,
                        'is_client' => true,
                        'names' => $validated['names'],
                        'gender' => 'M',
                        'status' => true
                    ]
                );

                if (!$person->email) {
                    $person->email = $validated['email'];
                    $person->save();
                }

                $user = User::create([
                    'name' => $person->names ?: $person->full_name,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password'] ?: $validated['dni']),
                    'person_id' => $person->id
                ]);

                Auth::login($user);

                if (!$user->hasRole('Alumno')) {
                    $role = Role::where('name', 'Alumno')->first();
                    if ($role) {
                        $user->assignRole($role);
                    }
                }
            }

            $student = AcaStudent::firstOrCreate(
                ['person_id' => $person->id],
                ['student_code' => $person->number ?: $person->id, 'new_student' => true]
            );

            $saleNote = $this->createCartSaleNote($onliSale, $person, $validated);
            $onliSale->person_id = $person->id;
            $onliSale->email = $onliSale->email ?: $person->email;
            $onliSale->clie_full_name = $onliSale->clie_full_name ?: $person->full_name;
            $onliSale->nota_sale_id = $saleNote->id;
            $onliSale->save();

            foreach ($onliSale->details as $detail) {
                $item = $detail->item ?: OnliItem::find($detail->onli_item_id);
                if (!$item) {
                    continue;
                }

                AcaCapRegistration::firstOrCreate(
                    ['student_id' => $student->id, 'course_id' => $item->item_id],
                    [
                        'status' => true,
                        'modality_id' => 3,
                        'unlimited' => true,
                        'sale_note_id' => $saleNote->id,
                        'amount_paid' => $detail->price,
                    ]
                );

                $course = AcaCourse::find($item->item_id);
                SaleProduct::create([
                    'sale_id' => $saleNote->id,
                    'product_id' => $item->item_id,
                    'product' => json_encode($course ?: $item),
                    'saleProduct' => json_encode([
                        'onli_item_id' => $item->id,
                        'item_id' => $item->item_id,
                        'name' => $item->name,
                        'price' => (float) $detail->price,
                        'quantity' => (float) $detail->quantity,
                        'student_id' => $student->id,
                    ]),
                    'price' => $detail->price,
                    'discount' => 0,
                    'quantity' => $detail->quantity,
                    'total' => round($detail->price * $detail->quantity, 2),
                    'entity_name_product' => AcaCourse::class
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo finalizar la compra.'], 500);
        }

        try {
            Mail::to($onliSale->email ?: $person->email)
                ->send(new ConfirmPurchaseMail(OnliSale::with('details.item')->where('id', $onliSale->id)->first()));
        } catch (\Throwable $e) {
            $onliSale->email_sent = false;
            $onliSale->save();
        }

        return response()->json([
            'url' => route('web_thanks', $onliSale->id)
        ]);
    }

    private function cartItemsFromIds(array $ids)
    {
        $items = OnliItem::whereIn('id', $ids)->get()->keyBy('id');

        if ($items->count() !== count($ids)) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                response()->json(['error' => 'Uno o mas productos del carrito ya no estan disponibles.'], 422)
            );
        }

        return collect($ids)->map(fn ($id) => $items[(int) $id]);
    }

    private function cartTotal($items): float
    {
        return round($items->sum(fn ($item) => (float) $item->price), 2);
    }

    private function normalizeMercadoPagoPayer(array $payer): ?array
    {
        $email = strtolower(trim($payer['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $payer['email'] = $email;

        return $payer;
    }

    private function createCartSaleNote(OnliSale $onliSale, Person $person, array $data): Sale
    {
        $payments = [[
            'type' => 6,
            'reference' => $onliSale->mercado_payment_id,
            'amount' => (float) $onliSale->total
        ]];

        $invoiceType = $data['invoice_type'] === 'factura' ? 1 : 2;
        $invoiceName = $data['invoice_type'] === 'factura'
            ? ($data['invoice_business_name'] ?? null)
            : ($data['invoice_name'] ?? $person->full_name);
        $invoiceNumber = $data['invoice_type'] === 'factura'
            ? ($data['invoice_ruc'] ?? null)
            : ($data['invoice_dni'] ?? $person->number);

        return Sale::create([
            'sale_date' => Carbon::now()->format('Y-m-d'),
            'user_id' => Auth::id(),
            'client_id' => $person->id,
            'local_id' => 1,
            'total' => $onliSale->total,
            'advancement' => $onliSale->total,
            'total_discount' => 0,
            'payments' => $payments,
            'petty_cash_id' => null,
            'status' => true,
            'physical' => 1,
            'invoice_razon_social' => $invoiceName,
            'invoice_ruc' => $invoiceNumber,
            'invoice_direccion' => $data['invoice_address'] ?? null,
            'invoice_ubigeo' => null,
            'invoice_ubigeo_description' => null,
            'invoice_type' => $invoiceType,
        ]);
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
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));
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
            'dni' => 'required|numeric|unique:people,number',
            'phone' => 'required|string|max:255',
            'email' => 'required|unique:people,email',
            'password' => 'required|string|min:8',
            'password2' => 'required|string|min:8|same:password',
        ], [
            // Mensajes personalizados
            'dni.required' => 'El campo DNI es obligatorio.',
            'dni.numeric' => 'El campo DNI debe ser un número.',
            'dni.unique' => 'El DNI ingresado ya está registrado.',
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


        //dd($request->all());

        $productids = $request->get('item_id');

        $comprador_nombre = $request->get('names');
        $comprador_telefono = $request->get('phone');
        $comprador_email = $request->get('email');

        $preference_id = null;

        try {
            DB::beginTransaction();
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));
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
                $total = $sale->details->sum(function ($detail) {
                    return (float) $detail->price * (float) $detail->quantity;
                });

                if ((float) $sale->total <= 0 && $total > 0) {
                    $sale->total = round($total, 2);
                    $sale->save();
                }
            } else {
                // Si no se encuentra la venta, inicializar cursos como una colección vacía
                $courses = collect();
                $total = 0;
            }

            return view('pages.thanks', [
                'sale' => $sale,
                'courses' => $courses,
                'total' => round($total, 2),
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
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));

        $client = new PaymentClient();
        $sale = OnliSale::find($id);

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

                    $sale->delete();
                    return response()->json([
                        'status' => $payment->status,
                        'message' => $payment->status_detail,
                        'url' => route('web_pagar')
                    ]);
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
        $ubigeo = null;
        if($request->ubigeo != null){
            $ubigeo = $request->ubigeo."0101";
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
                'ubigeo' => $ubigeo ?? null,
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

            $coursesInterest = $request->courseInterest ?? [];

            if(count($coursesInterest) > 0){
                foreach($coursesInterest as $course){
                    AcaStudentCoursesInterest::create([
                        'student_id' => $student->id,
                        'course_id' => $course,
                        'status' => 0
                    ]);
                }
            }


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
            return redirect()->back()->with('success', 'Registro completado exitosamente, Revisa tu correo donde recibirás mas información.');

        } catch (\Throwable $th) {
             // 5. REVERSIÓN (ROLLBACK) si algo falla
             DB::rollBack();
             dd($th);
            return redirect()->back()->with('fail', 'Registro fallido Reintentar.');
        }

    }
}
