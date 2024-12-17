<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Onlineshop\Entities\OnliItem;

use Intervention\Image\Facades\Image;
use Intervention\Image\Font;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Modules\CMS\Entities\CmsSection;
use Modules\CMS\Entities\CmsSectionItem;
use Modules\Sales\Entities\SaleProductCategory;

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
}
