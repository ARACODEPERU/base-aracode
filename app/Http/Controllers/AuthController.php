<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
