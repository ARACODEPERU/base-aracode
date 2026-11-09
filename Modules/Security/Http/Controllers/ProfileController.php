<?php

namespace Modules\Security\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Country;
use App\Models\District;
use App\Models\Person;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $person = Person::find(Auth::user()->person_id);
        $document_types = DB::table('identity_document_type')->get();
        // CONCAT(...) es SQL exclusivo de MySQL; se traen los nombres por
        // separado y se concatena en PHP para que la consulta funcione en
        // cualquier motor (la suite de tests corre sobre sqlite).
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                'departments.name AS department_name',
                'provinces.name AS province_name',
                'districts.name AS district_name'
            )
            ->get()
            ->map(fn ($row) => [
                'district_id' => $row->district_id,
                'name_city' => $row->department_name . '-' . $row->province_name . '-' . $row->district_name,
            ]) ->values();

        $countries = Country::where('status', true)->orderBy('description')->get();

        return Inertia::render('Security::Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'person' => $person,
            'document_types' => $document_types,
            'ubigeo' => $ubigeo,
            'countries' => $countries
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {

        $path = null;
        $destination = 'users/avatar';
        $file = $request->file('avatar');
        $file_name = null;

        $person = Person::find($request->user()->person_id);

        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $original_name = str_replace(" ", "_", $original_name);
            $extension = $file->getClientOriginalExtension();
            $file_name = date('YmdHis') . '.' . $extension;
            $path = $request->file('avatar')->storeAs(
                $destination,
                $file_name,
                'public'
            );

            if ($person) {
                $person->image = $path;
                $person->save();
            }
        }
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }
        if ($file_name != null) $request->user()->avatar = $path;

        $request->user()->information = $request->get('information');



        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Muestra la configuración del asistente Aracodito.
     */
    public function aracodito(): Response
    {
        return Inertia::render('Security::Profile/Aracodito');
    }

    /**
     * Guarda la configuración del asistente Aracodito del usuario autenticado.
     */
    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'aracodito_enabled' => ['sometimes', 'boolean'],
            'aracodito_audio_enabled' => ['sometimes', 'boolean'],
            'aracodito_ia_enabled' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        // boolean() normaliza "1", "on", "true", 1 y true, y devuelve false
        // cuando el interruptor no viene en la petición (valor apagado).
        $user->aracodito_enabled = $request->boolean('aracodito_enabled');
        $user->aracodito_audio_enabled = $request->boolean('aracodito_audio_enabled');
        $user->aracodito_ia_enabled = $request->boolean('aracodito_ia_enabled');

        $user->save();

        return Redirect::route('profile.aracodito')->with('status', 'aracodito-settings-updated');
    }


    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
