<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Modules\Onlineshop\Entities\OnliItem;

use Intervention\Image\Facades\Image;
use Intervention\Image\Font;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\CMS\Entities\CmsSection;
use Modules\CMS\Entities\CmsSectionItem;
use Modules\Sales\Entities\SaleProductCategory;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

class LandingController extends Controller
{
    public function index()
    {
        $header = CmsSection::with(['items' => function ($query) {
            $query->orderBy('position');
        }, 'items.item'])
            ->where('component_id', 'encabezado_2')
            ->first();

        $welcome = CmsSection::with(['items' => function ($query) {
            $query->orderBy('position');
        }, 'items.item'])
            ->where('component_id', 'bienvenida_3')
            ->first();

        return Inertia::render('Landing/Home', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'dataHome' => [
                'welcome' => $welcome,
                'header' => $header
            ],
            'pageActive' => 'home'
        ]);
    }

    public function biller()
    {
        $header = CmsSection::with(['items' => function ($query) {
            $query->orderBy('position');
        }, 'items.item'])
            ->where('component_id', 'encabezado_2')
            ->first();

        return Inertia::render('Landing/Biller', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'dataHome' => [
                'header' => $header
            ],
            'pageActive' => 'home'
        ]);
    }

    public function blog()
    {
        return Inertia::render('Landing/Blog');
    }

    public function computerStore()
    {
        $categories = OnliItem::select('category_description')->groupBy('category_description')->get();
        $products = OnliItem::where('status', true)->inRandomOrder()->get();

        $promotions = CmsSection::where('component_id', 'carrusel_productos_2')
            ->with('items.item.items')
            ->first();
        //dd($promotions);
        return Inertia::render('Landing/ComputerStore', [
            'pageActive' => 'store',
            'categories' => $categories,
            'products' => $products,
            'promotions' => $promotions
        ]);
    }

    public function redirectToWhatsApp($id)
    {
        $product = OnliItem::find($id);
        $telefono = '51921008708';
        $mensaje = 'Hola, estoy interesado en el producto: ' . $product->name . ' Precio: ' . $product->price;

        $urlWhatsApp = 'https://api.whatsapp.com/send/?phone=' . $telefono . '&text=' . urlencode($mensaje) . '&type=phone_number&app_absent=0';

        return redirect()->away($urlWhatsApp);
    }

    public function terms()
    {
        return Inertia::render('Landing/TermsConditions');
    }

    public function academicPrices()
    {
        $subscriptions  = AcaSubscriptionType::orderBy('order_number')->get();
        //dd($subscriptions);
        return Inertia::render('Landing/Academic/CoursesSubscriptions', [
            'subscriptions' => $subscriptions
        ]);
    }

    public function academiCreatePayment($id)
    {
        if (Auth::check()) {
            // Si el usuario está autenticado, lo redirige a la ruta `academic_step_verification`
            return redirect()->route('academic_step_verification', $id);
        }

        // Si no está autenticado, muestra la vista actual con la suscripción
        return Inertia::render('Landing/Academic/StepsPayLogin', [
            'subscription' => AcaSubscriptionType::find($id)
        ]);
    }
}
