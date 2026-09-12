<?php

namespace App\Http\Controllers;

use App\Services\JobOffersAccess;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class JobOffersController extends Controller
{
    /**
     * Muestra el iframe configurable de Ofertas Laborales.
     *
     * Solo accesible para alumnos con un curso de pago o una suscripcion activa
     * y vigente (la misma condicion que el enlace del sidebar).
     */
    public function index()
    {
        if (!JobOffersAccess::canView(Auth::user())) {
            return redirect()->route('web_subscriptions')
                ->with('message', 'Las ofertas laborales están disponibles con un curso de pago o una suscripción activa.');
        }

        return Inertia::render('JobOffers/Index', [
            'iframeCode' => JobOffersAccess::iframeCode(),
        ]);
    }
}
