<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSubscriptionType;

class AcaAuthController extends Controller
{
    use ValidatesRequests;

    public function login(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]
        );

        $credentials = [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->route('academic_step_verification', $id);
        }
        return redirect()->route('academic_step_account', $id);
    }

    public function create(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'names' => ['required', 'max:255'],
                'apps' => ['required', 'max:255'],
                'apms' => ['required', 'max:255'],
                'numberdni' => ['required', 'max:8'],
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required'],
            ]
        );

        $documentType = 1; // fijo en tu ejemplo
        $number = $request->get('numberdni');
        $email = $request->get('email');

        // Buscar si ya existe la persona
        $person = Person::where('document_type_id', $documentType)
            ->where('number', $number)
            ->first();

        if ($person) {
            // Verificar si ya tiene usuario
            $user = User::where('person_id', $person->id)->first();

            if ($user) {
                if ($user->email === $email) {
                    // Caso 1: misma persona y mismo email -> iniciar sesión
                    Auth::login($user);
                    $request->session()->regenerate();
                    return redirect()->route('academic_step_verification', $id);
                } else {
                    // Caso 2: misma persona pero email distinto -> error para useForm
                    throw ValidationException::withMessages([
                        'email' => 'Esta persona ya está registrada con otro correo electrónico.',
                    ]);
                }
            } else {
                // Caso raro: persona existe pero sin usuario -> crear usuario
                $user = User::create([
                    'name' => $request->get('names'),
                    'email' => $email,
                    'password' => Hash::make($request->get('password')),
                    'person_id' => $person->id,
                    'local_id' => 1,
                    'tour_completed' => false
                ]);

                Auth::login($user);
                $request->session()->regenerate();
                return redirect()->route('academic_step_verification', $id);
            }
        } else {
            // Caso 3: persona nueva -> crear persona y usuario
            $person = Person::create([
                'document_type_id' => $documentType,
                'number' => $number,
                'short_name' => $request->get('names'),
                'full_name' => $request->get('apps') . ' ' . $request->get('apms') . ' ' . $request->get('names'),
                'father_lastname' => $request->get('apps'),
                'mother_lastname' => $request->get('apms'),
                'gender' => $request->get('sexo'),
                'status' => true,
            ]);

            $user = User::create([
                'name' => $request->get('names'),
                'email' => $email,
                'password' => Hash::make($request->get('password')),
                'person_id' => $person->id,
                'local_id' => 1,
                'tour_completed' => false
            ]);

            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->route('academic_step_verification', $id);
        }
    }

    public function userVerification($id)
    {
        $personInvoice = Person::find(Auth::user()->person_id);

        return Inertia::render('Landing/Academic/StepsPayVerification', [
            'subscription' => AcaSubscriptionType::find($id),
            'personInvoice' => $personInvoice
        ]);
    }
}
