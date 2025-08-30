<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Company;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $socialNetworks=null;
        try {
            $company = Company::first(); // O cualquier otro método para obtener el modelo
            $socialNetworksJson = $company->social_networks;

            // Decodificar la cadena JSON a un array de PHP
            $socialNetworks = json_decode($socialNetworksJson, true);

            // Verificar que la decodificación fue exitosa y es un array
            if (is_array($socialNetworks)) {
                $socialLinks = [];
                foreach ($socialNetworks as $network) {
                    if (isset($network['id']) && isset($network['route'])) {
                        $socialLinks[] = [
                            'name' => $network['id'],
                            'url' => $network['route'],
                        ];
                    }
                }
            }
        } catch (\Throwable $th) {
        }


        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'socialNetworks' => $socialNetworks,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
