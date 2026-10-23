<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Support\RegistroVariantes;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Socialevents\Services\SportsOrganizationService;

class RegisteredUserController extends Controller
{
    /**
     * Formulario de registro. La pantalla la decide la portada publicada
     * (config/registro.php); la lógica de alta es la misma para todas.
     */
    public function create(): Response
    {
        if (! RegistroVariantes::habilitado()) {
            abort(404);
        }

        return Inertia::render(RegistroVariantes::componente());
    }

    /**
     * Crea la organización y el usuario organizador con su prueba gratuita.
     *
     * Si el correo o el número de identidad ya tienen cuenta, no se crea nada:
     * se redirige al login con una alerta.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! RegistroVariantes::habilitado()) {
            abort(404);
        }

        $organizationService = app(SportsOrganizationService::class);

        $validated = $request->validate([
            'organization_name' => 'required|string|max:180',
            'organization_type' => 'required|in:empresa,persona_natural',
            'ruc' => 'nullable|digits:11',
            'first_name' => 'required|string|max:120',
            'apellido_paterno' => 'required|string|max:120',
            'apellido_materno' => 'required|string|max:120',
            'telefono' => 'required|string|max:20',
            'tipo_identidad' => 'required|in:DNI,CE,PASAPORTE',
            'numero_identidad' => 'required|string|max:20',
            'sexo' => 'nullable|in:M,F',
            'direccion' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // La cuenta se crea con datos personales: sin aceptar los terminos y
            // la politica de privacidad no se registra nada.
            'acepto' => ['required', 'accepted'],
        ], [
            'acepto.required' => 'Debes aceptar los Términos y Condiciones y la Política de Privacidad para crear tu cuenta.',
            'acepto.accepted' => 'Debes aceptar los Términos y Condiciones y la Política de Privacidad para crear tu cuenta.',
        ]);

        $email = strtolower(trim($validated['email']));
        $identity = trim($validated['numero_identidad']);

        // Duplicados: la alerta y el login sustituyen al error de campo, para
        // que la persona entienda que ya tiene cuenta y pueda iniciar sesión.
        $emailTaken = User::whereRaw('LOWER(email) = ?', [$email])->exists();
        $identityTaken = User::where('numero_identidad', $identity)->exists();

        if ($emailTaken || $identityTaken) {
            return redirect()->route('login')->with(
                'error',
                $emailTaken
                    ? 'Este correo ya está registrado. Inicia sesión para continuar.'
                    : 'Este número de identidad ya está registrado. Inicia sesión para continuar.'
            );
        }

        $user = DB::transaction(function () use ($validated, $email, $identity, $organizationService) {
            $user = User::create([
                'name' => $validated['first_name'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'],
                'telefono' => $validated['telefono'],
                'tipo_identidad' => $validated['tipo_identidad'],
                'numero_identidad' => $identity,
                'sexo' => $validated['sexo'] ?? null,
                'direccion' => $validated['direccion'] ?? null,
                'email' => $email,
                'password' => Hash::make($validated['password']),
            ]);

            $organizationService->createOrganizationForUser($user, [
                'nombre' => $validated['organization_name'],
                'tipo' => $validated['organization_type'],
                'ruc' => $validated['ruc'] ?? null,
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        // El alta crea al organizador y lo deja en su panel deportivo; si el
        // modo cambiara, cae en el dashboard general.
        return redirect(
            $organizationService->organizerHome($user) ?? RouteServiceProvider::HOME
        );
    }
}
