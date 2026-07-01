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
use App\Support\ActiveModulesResolver;
use Illuminate\Support\Str;

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
            'activeModules' => ActiveModulesResolver::forLogin(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();
        if ($user->hasRole('Lector') && $user->roles()->count() === 1) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => __('No tienes permiso para acceder a este panel. Usa el acceso de Biblioteca.'),
            ]);
        }

        $request->session()->regenerate();

        $redirectTo = $this->resolveSafeRedirectTo($request->input('redirect_to'));
        if ($redirectTo) {
            return redirect()->to($redirectTo);
        }

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

    private function resolveSafeRedirectTo(?string $redirectTo): ?string
    {
        if (!is_string($redirectTo)) {
            return null;
        }

        $redirectTo = trim($redirectTo);
        if ($redirectTo === '' || Str::startsWith($redirectTo, ['//', 'http://', 'https://'])) {
            return null;
        }

        if (!Str::startsWith($redirectTo, '/')) {
            return null;
        }

        $path = parse_url($redirectTo, PHP_URL_PATH) ?: '/';
        if (Str::startsWith($path, [
            '/login',
            '/logout',
            '/forgot-password',
            '/reset-password',
            '/verify-email',
        ])) {
            return null;
        }

        return $redirectTo;
    }
}
