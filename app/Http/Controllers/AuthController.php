<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Socialevents\Services\SportsOrganizationService;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user,
                // La app móvil necesita los roles para mostrar las opciones de administrador
                'roles' => $user->getRoleNames()->values()->all(),
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    /**
     * Datos del usuario autenticado, incluyendo sus roles.
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'message' => 'Usuario obtenido correctamente',
            'user' => $user,
            'roles' => $user->getRoleNames()->values()->all(),
        ]);
    }

    /**
     * Sesión completa para la app móvil: usuario, roles, organización y vigencia.
     *
     * La organización se deriva del token; la app nunca la envía. `access` es la
     * única fuente de verdad para habilitar o bloquear escrituras en el cliente.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $organizations = app(SportsOrganizationService::class);

        return response()->json([
            'success' => true,
            'message' => 'Sesión obtenida correctamente',
            'user' => $user,
            'roles' => $user->getRoleNames()->values()->all(),
            'organization' => $organizations->organizationSummary($user),
            'access' => $organizations->accessSummary($user),
            'features' => [
                'sports_mode' => $organizations->sportsMode(),
            ],
        ]);
    }
}
