<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Mail;
use Modules\Socialevents\Services\SportsOrganizationService;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        // Con PW00001 = 2 el organizador ve la pantalla con la piel de
        // Pichanguero; el resto de modos conserva la pantalla general.
        $componente = app(SportsOrganizationService::class)->sportsMode()
            ? 'Auth/ForgotPasswordOrganizer'
            : 'Auth/ForgotPassword';

        return Inertia::render($componente, [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {

        $request->validate([
            'email' => 'required|email',
        ]);

        // Busca al usuario
        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => [trans('No se encontró un usuario con este correo.')],
            ]);
        }

        // Genera un token de restablecimiento


        // Solo el flujo de recuperación de organizadores con PW00001 = 2 usa la
        // identidad de Pichanguero. El resto de flujos y modos conserva el
        // correo genérico.
        $vista = app(SportsOrganizationService::class)->sportsMode()
            ? 'emails.reset_password_pichanguero'
            : null;

        Mail::to($user->email)->queue(new ResetPassword($user, $vista));

        return back()->with('status', __('Se ha enviado el enlace de restablecimiento de contraseña.'));
    }
}
