<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Industry;
use App\Models\Parameter;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DataTables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaTeacher;
use Modules\CRM\Emails\MailwithUserAccount;
use Modules\CRM\Emails\PersonalizedEmailStudent;

class CrmContactsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $P000009;

    public function __construct()
    {
        $this->P000009 = Parameter::where('parameter_code', 'P000009')->value('value_default');
        // $this->P000009 si es 1 = cursos y capacitaciones
        // $this->P000009 si es 99 = todos
    }


    public function index()
    {

        return Inertia::render('CRM::Contacts/List', [
            'P000009' => $this->P000009
        ]);
    }

    public function getData()
    {
        $model = Person::query();
        $selectFields = ['people.*']; // Siempre incluir 'people.*'

        if ($this->P000009 == '1' || $this->P000009 == '99') {
            $model = $model->join('aca_students', 'aca_students.person_id', 'people.id');
            $selectFields[] = 'aca_students.new_student'; // Agregar 'new_student' dinámicamente
        }
        // Puedes agregar más condiciones similares aquí con otros joins y campos
        $model = $model->select($selectFields); // Aplicar el select con todos los campos acumulados
        $model = $model->where('people.id', '<>', 1);
        $model = $model->where('people.document_type_id', '=', 1);
        //$model = $model->where('people.is_client', true);

        return DataTables::of($model)->toJson();
    }

    public function massMailing()
    {
        $courses = [];
        if ($this->P000009 == '1' || $this->P000009 == '99') {
            $courses = AcaCourse::where('status', true)->get();
        }

        return Inertia::render('CRM::Contacts/MassMailing', [
            'P000009' => $this->P000009,
            'courses' => $courses
        ]);
    }

    public function getContactsPagination(Request $request)
    {
        $search = $request->get('search');
        $text = $search['search'];
        $num = $search['number_records'];

        $model = Person::query();
        $selectFields = ['people.*']; // Siempre incluir 'people.*'

        if ($this->P000009 == '1' || $this->P000009 == '99') {
            $model = $model->join('aca_students', 'aca_students.person_id', 'people.id');

            if ($search['type'] && is_array($search['type'])) {
                $ty = $search['type'][0];

                if ($ty == 'new') {
                    $model = $model->where('aca_students.new_student', true);
                } elseif ($ty == 'cur') {

                    $cu = $search['type'][1];

                    $model = $model->join('aca_cap_registrations', 'aca_students.id', 'aca_cap_registrations.student_id');

                    $model = $model->where('aca_cap_registrations.course_id', $cu);
                }
            }


            $selectFields[] = 'aca_students.new_student'; // Agregar 'new_student' dinámicamente
        }
        // Puedes agregar más condiciones similares aquí con otros joins y campos
        $model = $model->select($selectFields); // Aplicar el select con todos los campos acumulados
        $model = $model->where('people.id', '<>', 1);
        $model = $model->where('people.is_client', true);
        $model = $model->where(function (Builder $query) use ($text) {
            $query->where('people.number', '=', $text)
                ->orWhere('people.full_name', 'like', '%' . $text . '%');
        });

        return $model->paginate($num);
    }

    public function sendMassMessage(Request $request)
    {
        $correo = $request->get('correo');

        $type = $correo['type'];
        $message = $correo['message'];

        $correosEnviados = 0;
        $correosFallidos = [];

        $data = [
            'from_mail' => env('MAIL_FROM_ADDRESS'),
            'from_name' => env('MAIL_FROM_NAME'),
            'title' => $correo['title'],
            'contact' => $correo['contact'],
            'message' => null
        ];

        try {
            if ($type == 'ccu') {
                Mail::to(trim($correo['contact']['email']))->send(new MailwithUserAccount($data));
            } elseif ($type == 'cdb') {
            } elseif ($type == 'ccc') {
            } elseif ($type == 'cmp') {
                $data['message'] =  $message;
                Mail::to(trim($correo['contact']['email']))->send(new PersonalizedEmailStudent($data));
            }
            $correosEnviados = 1;
        } catch (\Exception $e) {

            $correosFallidos = [
                'email' => $correo['contact']['email'],
                'error' => $e->getMessage() // Guarda el mensaje de error
            ];
        }

        // Devuelve la respuesta con totales y detalles de errores
        return response()->json([
            'success' => count($correosFallidos) === 0,
            'enviados' => $correosEnviados,
            'fallidos' => $correosFallidos
        ]);
    }

    public function companies()
    {
        return Inertia::render('CRM::Companies/List', [
            'P000009' => $this->P000009
        ]);
    }

    public function getCompanies()
    {
        $model = Person::query();
        $model = $model->where('people.document_type_id', '=', 6);
        $model = $model->where('people.id', '<>', 1);
        $model = $model->where('people.is_client', true);

        return DataTables::of($model)->toJson();
    }

    public function companiesCreate()
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

        $industry = Industry::all();

        return Inertia::render('CRM::Companies/Create', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo' => $ubigeo,
            'industry' => $industry
        ]);
    }

    public function companiesStore(Request $request)
    {
        $update_id = $request->get('id');

        $this->validate(
            $request,
            [
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'number'            => 'unique:people,number,' . $update_id . ',id,document_type_id,' . $request->get('document_type_id'),
                'telephone'         => 'required|max:12',
                'email'             => 'required|max:255',
                'email'             => 'unique:people,email,' . $update_id . ',id',
                'address'           => 'required|max:255',
                'ubigeo'            => 'required|max:255',
                'industry'         => 'required|',
                'contact_telephone'             => 'required|max:255',
                'contact_name'   => 'required|max:255',
                'contact_email'   => 'required|max:255',
            ]
        );

        // $path = 'img' . DIRECTORY_SEPARATOR . 'imagen-no-disponible.jpeg';
        // $destination = 'uploads' . DIRECTORY_SEPARATOR . 'products';
        $path = null;
        $destination = 'uploads/crm/companies';
        $file = $request->file('image');

        $industry = $request->get('industry');

        $per = Person::updateOrCreate(
            [
                'document_type_id'      => $request->get('document_type_id'),
                'number'                => trim($request->get('number')),
            ],
            [
                'short_name'            => trim($request->get('short_name')),
                'full_name'             => strtoupper(trim($request->get('full_name'))),
                'names'                 => strtoupper(trim($request->get('short_name'))),
                'telephone'             => trim($request->get('telephone')),
                'email'                 => trim($request->get('email')),
                'image'                 => $path,
                'address'               => $request->get('address'),
                'is_provider'           => false,
                'is_client'             => true,
                'ubigeo'                => $request->get('ubigeo'),
                'industry_id'           => $industry['id'],
                'industry'              => $industry['description'],
                'contact_telephone'     => trim($request->get('contact_telephone')),
                'contact_name'          => trim($request->get('contact_name')),
                'contact_email'         => trim($request->get('contact_email'))
            ]
        );

        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $original_name = str_replace(" ", "_", $original_name);
            $extension = $file->getClientOriginalExtension();
            $file_name = trim($request->get('number')) . '.' . $extension;
            $path = $request->file('image')->storeAs(
                $destination,
                $file_name,
                'public'
            );

            $per->image = $path;
            $per->save();
        }


        return redirect()->route('crm_companies_list')
            ->with('message', __('Empresa creado con éxito'));
    }

    public function companiesEdit($id)
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

        $industry = Industry::all();

        $company = Person::leftJoin('districts', 'ubigeo', 'districts.id')
            ->leftJoin('provinces', 'districts.province_id', 'provinces.id')
            ->leftJoin('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'people.*',
                DB::raw('CONCAT(departments.name,"-",provinces.name,"-",districts.name) AS city')
            )
            ->where('people.id', $id)
            ->first();

        $company->image_preview = $company->image;

        return Inertia::render('CRM::Companies/Edit', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo' => $ubigeo,
            'industry' => $industry,
            'empresa' => $company
        ]);
    }

    public function companiesUpdate(Request $request)
    {
        $update_id = $request->get('id');

        $this->validate(
            $request,
            [
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'number'            => 'unique:people,number,' . $update_id . ',id,document_type_id,' . $request->get('document_type_id'),
                'telephone'         => 'required|max:12',
                'email'             => 'required|max:255',
                'email'             => 'unique:people,email,' . $update_id . ',id',
                'address'           => 'required|max:255',
                'ubigeo'            => 'required|max:255',
                'industry'         => 'required|',
                'contact_telephone'             => 'required|max:255',
                'contact_name'   => 'required|max:255',
                'contact_email'   => 'required|max:255',
            ]
        );

        // $path = 'img' . DIRECTORY_SEPARATOR . 'imagen-no-disponible.jpeg';
        // $destination = 'uploads' . DIRECTORY_SEPARATOR . 'products';
        $path = null;
        $destination = 'uploads/crm/companies';
        $file = $request->file('image');

        $industry = $request->get('industry');

        $per = Person::find($update_id)->update(
            [
                'document_type_id'      => $request->get('document_type_id'),
                'number'                => trim($request->get('number')),
                'short_name'            => strtoupper(trim($request->get('short_name'))),
                'full_name'             => strtoupper(trim($request->get('full_name'))),
                'names'                 => strtoupper(trim($request->get('short_name'))),
                'telephone'             => trim($request->get('telephone')),
                'email'                 => trim($request->get('email')),
                'image'                 => $path,
                'address'               => $request->get('address'),
                'ubigeo'                => $request->get('ubigeo'),
                'industry_id'           => $industry['id'],
                'industry'              => $industry['description'],
                'contact_telephone'     => trim($request->get('contact_telephone')),
                'contact_name'          => trim($request->get('contact_name')),
                'contact_email'         => trim($request->get('contact_email'))
            ]
        );

        if ($file) {
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $original_name = str_replace(" ", "_", $original_name);
            $extension = $file->getClientOriginalExtension();
            $file_name = trim($request->get('number')) . '.' . $extension;
            $path = $request->file('image')->storeAs(
                $destination,
                $file_name,
                'public'
            );

            $per->image = $path;
            $per->save();
        }
    }

    public function companiesDestroy($id)
    {
        try {
            $person = Person::findOrFail($id);
            $person->delete();
            Person::where('company_person_id', $id)->update(['company_person_id' => null]);
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Registro eliminado correctamente'
                ],
                200
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        }
    }

    public function companiesEmployees($id)
    {
        $employees = Person::where('company_person_id', $id)->get();
        $empresa = Person::find($id);

        return Inertia::render('CRM::Companies/Employees', [
            'employees' => $employees,
            'empresa' => $empresa
        ]);
    }

    public function getEmployeesSearch(Request $request)
    {
        $search = $request->get('search');

        $model = Person::query();
        $selectFields = ['people.*']; // Siempre incluir 'people.*'

        if ($this->P000009 == '1' || $this->P000009 == '99') {
            $model = $model->join('aca_students', 'aca_students.person_id', 'people.id');

            $selectFields[] = 'aca_students.new_student'; // Agregar 'new_student' dinámicamente
        }
        // Puedes agregar más condiciones similares aquí con otros joins y campos
        $model = $model->select($selectFields); // Aplicar el select con todos los campos acumulados
        $model = $model->where('people.id', '<>', 1);
        $model = $model->where('people.is_client', true);
        $model = $model->where(function (Builder $query) use ($search) {
            $query->where('people.number', '=', $search)
                ->orWhere('people.full_name', 'like', '%' . $search . '%');
        });

        return response()->json([
            'employees' => $model->get()
        ]);
    }

    public function contactsDocentsChat()
    {
        //dd(Auth::user()->person_id);
        $student = AcaStudent::where('person_id', Auth::user()->person_id)->first();
        $docents = [];
        //dd($student);
        // $docents = AcaCapRegistration::with('course.teacher.person')
        //     ->where('student_id', $student->id)
        //     ->where('status', true)
        //     ->get()
        //     ->map(function ($registration) {
        //         return $registration->course->teacher ?? null;
        //     })
        //     ->filter() // Elimina valores nulos en caso de cursos sin teacher
        //     ->unique('id') // Agrupa por ID del teacher
        //     ->values(); // Reindexa el array
        //dd($docents);

        $existeActiva = AcaStudentSubscription::where('student_id', $student->id)
            ->where('status', true)
            ->exists();

        if($existeActiva){
            $docents = User::with('person')
                ->role('Asistente') // Método de Spatie
                ->get();
        }

        return response()->json([
            'success' => true,
            'docents' => $docents
        ], 200);
    }
}
