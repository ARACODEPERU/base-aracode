<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\Country;
use App\Models\District;
use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaTeacher;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Modules\Academic\Entities\AcaTeachingResume;
use Illuminate\Support\Facades\Hash;

class AcaTeacherController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $teachers = (new AcaTeacher())->newQuery();
        $teachers = $teachers->with('person.country');

        if (request()->filled('search')) {
            $search = request()->input('search');

            $teachers->whereHas('person', function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%');
            });
        }

        if (request()->query('sort')) {
            $attribute = request()->query('sort');
            $sort_order = 'ASC';
            if (strncmp($attribute, '-', 1) === 0) {
                $sort_order = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $teachers->orderBy($attribute, $sort_order);
        } else {
            $teachers->latest();
        }

        $teachers = $teachers->paginate(12)->onEachSide(2);

        return Inertia::render('Academic::Teachers/List', [
            'teachers' => $teachers,
            'filters' => request()->all('search')
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $identityDocumentTypes = DB::table('identity_document_type')->get();
        $countries = Country::orderBy('description')->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();
        $professions = DB::table('professions')
            ->orderBy('id')
            ->get();
        $occupations = DB::table('occupations')
            ->orderBy('id')
            ->get();

        return Inertia::render('Academic::Teachers/Create', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo'        => $ubigeo,
            'countries'     => $countries,
            'professions'   => $professions,
            'occupations'   => $occupations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $update_id = $request->get('id');
        $user = User::where('person_id', $request->get('id'))->first();

        $this->validate(
            $request,
            [
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'number'            => 'unique:people,number,' . $update_id . ',id,document_type_id,' . $request->get('document_type_id'),
                'telephone'         => 'required|max:12',
                'email'             => 'required|email|max:255',
                'email'             => 'regex:/^[\w\.-]+@[\w\.-]+\.\w+$/|max:255',
                'email'             => 'unique:people,email,' . $update_id . ',id',
                'email'             => 'unique:users,email,' . ($user ? $user->id : null) . ',id',
                'address'           => 'required|max:255',
                'birthdate'         => 'required|',
                'names'             => 'required|max:255',
                'father_lastname'   => 'required|max:255',
                'mother_lastname'   => 'required|max:255',
                'ubigeo_description'=> 'required|max:255'
            ],
            [
                'email.unique' => 'El email ya esta en uso en usuario o en persona',
                'ubigeo_description.required' => 'La ciudad es nesesaria',
                'ubigeo_description.max' => 'Excede el numero de caracteres permitidos'
            ]
        );

        $country_id = $request->get('country_id') ?? 1;

        if($country_id == 1){
            $this->validate(
                $request,
                [
                    'ubigeo' => 'required|max:255',
                ],
                [
                    'ubigeo.required' => 'La ciudad es nesesaria'
                ]
            );
        }

        $per = Person::updateOrCreate(
            [
                'document_type_id'      => $request->get('document_type_id'),
                'number'                => $request->get('number'),
            ],
            [

                'short_name'            => trim($request->get('names')),
                'full_name'             => trim($request->get('father_lastname') . ' ' .  $request->get('mother_lastname') . ' ' . $request->get('names')),
                'description'           => $request->get('description'),
                'telephone'             => $request->get('telephone'),
                'email'                 => trim($request->get('email')),
                'address'               => $request->get('address'),
                'is_provider'           => false,
                'is_client'             => true,
                'ubigeo'                => $request->get('ubigeo'),
                'ubigeo_description'    => $request->get('ubigeo_description'),
                'birthdate'             => $request->get('birthdate'),
                'names'                 => trim($request->get('names')),
                'father_lastname'       => trim($request->get('father_lastname')),
                'mother_lastname'       => trim($request->get('mother_lastname')),
                'presentacion'          => $request->get('presentacion'),
                'country_id'            => $request->get('country_id') ?? 1,
                'gender'                => $request->get('gender') ?? 'M',
                'profession_id'         => $request->get('profession_id') ? $request->get('profession_id')['id'] : null,
                'occupation_id'         => $request->get('occupation_id') ? $request->get('occupation_id')['id'] : null,
                'ocupacion'             => $request->get('occupation_id') ? $request->get('occupation_id')['description'] : null,
                'profession'            => $request->get('profession_id') ? $request->get('profession_id')['description'] : null,
            ]
        );

        $path = null;
        $destination = 'uploads/teachers';
        $file = $request->file('image');
        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $original_name = str_replace(" ", "_", $original_name);
            $extension = $file->getClientOriginalExtension();
            $file_name = $per->id . '.' . $extension;
            $path = $request->file('image')->storeAs(
                $destination,
                $file_name,
                'public'
            );

            //$path = asset("storage/" . $path); //guardar la ruta COMPLETA
            $per->image = $path;
            $per->save();
        }

        $user = User::updateOrCreate(
            [
                'email'         => $request->get('email'),
                'person_id'     => $per->id
            ],
            [
                'name'          => $request->get('names'),
                'password'      => Hash::make($request->get('number')),
                'information'   => $request->get('description'),
                'avatar'        => $path,
            ]
        );

        $user->assignRole('Docente');

        AcaTeacher::updateOrCreate(
            [
                'person_id'     => $per->id
            ],
            [
                'teacher_code'  => $request->get('number'),
            ]
        );

        return redirect()->route('aca_teachers_list')
            ->with('message', __('Docente creado con éxito'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $identityDocumentTypes = DB::table('identity_document_type')->get();

        $countries = Country::orderBy('description')->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();
        $professions = DB::table('professions')
            ->orderBy('id')
            ->get();
        $occupations = DB::table('occupations')
            ->orderBy('id')
            ->get();

        $teacher = AcaTeacher::join('people', 'person_id', 'people.id')
                ->select(
                    'people.*',
                    'aca_teachers.id AS teacher_id'
                )
            ->where('aca_teachers.id', $id)
            ->first();

        $teacher->image_preview = $teacher->image;
        //dd($teacher);
        return Inertia::render('Academic::Teachers/Edit', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo'                => $ubigeo,
            'teacher'               => $teacher,
            'countries'             => $countries,
            'professions'           => $professions,
            'occupations'           => $occupations
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request)
    {
        $person_id = $request->get('id');
        $teacher_id = $request->get('teacher_id');
        $user = User::where('person_id', $request->get('id'))->first();

        $this->validate(

            $request,
            [
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'number'            => 'unique:people,number,' . $person_id . ',id,document_type_id,' . $request->get('document_type_id'),
                'telephone'         => 'required|max:12',
                'email'             => 'required|email|max:255',
                'email'             => 'regex:/^[\w\.-]+@[\w\.-]+\.\w+$/|max:255',
                'email'             => 'unique:people,email,' . $person_id . ',id',
                'email'             => 'unique:users,email,' . $user->id . ',id',
                'address'           => 'required|max:255',
                'birthdate'         => 'required|',
                'names'             => 'required|max:255',
                'father_lastname'   => 'required|max:255',
                'mother_lastname'   => 'required|max:255',
                'ubigeo_description'=> 'required|max:255'
            ],
            [
                'email.unique' => 'El email ya esta en uso en usuario o en persona',
                'ubigeo_description.required' => 'La ciudad es nesesaria',
                'ubigeo_description.max' => 'Excede el numero de caracteres permitidos'
            ]
        );

        $country_id = $request->get('country_id') ?? 1;

        if($country_id == 1){
            $this->validate(
                $request,
                [
                    'ubigeo' => 'required|max:255',
                ],
                [
                    'ubigeo.required' => 'La ciudad es nesesaria'
                ]
            );
        }

        $person = Person::find($person_id);
        $path = null;
        $destination = 'uploads/teachers';
        $file = $request->file('image');
        //dd($file);
        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $original_name = str_replace(" ", "_", $original_name);
            $extension = $file->getClientOriginalExtension();
            $file_name = $person_id . time() . '.' . $extension;
            $path = $request->file('image')->storeAs(
                $destination,
                $file_name,
                'public'
            );
            $person->image = $path;
            $person->save();
        }

        $person->update([
            'document_type_id'      => $request->get('document_type_id'),
            'short_name'            => trim($request->get('names')),
            'full_name'             => trim($request->get('father_lastname') . ' ' .  $request->get('mother_lastname') . ' ' . $request->get('names')),
            'description'           => $request->get('description'),
            'number'                => trim($request->get('number')),
            'telephone'             => $request->get('telephone'),
            'email'                 => trim($request->get('email')),
            'address'               => $request->get('address'),
            'is_provider'           => false,
            'is_client'             => true,
            'ubigeo'                => $request->get('ubigeo') ?? null,
            'birthdate'             => $request->get('birthdate'),
            'names'                 => trim($request->get('names')),
            'father_lastname'       => trim($request->get('father_lastname')),
            'mother_lastname'       => trim($request->get('mother_lastname')),
            'presentacion'          => $request->get('presentacion'),
            'country_id'            => $request->get('country_id') ?? 1,
            'gender'                => $request->get('gender') ?? 'M',
            'profession_id'         => $request->get('profession_id') ? $request->get('profession_id')['id'] : null,
            'occupation_id'         => $request->get('occupation_id') ? $request->get('occupation_id')['id'] : null,
            'ocupacion'             => $request->get('occupation_id') ? $request->get('occupation_id')['description'] : null,
            'profession'            => $request->get('profession_id') ? $request->get('profession_id')['description'] : null,
        ]);

        $user->update([
            'name'          => $request->get('names'),
            'email'         => $request->get('email'),
            'password'      => Hash::make($request->get('number')),
            'information'   => $request->get('description'),
            'avatar'        => $path
        ]);
        //dd($request->get('presentacion'));
        AcaTeacher::where('person_id', $person_id)->update([
            'teacher_code'  => $request->get('number'),
        ]);

        return redirect()->route('aca_teachers_edit', $teacher_id)
            ->with('message', __('Docente creado con éxito'));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $message = null;
        $success = false;

        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            $tea = AcaTeacher::findOrFail($id);
            $person_id = $tea->person_id;
            // Verificamos si existe.
            $per = Person::findOrFail($person_id);
            $tea->delete();
            // Si no hay detalles asociados, eliminamos.
            $per->delete();
            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Docente eliminada correctamente';
            $success = true;
        } catch (\Exception $e) {
            // Si ocurre alguna excepción durante la transacción, hacemos rollback para deshacer cualquier cambio.
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }

    public function resume($id)
    {
        $tea = AcaTeacher::findOrFail($id);
        $person_id = $tea->person_id;

        $per = Person::findOrFail($person_id);

        $resumes = AcaTeachingResume::select(
            DB::raw('true AS destroy'),
            DB::raw('false AS loading'),
            'id',
            'teacher_id',
            'description'
        )
            ->where('teacher_id', $id)
            ->get();

        if (count($resumes) > 0) {
            $resumes = $resumes->toArray();
        }

        return Inertia::render('Academic::Teachers/Resume', [
            'person'                => $per,
            'teacher'               => $tea,
            'resumes'               => $resumes
        ]);
    }

    public function workExperienceStore(Request $request)
    {
        $description = $request->get('description');
        $id = $request->get('id');
        $teacher_id = $request->get('teacher_id');

        $this->validate(
            $request,
            [
                'teacher_id'    => 'required',
                'description'   => 'required|max:500'
            ]
        );

        if ($id) {
            AcaTeachingResume::find($id)->update([
                'description'   => $description
            ]);
        } else {
            $resume = AcaTeachingResume::create([
                'type'          => 'work experience',
                'teacher_id'    => $teacher_id,
                'description'   => $description,
                'person_id'     => AcaTeacher::find($teacher_id)->person_id
            ]);

            $id = $resume->id;
        }

        return response()->json([
            'id' => $id
        ]);
    }

    public function workExperienceDestroy($id)
    {
        $message = null;
        $success = false;

        try {

            DB::beginTransaction();

            $resume = AcaTeachingResume::findOrFail($id);

            $resume->delete();

            DB::commit();

            $message =  'Experiencia eliminada correctamente';
            $success = true;
        } catch (\Exception $e) {

            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }
}
