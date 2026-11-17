<?php

namespace App\Http\Controllers;

use App\Events\BlogUserRegistered;
use App\Http\Requests\BlogRegistrationRequest;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Alta de lectores desde el blog.
 *
 * Crea la ficha de persona, la cuenta de usuario con telefono y pais, le
 * asigna el rol por defecto (Alumno) y deja que el evento BlogUserRegistered
 * envie el alta a Integrationhub.
 */
class BlogRegistrationController extends Controller
{
    public function store(BlogRegistrationRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        try {
            $user = DB::transaction(function () use ($data) {
                // Si la persona ya existe (por ejemplo, compro un curso antes),
                // se reutiliza su ficha y solo se completan telefono y pais.
                $person = Person::where('email', $data['email'])->first();

                $personData = [
                    'short_name' => $data['name'],
                    'full_name' => $data['name'],
                    'names' => $data['name'],
                    'telephone' => $data['telefono'],
                    'email' => $data['email'],
                    'country_id' => $data['country_id'],
                    'is_provider' => false,
                    'is_client' => true,
                    'status' => true,
                ];

                if ($person) {
                    $person->fill($personData)->save();
                } else {
                    // El blog no pide documento: se usa el tipo "Doc.trib.no.dom.sin.ruc"
                    // (0) y un numero interno unico para no chocar con DNI reales.
                    $personData['document_type_id'] = '0';
                    $personData['number'] = $this->numeroInternoUnico();
                    $person = Person::create($personData);
                }

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'person_id' => $person->id,
                    'telefono' => $data['telefono'],
                    'country_id' => $data['country_id'],
                    'status' => true,
                ]);

                $role = Role::where('name', 'Alumno')->first();

                if ($role) {
                    $user->assignRole($role);
                }

                return $user;
            });
        } catch (\Throwable $e) {
            Log::error('Registro del blog: no se pudo crear la cuenta: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            $mensaje = 'No pudimos crear tu cuenta. Intenta nuevamente en unos minutos.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $mensaje], 500);
            }

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', $mensaje);
        }

        Auth::login($user);

        // Fuera de la transaccion: la cuenta ya esta guardada y el envio a
        // Integrationhub no puede revertirla ni hacerla fallar.
        event(new BlogUserRegistered($user));

        $mensaje = 'Tu cuenta fue creada correctamente. Ya puedes descargar el material del articulo.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'redirect' => url()->previous(),
            ]);
        }

        return redirect()->back()->with('success', $mensaje);
    }

    /**
     * Numero interno unico para la ficha de persona creada desde el blog.
     * No reemplaza a un documento de identidad: solo cubre la columna
     * obligatoria y unica de people.number.
     */
    private function numeroInternoUnico(): string
    {
        do {
            $numero = 'WEB-'.Str::upper(Str::random(10));
        } while (Person::where('number', $numero)->exists());

        return $numero;
    }
}
