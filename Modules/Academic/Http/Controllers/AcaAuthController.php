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
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]
        );

        $person = Person::firstOrCreate(
            [
                'document_type_id' => 1,
                'email' => $request->get('email'),
                'number' => $request->get('numberdni'),
            ],
            [

                'short_name' => $request->get('names'),
                'full_name' => $request->get('apps') . ' ' . $request->get('apms') . ' ' . $request->get('names'),
                'father_lastname' => $request->get('apps'),
                'mother_lastname' => $request->get('apms'),
                'gender' => $request->get('sexo'),
                'status' => true,
            ]
        );
        // Crear el usuario
        if ($person->id) {
            $user = User::create([
                'name' => $request->get('names'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')), // Encriptar la contraseña
                'person_id' => $person->id,
                'local_id' => 1,
            ]);

            // Loguear al usuario
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->route('academic_step_verification', $id);
        } else {
            return redirect()->route('academic_step_account', $id);
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
