<?php

namespace App\Http\Middleware;

use App\Support\ModoAlumno;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfInvalidUserData
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo el campus del alumno exige completar los datos personales. Un
        // administrador que además es alumno entra al panel y no queda atrapado
        // en la pantalla de actualización de perfil.
        if (ModoAlumno::activo(Auth::user()) && ! Auth::user()->updated_information) {
            return redirect()->route('user-update-profile'); // Cambia 'update-profile' por la ruta que desees
        }
        return $next($request);
    }
}
