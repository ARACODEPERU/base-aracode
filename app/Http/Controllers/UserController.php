<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\LocalSale;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use DataTables;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Users/List');
    }

    public function getUsers()
    {
        $users = (new User())->newQuery();
        $users = $users->leftJoin('people', 'people.id', 'users.person_id');
        $users = $users->select(
            'users.*',
            'people.full_name',
            'people.image',
            'people.number'
        );

        return DataTables::of($users)->toJson();
    }
    public function create()
    {
        return Inertia::render('Users/Create', [
            'establishments' => LocalSale::all(),
            'roles' => Role::all()
        ]);
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            //'local_id' => 'required|unique:users,local_id',
            'local_id' => 'required',
            'name' => 'required',
            'email' => 'required|string|max:255|unique:users,email',
            'password' => 'required|string'
        ]);

        $user = User::create([
            'name'          => trim($request->get('name')),
            'email'         => trim($request->get('email')),
            'password'      => Hash::make($request->get('password')),
            'local_id'      => $request->get('local_id')
        ]);

        $user->assignRole($request->get('role'));

        $token = $user->createToken($request->get('email'))->plainTextToken;

        $user->api_token = $token;
        $user->save();

        return redirect()->route('users.index')
            ->with('message', __('Usuario creado con éxito'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')
            ->with('message', __('Usuario eliminado con éxito'));
    }

    public function edit(User $user)
    {
        $person = Person::find($user->person_id);

        $identityDocumentTypes = DB::table('identity_document_type')->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                'districts.name AS district_name',
                'provinces.name AS province_name',
                'departments.name AS department_name'
            )
            ->get();

        return Inertia::render('Users/Edit', [
            'establishments' => LocalSale::all(),
            'xuser' => $user,
            'xrole' => $user->getRoleNames(),
            'roles' => Role::all(),
            'person' => $person,
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo'       => $ubigeo
        ]);
    }
    public function update(Request $request, User $user)
    {
        if ($user->hasAnyRole(['Vendedor'])) {

            $this->validate($request, [
                'local_id' => 'required'
            ]);

            $user->local_id = $request->get('local_id');
        }

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|string|max:255|unique:users,email,' . $user->id,
        ]);


        $user->syncRoles([]);

        $user->name = trim($request->get('name'));
        $user->email = trim($request->get('email'));

        if ($request->get('password')) {
            $user->password = Hash::make($request->get('password'));
        }

        $user->assignRole($request->get('role'));

        $user->save();

        return redirect()->route('users.edit', $user->id)
            ->with('message', __('Usuario modificado con éxito'));
    }
}
