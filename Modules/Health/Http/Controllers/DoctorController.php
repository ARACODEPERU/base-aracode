<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Health\Entities\HealDoctor;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DoctorController extends Controller
{
    use ValidatesRequests;

    private const ATTENTION_SERVICE_TYPES = [
        'general',
        'medicina_general',
        'medicina_interna',
        'pediatria',
        'ginecologia',
        'cardiologia',
        'dermatologia',
        'traumatologia',
        'neurologia',
        'oftalmologia',
        'otorrinolaringologia',
        'gastroenterologia',
        'endocrinologia',
        'urologia',
        'psicologia',
        'nutricion',
        'dental',
        'odontologia_general',
        'ortodoncia',
        'endodoncia',
        'periodoncia',
        'rehabilitacion_oral',
        'cirugia_bucal',
        'odontopediatria',
        'implantologia',
    ];
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doctors = (new HealDoctor())->newQuery();
        $doctors = $doctors->join('people', 'heal_doctors.person_id', 'people.id')
            ->select(
                'heal_doctors.id',
                'heal_doctors.person_id',
                'heal_doctors.doctor_code',
                'heal_doctors.colegiatura',
                'heal_doctors.profession',
                'heal_doctors.specialty',
                'heal_doctors.attention_service_type',
                'people.document_type_id',
                'people.full_name',
                'people.number',
                'people.telephone',
                'people.email',
                'people.address',
                'people.birthdate',
                'heal_doctors.created_at',
                'people.image',
                'people.gender'
            );
        if (request()->has('search')) {
            $doctors->where('people.full_name', 'Like', '%' . request()->input('search') . '%');
        }

        if (request()->query('sort')) {
            $attribute = request()->query('sort');
            $sort_order = 'ASC';
            if (strncmp($attribute, '-', 1) === 0) {
                $sort_order = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $doctors->orderBy($attribute, $sort_order);
        } else {
            $doctors->latest();
        }

        $doctors = $doctors->paginate(10)->onEachSide(2);

        return Inertia::render('Health::Doctors/CardList', [
            'doctors' => $doctors,
            'filters' => request()->all('search')
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
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

        return Inertia::render('Health::Doctors/Create', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo'       => $ubigeo,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $update_id = null;

        $this->validate(

            $request,
            [
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'number'            => 'unique:people,number,' . $update_id . ',id,document_type_id,' . $request->get('document_type_id'),
                'telephone'         => 'required|max:12',
                'email'             => 'required|max:255|unique:people,email',
                'address'           => 'required|max:255',
                'ubigeo'            => 'required|max:255',
                'birthdate'         => 'required|',
                'names'             => 'required|max:255',
                'father_lastname'   => 'required|max:255',
                'mother_lastname'   => 'required|max:255',
                'colegiatura'        => 'nullable|string|max:80',
                'profession'         => 'required|max:120',
                'specialty'          => 'nullable|max:120',
                'attention_service_type' => 'required|in:' . implode(',', self::ATTENTION_SERVICE_TYPES),
                'generate_user'      => 'nullable|boolean',
                'user_email'         => $request->boolean('generate_user') ? 'required|email|max:255|unique:users,email' : 'nullable|email|max:255',
                'user_password'      => $request->boolean('generate_user') ? 'required|string|min:4' : 'nullable|string|min:4',
            ]
        );

        // $path = 'img' . DIRECTORY_SEPARATOR . 'imagen-no-disponible.jpeg';
        // $destination = 'uploads' . DIRECTORY_SEPARATOR . 'products';
        $path = $this->storeProfileImage($request, 'uploads/doctores');

        DB::transaction(function () use ($request, $path) {
            $per = Person::create([
                'document_type_id'      => $request->get('document_type_id'),
                'short_name'            => $request->get('names'),
                'full_name'             => $request->get('father_lastname') . ' ' .  $request->get('mother_lastname') . ' ' . $request->get('names'),
                'description'           => 'Doctor',
                'number'                => $request->get('number'),
                'telephone'             => $request->get('telephone'),
                'email'                 => $request->get('email'),
                'image'                 => $path,
                'address'               => $request->get('address'),
                'is_provider'           => false,
                'is_client'             => false,
                'ubigeo'                => $request->get('ubigeo'),
                'birthdate'             => $request->get('birthdate'),
                'names'                 => $request->get('names'),
                'father_lastname'       => $request->get('father_lastname'),
                'mother_lastname'       => $request->get('mother_lastname'),
                'gender'                => $request->get('gender')
            ]);

            $user = $request->boolean('generate_user') ? $this->createDoctorUser($per, $request) : null;

            HealDoctor::create([
                'person_id'     => $per->id,
                'user_id'       => $user?->id,
                'doctor_code'  => $request->get('number'),
                'colegiatura' => $request->get('colegiatura'),
                'profession' => $request->get('profession'),
                'specialty' => $request->get('specialty'),
                'attention_service_type' => $this->normalizeServiceType($request->get('attention_service_type', 'general')),
            ]);
        });

        return redirect()->route('heal_doctors_create')
            ->with('message', __('Doctor creado con éxito'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('health::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
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

        $doctor = Person::leftJoin('districts', 'ubigeo', 'districts.id')
            ->leftJoin('provinces', 'districts.province_id', 'provinces.id')
            ->leftJoin('departments', 'provinces.department_id', 'departments.id')
            ->leftJoin('heal_doctors', 'heal_doctors.person_id', 'people.id')
            ->select(
                'people.*',
                'heal_doctors.user_id',
                'heal_doctors.colegiatura',
                'heal_doctors.profession',
                'heal_doctors.specialty',
                'heal_doctors.attention_service_type',
                DB::raw('CONCAT(departments.name,"-",provinces.name,"-",districts.name) AS city'),

            )
            ->where('people.id', $id)
            ->first();

        return Inertia::render('Health::Doctors/Edit', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo'                => $ubigeo,
            'doctor'               => $doctor
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $update_id = $request->get('id');

        $this->validate(

            $request,
            [
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'number'            => 'unique:people,number,' . $update_id . ',id,document_type_id,' . $request->get('document_type_id'),
                'telephone'         => 'required|max:12',
                'email'             => 'required|max:255|unique:people,email,' . $update_id . ',id',
                'address'           => 'required|max:255',
                'ubigeo'            => 'required|max:255',
                'birthdate'         => 'required|',
                'names'             => 'required|max:255',
                'father_lastname'   => 'required|max:255',
                'mother_lastname'   => 'required|max:255',
                'colegiatura'        => 'nullable|string|max:80',
                'profession'         => 'required|max:120',
                'specialty'          => 'nullable|max:120',
                'attention_service_type' => 'required|in:' . implode(',', self::ATTENTION_SERVICE_TYPES),
                'generate_user'      => 'nullable|boolean',
                'user_email'         => $request->boolean('generate_user') ? 'required|email|max:255|unique:users,email' : 'nullable|email|max:255',
                'user_password'      => $request->boolean('generate_user') ? 'required|string|min:4' : 'nullable|string|min:4',
            ]
        );

        // $path = 'img' . DIRECTORY_SEPARATOR . 'imagen-no-disponible.jpeg';
        // $destination = 'uploads' . DIRECTORY_SEPARATOR . 'products';

        $person = Person::find($update_id);

        $person->document_type_id   = $request->get('document_type_id');
        $person->short_name         = $request->get('names');
        $person->full_name          = $request->get('father_lastname') . ' ' .  $request->get('mother_lastname') . ' ' . $request->get('names');
        $person->number             = $request->get('number');
        $person->telephone          = $request->get('telephone');
        $person->email              = $request->get('email');
        $person->address            = $request->get('address');
        $person->ubigeo             = $request->get('ubigeo');
        $person->birthdate          = $request->get('birthdate');
        $person->names              = $request->get('names');
        $person->father_lastname    = $request->get('father_lastname');
        $person->mother_lastname    = $request->get('mother_lastname');
        $person->gender             = $request->get('gender');

        $path = $this->storeProfileImage($request, 'uploads/doctores');
        if ($path) {
            $person->image = $path;
        }

        DB::transaction(function () use ($request, $person, $update_id) {
            $person->save();

            $doctor = HealDoctor::where('person_id', $update_id)->firstOrFail();
            $userId = $doctor->user_id;

            if (!$userId && $request->boolean('generate_user')) {
                $userId = $this->createDoctorUser($person, $request)->id;
            }

            $doctor->update([
                'user_id' => $userId,
                'doctor_code'  => $request->get('number'),
                'colegiatura' => $request->get('colegiatura'),
                'profession' => $request->get('profession'),
                'specialty' => $request->get('specialty'),
                'attention_service_type' => $this->normalizeServiceType($request->get('attention_service_type', 'general')),
            ]);
        });

        return redirect()->route('heal_doctors_edit', $update_id)
            ->with('message', __('Doctor actualizado con éxito'));
    }

    private function createDoctorUser(Person $person, Request $request): User
    {
        $user = User::create([
            'name' => trim($person->full_name),
            'email' => trim($request->get('user_email')),
            'password' => Hash::make($request->get('user_password')),
            'person_id' => $person->id,
            'status' => true,
        ]);

        $doctorRole = Role::firstOrCreate(['name' => 'Doctor']);
        $permissions = [
            'heal_dashboard',
            'heal_pacientes_listado',
            'heal_atenciones_listado',
            'heal_atenciones_nuevo',
            'heal_atenciones_editar',
            'heal_citas_listado',
            'heal_citas_nuevo',
            'heal_citas_editar',
            'heal_odontology',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            if (!$doctorRole->hasPermissionTo($permissionName)) {
                $doctorRole->givePermissionTo($permission);
            }
        }

        $user->assignRole($doctorRole);

        return $user;
    }

    private function storeProfileImage(Request $request, string $destination): ?string
    {
        $image = $request->get('image');

        if (is_string($image) && str_starts_with($image, 'data:image')) {
            [$meta, $content] = explode(',', $image, 2);
            preg_match('/data:image\/(?<extension>[^;]+);base64/', $meta, $matches);
            $extension = $matches['extension'] ?? 'png';
            $extension = $extension === 'jpeg' ? 'jpg' : $extension;
            $fileName = date('YmdHis') . '_' . Str::random(8) . '.' . $extension;
            $path = $destination . '/' . $fileName;

            Storage::disk('public')->put($path, base64_decode($content));

            return $path;
        }

        $file = $request->file('image');
        if (!$file) {
            return null;
        }

        $extension = $file->getClientOriginalExtension();
        $fileName = date('YmdHis') . '_' . Str::random(8) . '.' . $extension;

        return $file->storeAs($destination, $fileName, 'public');
    }

    private function normalizeServiceType(?string $serviceType): string
    {
        return $serviceType === 'dental' ? 'odontologia_general' : ($serviceType ?: 'general');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
