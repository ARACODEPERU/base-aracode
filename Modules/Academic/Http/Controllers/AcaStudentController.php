<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\District;
use App\Models\Industry;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaStudent;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaModule;
use Illuminate\Http\RedirectResponse;
use Modules\Academic\Entities\AcaStudentSubscription;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaStudentHistory;

class AcaStudentController extends Controller
{
    use ValidatesRequests;

    private $ubl;
    private $igv;
    private $top;
    private $icbper;

    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function __construct()
    {
        $this->ubl = Parameter::where('parameter_code', 'P000003')->value('value_default');
        $this->igv = Parameter::where('parameter_code', 'P000001')->value('value_default');
        $this->top = Parameter::where('parameter_code', 'P000002')->value('value_default');
        $this->icbper = Parameter::where('parameter_code', 'P000004')->value('value_default');
    }

    public function index()
    {
        $students = (new AcaStudent())->newQuery();
        $students = $students->join('people', 'aca_students.person_id', 'people.id')
            ->select(
                'aca_students.id',
                'aca_students.student_code',
                'people.document_type_id',
                'people.full_name',
                'people.number',
                'people.telephone',
                'people.email',
                'people.address',
                'people.birthdate',
                'people.image AS people_image',
                'aca_students.created_at',
                'aca_students.new_student',
                DB::raw('(SELECT COUNT(course_id) FROM aca_cap_registrations WHERE student_id=aca_students.id) as countCourses'),
                DB::raw('(SELECT COUNT(subscription_id) FROM aca_student_subscriptions WHERE student_id=aca_students.id) as countSubscriptions'),
                DB::raw('(SELECT COUNT(course_id) FROM aca_certificates WHERE student_id=aca_students.id) as countCertificates')
            );
        if (request()->has('search')) {
            $searchTerm = request()->input('search');

            $students->where(function ($query) use ($searchTerm) {
                $query->where('people.full_name', 'LIKE', '%' . $searchTerm . '%') // Búsqueda parcial por nombre
                    ->orWhere('people.email', 'LIKE', '%' . $searchTerm . '%')     // Búsqueda parcial por email
                    ->orWhere('people.number', '=', $searchTerm);                  // ¡Coincidencia EXACTA por DNI!
            });
        }
        if (request()->query('sort')) {
            $attribute = request()->query('sort');
            $sort_order = 'ASC';
            if (strncmp($attribute, '-', 1) === 0) {
                $sort_order = 'DESC';
                $attribute = substr($attribute, 1);
            }
            $students->orderBy($attribute, $sort_order);
        } else {
            $students->latest();
        }

        $students = $students->paginate(12)->onEachSide(2);

        return Inertia::render('Academic::Students/List', [
            'students' => $students,
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
        $industrias = Industry::select('id', 'description')
            ->orderBy('id')
            ->get();
        $professions = DB::table('professions')
            ->orderBy('id')
            ->get();
        $occupations = DB::table('occupations')
            ->orderBy('id')
            ->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        return Inertia::render('Academic::Students/Create', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo' => $ubigeo,
            'industrias' => $industrias,
            'professions' => $professions,
            'occupations' => $occupations
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
        //dd($request->all());
        $this->validate(
            $request,
            [
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'number'            => 'unique:people,number,' . $update_id . ',id,document_type_id,' . $request->get('document_type_id'),
                'telephone'         => 'required|max:12',
                'email'             => 'required|email|max:255',
                'email'             => 'unique:people,email,' . $update_id . ',id',
                'email'             => 'unique:users,email,' . ($user ? $user->id  : null) . ',id',
                'address'           => 'required|max:255',
                'ubigeo'            => 'required|max:255',
                'birthdate'         => 'required|',
                'names'             => 'required|max:255',
                'father_lastname'   => 'required|max:255',
                'mother_lastname'   => 'required|max:255',
            ]
        );

        // $path = 'img' . DIRECTORY_SEPARATOR . 'imagen-no-disponible.jpeg';
        // $destination = 'uploads' . DIRECTORY_SEPARATOR . 'products';
        $path = null;
        $destination = 'uploads/students';
        $file = $request->file('image');

        $per = Person::updateOrCreate(
            [
                'document_type_id'      => $request->get('document_type_id'),
                'number'                => $request->get('number'),
            ],
            [
                'short_name'            => $request->get('names'),
                'full_name'             => trim($request->get('father_lastname') . ' ' .  $request->get('mother_lastname') . ' ' . $request->get('names')),
                'description'           => $request->get('description'),
                'telephone'             => $request->get('telephone'),
                'email'                 => $request->get('email'),
                'image'                 => $path,
                'address'               => $request->get('address'),
                'is_provider'           => false,
                'is_client'             => true,
                'ubigeo'                => $request->get('ubigeo'),
                'ubigeo_description'    => $request->get('ubigeo_description'),
                'birthdate'             => $request->get('birthdate'),
                'names'                 => trim($request->get('names')),
                'father_lastname'       => trim($request->get('father_lastname')),
                'mother_lastname'       => trim($request->get('mother_lastname')),
                'gender'                => $request->get('gender') ?? 'M',
                'industry_id'           => $request->get('industry_id') ? $request->get('industry_id')['id'] : null,
                'profession_id'         => $request->get('profession_id') ? $request->get('profession_id')['id'] : null,
                'occupation_id'         => $request->get('occupation_id') ? $request->get('occupation_id')['id'] : null,
                'ocupacion'             => $request->get('occupation_id') ? $request->get('occupation_id')['description'] : null,
                'profession'            => $request->get('profession_id') ? $request->get('profession_id')['description'] : null,
                'industry'              => $request->get('industry_id') ? $request->get('industry_id')['description'] : null,
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


        $user = User::updateOrCreate(
            [
                'email'         => trim($request->get('email')),
                'person_id'     => $per->id
            ],
            [
                'name'          => trim($request->get('names')),
                'password'      => Hash::make(trim($request->get('number'))),
                'information'   => $request->get('description'),
                'avatar'        => $path,
                'person_id'     => $per->id,
                'local_id'      => 1
            ]
        );

        $user->assignRole('Alumno');

        AcaStudent::updateOrCreate(
            [
                'person_id'     => $per->id
            ],
            [
                'student_code'  => $request->get('number'),
            ]
        );

        return redirect()->route('aca_students_list')
            ->with('message', __('Estudiante creado con éxito'));
    }


    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $identityDocumentTypes = DB::table('identity_document_type')->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $student = AcaStudent::join('people', 'person_id', 'people.id')
            ->select(
                'people.*',
                'aca_students.id AS student_id'
            )
            ->where('aca_students.id', $id)
            ->first();

        $industrias = Industry::select('id', 'description')
            ->orderBy('id')
            ->get();
        $professions = DB::table('professions')
            ->orderBy('id')
            ->get();
        $occupations = DB::table('occupations')
            ->orderBy('id')
            ->get();

        $student->image_preview = $student->image;

        return Inertia::render('Academic::Students/Edit', [
            'identityDocumentTypes' => $identityDocumentTypes,
            'ubigeo'                => $ubigeo,
            'student'               => $student,
            'industrias'            => $industrias,
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
        $student_id = $request->get('student_id');
        $user = User::where('person_id', $person_id)->first();

        $this->validate(

            $request,
            [
                'document_type_id'  => 'required',
                'number'            => 'required|max:12',
                'number'            => 'unique:people,number,' . $person_id . ',id,document_type_id,' . $request->get('document_type_id'),
                'telephone'         => 'required|max:12',
                'email'             => 'required|email|max:255',
                'email'            => 'unique:people,email,' . $person_id . ',id',
                'email'            => 'unique:users,email,' . $user->id . ',id',
                'address'           => 'required|max:255',
                'ubigeo'            => 'required|max:255',
                'birthdate'         => 'required|',
                'names'             => 'required|max:255',
                'father_lastname'   => 'required|max:255',
                'mother_lastname'   => 'required|max:255',
            ]
        );

        // $path = 'img' . DIRECTORY_SEPARATOR . 'imagen-no-disponible.jpeg';
        // $destination = 'uploads' . DIRECTORY_SEPARATOR . 'products';
        $path = null;
        $destination = 'uploads/students';
        $file = $request->file('image');
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
            //$path = asset('storage/' . $path);
            $path =  $path;
        }

        Person::find($person_id)->update([
            'document_type_id'      => $request->get('document_type_id'),
            'short_name'            => trim($request->get('names')),
            'full_name'             => trim($request->get('father_lastname') . ' ' .  $request->get('mother_lastname') . ' ' . $request->get('names')),
            'description'           => $request->get('description'),
            'number'                => trim($request->get('number')),
            'telephone'             => $request->get('telephone'),
            'email'                 => trim($request->get('email')),
            'image'                 => $path,
            'address'               => $request->get('address'),
            'is_provider'           => false,
            'is_client'             => true,
            'ubigeo'                => $request->get('ubigeo'),
            'ubigeo_description'    => $request->get('ubigeo_description'),
            'birthdate'             => $request->get('birthdate'),
            'names'                 => trim($request->get('names')),
            'father_lastname'       => trim($request->get('father_lastname')),
            'mother_lastname'       => trim($request->get('mother_lastname')),
            'gender'                => $request->get('gender') ?? 'M',
            'industry_id'           => $request->get('industry_id') ? $request->get('industry_id')['id'] : null,
            'profession_id'         => $request->get('profession_id') ? $request->get('profession_id')['id'] : null,
            'occupation_id'         => $request->get('occupation_id') ? $request->get('occupation_id')['id'] : null,
            'ocupacion'             => $request->get('occupation_id') ? $request->get('occupation_id')['description'] : null,
            'profession'            => $request->get('profession_id') ? $request->get('profession_id')['description'] : null,
            'industry'              => $request->get('industry_id') ? $request->get('industry_id')['description'] : null,
        ]);

        $user->update([
            'name'          => $request->get('names'),
            'email'         => $request->get('email'),
            //'password'      => Hash::make($request->get('number')),
            'information'   => $request->get('description'),
            'avatar'        => $path
        ]);

        AcaStudent::where('person_id', $person_id)->update([
            'student_code'  => $request->get('number'),
        ]);

        return redirect()->route('aca_students_edit', $student_id)
            ->with('message', __('Estudiante creado con éxito'));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function myCourses()
    {

        $user = Auth::user();
        $student_id = AcaStudent::where('person_id', $user->person_id)->value('id');
        $courses = [];
        $mycourses = [];

        $studentSubscribed = AcaStudentSubscription::where('student_id', $student_id)
            ->where('status', true)
            ->first();

        // También puedes verificar múltiples roles a la vez
        if ($user->hasAnyRole(['admin', 'Docente', 'Administrador'])) {
            $courses = AcaCourse::with('modules.themes.contents')
                ->with('teacher.person')->where('status', true)
                ->with('category')
                ->with('modality')
                ->orderBy('id', 'DESC')
                ->get()
                ->map(function ($course) {
                    $course->can_view = true; // Campo adicional
                    return $course;
                });
        } else {
            // $courses = AcaCourse::with(['modules.themes.contents', 'modality', 'category', 'teacher.person'])
            //     ->with('registrations') // Para validar los cursos registrados
            //     ->get()
            //     ->map(function ($course) use ($studentSubscribed, $student_id) {
            //         // Verificar si el curso es gratuito
            //         $isFree = is_null($course->price) || floatval($course->price) == 0.00;
            //         $isProgram = $course->type_description == 'Programas de especialización' ? true : false;
            //         // Verificar si el alumno está registrado en este curso
            //         $isRegistered = $course->registrations->contains('student_id', $student_id);

            //         // Verificar si el alumno tiene una suscripción activa
            //         $hasActiveSubscription = $studentSubscribed !== null;

            //         // Lógica para determinar si puede ver
            //         if ($hasActiveSubscription || $isRegistered || $isFree) {
            //             if ($isProgram) {
            //                 if ($isRegistered) {
            //                     $course->can_view = true;
            //                 } else {
            //                     $course->can_view = false;
            //                 }
            //             } else {
            //                 $course->can_view = true;
            //             }
            //         } else {
            //             $course->can_view = false; // Campo adicional
            //         }

            //         return $course;
            //     });

            $mycourses = AcaCourse::with('modules.themes.contents')
                ->with('modality')
                ->with('teacher.person')->whereHas('registrations', function ($query) use ($student_id) {
                    $query->where('student_id', $student_id);
                })->orderBy('id', 'DESC')
                ->get();

            $result = AcaCourse::with(['modules.themes.contents', 'modality', 'teacher.person'])
                ->whereDoesntHave('registrations', function ($query) use ($student_id) {
                    $query->where('student_id', $student_id);
                })
                ->get();

            // Agrupar cursos
            $grouped = [];

            foreach ($result as $course) {
                if (empty($course->price) || $course->price == 0) {
                    $grouped['Cursos gratis'][] = $course;
                } else {
                    $grouped[$course->type_description][] = $course;
                }
            }

            // Ordenar alfabéticamente por clave del grupo
            ksort($grouped);

            // Transformar al formato deseado
            $courses = collect($grouped)->map(function ($items, $type_description) {
                return [
                    'type_description' => $type_description,
                    'courses' => $items,
                ];
            })->values(); // values() para que los índices sean numéricos
            //dd($courses);
        }

        $certificates = AcaCertificate::with('course')
            ->where('student_id', $student_id)
            ->get();




        return Inertia::render('Academic::Students/Courses', [
            'mycourses' => $mycourses,
            'courses' => $courses,
            'studentSubscribed' => $studentSubscribed,
            'certificates' => $certificates
        ]);
    }

    public function courseLessons($id)
    {

        // Obtener el curso con relaciones
        $course = AcaCourse::with(['modules.themes.contents'])->where('id', $id)->first();

        // Verificar si existe el curso
        if (!$course) {
            abort(404, 'Curso no encontrado.');
        }

        // Verificar si el estudiante está matriculado
        $isEnrolled = $course->registrations()
            ->where('student_id', AcaStudent::where('person_id', Auth::user()->person_id)->value('id'))
            ->exists();

        // Verificar si el curso es gratuito
        $isFree = $course->price == 0 || is_null($course->price);

        // Denegar acceso si no está matriculado y el curso no es gratis
        if (!$isEnrolled && !$isFree) {
            abort(403, 'No tienes acceso a este curso.');
        }

        $courseSummary = [
            'course' => $course->only(['id', 'description']), // o los campos que desees
            'modules' => []
        ];

        foreach ($course->modules as $module) {
            $totalThemes = $module->themes->count();
            $totalFiles = 0;
            $totalVideos = 0;

            foreach ($module->themes as $theme) {
                foreach ($theme->contents as $content) {
                    if ($content->is_file == 1 || $content->is_file == 2) {
                        $totalFiles++;
                    } elseif ($content->type == 0) {
                        $totalVideos++;
                    }
                }
            }

            $courseSummary['modules'][] = [
                'id' => $module->id,
                'description' => $module->description,
                'position' => $module->position,
                'total_themes' => $totalThemes,
                'total_files' => $totalFiles,
                'total_videos' => $totalVideos,
            ];
        }

        return Inertia::render('Academic::Students/Lessons', [
            'course' => $courseSummary
        ]);
    }

    public function courseLessonThemes($id)
    {


        $module = AcaModule::with('teacher.person')
            ->with(['themes' => function ($query) {
                $query->orderBy('position')
                    ->with('contents')
                    ->with('comments.user'); // Cargar los contenidos de cada theme
            }])
            ->where('id', $id)
            ->first();

        $course = AcaCourse::with('teacher.person')->where('id', $module->course_id)
            ->first();

        return Inertia::render('Academic::Students/Themes', [
            'course' => $course,
            'module' => $module
        ]);
    }

    public function invoice($id)
    {
        $payments = PaymentMethod::all();
        $saleDocumentTypes = DB::table('sale_document_types')->whereIn('sunat_id', ['01', '03'])->get();

        $services = Product::where('is_product', false)->get();
        $courses = AcaCourse::where('status', true)
            ->where(function ($query) {
                $query->whereNotNull('price')->where('price', '>', 0);
            })
            ->get();

        $registrationCourses = AcaCapRegistration::with('course')
            ->with('salenote')
            ->where('student_id', $id)
            ->whereNull('document_id')
            ->whereHas('course', function ($query) {
                $query->whereNotNull('price')->where('price', '>', 0);
            })
            ->whereNull('sale_note_id')
            ->get();

        $subscriptions = AcaStudentSubscription::with('subscription')
            ->where('student_id', $id)
            ->whereNull('onli_sale_id')
            ->whereNull('xdocument_id') ///si esta lleno es porque lo compro en linea
            ->get();

        $standardIdentityDocument = DB::table('identity_document_type')->get();

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                'districts.name AS district_name',
                'provinces.name AS province_name',
                'departments.name AS department_name',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS city_name")
            )
            ->get();

        return Inertia::render('Academic::Students/Invoice', [
            'payments' => $payments,
            'saleDocumentTypes' => $saleDocumentTypes,
            'student' => AcaStudent::with('person')->where('id', $id)->first(),
            'services' => $services,
            'courses' => $courses,
            'taxes' => array(
                'igv' => $this->igv,
                'icbper' => $this->icbper
            ),
            'registrationCourses' => $registrationCourses,
            'subscriptions' => $subscriptions,
            'standardIdentityDocument' => $standardIdentityDocument,
            'departments'       => $ubigeo,
        ]);
    }

    public function getCourses(Request $request)
    {
        $student = AcaStudent::where('person_id', $request->id)->first();
        $mycourses = $student->registrations()
            ->with(['course.modality', 'course.teachers.teacher.person'])
            ->get();

        $allcourses = [];

        if (empty($mycourses)) {
            $allcourses = AcaCourse::with('teachers.teacher.person')
                ->with('modality')
                ->get();
        } else {
            $ids = $mycourses->pluck('course_id')->toArray();
            $allcourses = AcaCourse::with('teachers.teacher.person')
                ->with('modality')
                ->whereNotIn('id', $ids)
                ->get();
        }


        return response()->json([
            'mycourses' => $mycourses,
            'allcourses' => $allcourses
        ]);
    }

    public function startStudentFree(Request $request): RedirectResponse
    {
        $type = $request->get('subscription');
        $user = Auth::user();
        $person_id = $user->person_id;
        $per = null;
        if ($person_id) {
            $per = Person::find($person_id);

            $user->assignRole('Alumno');

            AcaStudent::updateOrCreate(
                [
                    'person_id'     => $per->id
                ],
                [
                    'student_code'  => $per->number,
                    'new_student' => false
                ]
            );
            return to_route('dashboard');
        } else {
            return to_route('profile.edit');
        }
    }


    public function import(Request $request)
    {
        try {
            // Validar el archivo
            $request->validate([
                'file' => 'required|mimes:xlsx,xls',
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            DB::beginTransaction();

            $totalRows = count($rows) - 1; // Total de filas a procesar

            // Usar una clave única para el progreso
            $importKey = uniqid();

            // Iniciar el progreso en 0
            Cache::put($importKey, 0);

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                if (array_filter($row) === []) {
                    continue; // Ignorar filas completamente vacías
                }

                $cont = $index + 1;

                // Validar datos obligatorios
                if (!isset($row[0]) || !isset($row[1]) || !isset($row[4]) || !isset($row[9]) || trim($row[0]) === '' || trim($row[1]) === '' || trim($row[4]) === '' || trim($row[9]) === '') {
                    throw new \Exception("Fila {$cont}: Faltan datos obligatorios.");
                }

                // Validar cada campo con detalles específicos
                if (!$row[0]) {
                    throw new \Exception("Fila {$cont}: El campo 'Nombre completo' (columna A) es obligatorio.");
                }

                if (!$row[1]) {
                    throw new \Exception("Fila {$cont}: El campo 'Número' (columna B) es obligatorio.");
                }
                $dni = $row[1];
                if (Person::where('number', $dni)->exists()) {
                    throw new \Exception("Fila {$cont}: Número de identificación ya registrado ({$dni}).");
                }
                $fechaExcel = $row[2]; // Fecha en formato d/m/Y

                // Validar fecha con strtotime
                if (strtotime($fechaExcel) !== false) {
                    $fechaMysql = Carbon::parse($fechaExcel)->format('Y-m-d');
                } else {
                    throw new \Exception("Fila {$cont}: Formato de fecha no válido: {$fechaExcel}");
                }

                $fechaMysql = Carbon::parse($fechaExcel)->format('Y-m-d');

                if (!$row[4]) {
                    throw new \Exception("Fila {$cont}: El campo 'Correo electrónico' (columna E) es obligatorio.");
                }
                $email = trim($row[4]); // Eliminar espacios al inicio y al final

                // Opcional: eliminar caracteres no visibles
                $email = preg_replace('/[^\P{C}\n]+/u', '', $email);

                // Validar el correo
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Fila {$index}: El correo '{$email}' no es válido.");
                }

                if (Person::where('email', $email)->exists()) {
                    throw new \Exception("Fila {$cont}: Email ya registrado ({$email}).");
                }

                if (!isset($row[9]) || !$row[9]) {
                    throw new \Exception("Fila {$cont}: El campo 'Género' (columna J) es obligatorio.");
                }
                // Validar género
                $genero = trim(strtoupper($row[9]));
                if (!in_array($genero, ['M', 'F'])) {
                    throw new \Exception("Fila {$cont}: El campo 'Género' (columna J) debe ser 'M = Masculino' o 'F = Femenino'. Valor encontrado: '{$genero}'");
                }

                // Crear registro en la base de datos
                $person = Person::create([
                    'document_type_id' => 1,
                    'full_name' => trim($row[0]),
                    'number' => trim($row[1]),
                    'birthdate' => $fechaMysql,
                    'telephone' => $row[3],
                    'email' => trim($row[4]),
                    'company' => $row[5],
                    'industry' => $row[6],
                    'ocupacion' => $row[7],
                    'profession' => $row[8],
                    'gender' => $row[9],
                    'is_provider' => false,
                    'is_client' => true
                ]);

                User::updateOrCreate(
                    [
                        'email' => trim($row[4])
                    ],
                    [
                        'name' => trim($row[0]),
                        'password' => Hash::make(trim($row[1])),
                        'local_id' => 1,
                        'person_id' => $person->id,
                        'status' => true
                    ]
                );

                $student = AcaStudent::firstOrCreate(
                    [
                        'person_id' => $person->id,
                        'student_code' => trim($row[1]),
                    ],
                    [
                        'new_student' => true
                    ]
                );

                // Actualizar el progreso en la caché
                $currentProgress = (($index + 1) / $totalRows) * 100;
                Cache::put($importKey, $currentProgress);
            }

            DB::commit();

            // Al finalizar, actualizar el progreso al 100%
            Cache::put($importKey, 100);

            return response()->json([
                'message' => 'Archivo importado exitosamente.',
                'importKey' => $importKey, // Devolver la clave para que se pueda consultar el progreso
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error de importación: {$e->getMessage()}");

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 400);
        }
    }



    public function getProgress($importKey)
    {
        // Obtener el progreso desde la caché
        $progress = Cache::get($importKey, 0);

        return response()->json(['progress' => $progress]);
    }


    public function importByCourse(Request $request)
    {
        $student = $request->get('student');
        $course_id = $request->get('course_id');
        $index = $request->get('index');
        $modality_id = $request->get('modality_id');

        $cont = $index + 1;

        try {
            if (!isset($student[0]) || !isset($student[1]) || !isset($student[4]) || !isset($student[9]) || trim($student[0]) === '' || trim($student[1]) === '' || trim($student[4]) === '' || trim($student[9]) === '') {
                throw new \Exception("Fila {$cont}: Faltan datos obligatorios. (Todos los campos son obligatorios)");
            }

            // Validar cada campo con detalles específicos
            if (!$student[0]) {
                throw new \Exception("Fila {$cont}: El campo 'Nombre completo' (columna A) es obligatorio.");
            }

            if (!$student[1]) {
                throw new \Exception("Fila {$cont}: El campo 'Número' (columna B) es obligatorio.");
            }
            $dni = $student[1];
            // if (Person::where('number', $dni)->exists()) {
            //     throw new \Exception("Fila {$cont}: Número de identificación ya registrado ({$dni}).");
            // }
            $fechaExcel = $student[2]; // Fecha en formato d/m/Y

            // Validar fecha con strtotime
            if (strtotime($fechaExcel) !== false) {
                $fechaMysql = Carbon::parse($fechaExcel)->format('Y-m-d');
            } else {
                throw new \Exception("Fila {$cont}: Formato de fecha no válido: {$fechaExcel}");
            }

            $fechaMysql = Carbon::parse($fechaExcel)->format('Y-m-d');

            if (!$student[4]) {
                throw new \Exception("Fila {$cont}: El campo 'Correo electrónico' (columna E) es obligatorio.");
            }
            $email = trim($student[4]); // Eliminar espacios al inicio y al final

            // Opcional: eliminar caracteres no visibles
            $email = preg_replace('/[^\P{C}\n]+/u', '', $email);

            // Validar el correo
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Fila {$index}: El correo '{$email}' no es válido.");
            }

            // if (Person::where('email', $email)->exists()) {
            //     throw new \Exception("Fila {$cont}: Email ya registrado ({$email}).");
            // }

            if (!isset($student[9]) || !$student[9]) {
                throw new \Exception("Fila {$cont}: El campo 'Género' (columna J) es obligatorio.");
            }
            // Validar género
            $genero = trim(strtoupper($student[9]));
            if (!in_array($genero, ['M', 'F'])) {
                throw new \Exception("Fila {$cont}: El campo 'Género' (columna J) debe ser 'M = Masculino' o 'F = Femenino'. Valor encontrado: '{$genero}'");
            }


            // Crear registro en la base de datos
            $person = Person::firstOrCreate(
                [
                    'number' => trim($student[1]),
                ],
                [
                    'document_type_id' => 1,
                    'full_name' => trim($student[0]),
                    'birthdate' => $fechaMysql,
                    'telephone' => $student[3],
                    'email' => trim($student[4]),
                    'company' => $student[5] == "-" ? null : $student[5],
                    'industry' => $student[6] == "-" ? null : $student[6],
                    'ocupacion' => $student[7] == "-" ? null : $student[7],
                    'profession' => $student[8] == "-" ? null : $student[8],
                    'gender' => $student[9] == "-" ? null : $student[9],
                    'is_provider' => false,
                    'is_client' => true
                ]
            );

            User::firstOrCreate(
                [
                    'email' => trim($student[4])
                ],
                [
                    'name' => trim($student[0]),
                    'password' => Hash::make(trim($student[1])),
                    'local_id' => 1,
                    'person_id' => $person->id,
                    'status' => true
                ]
            );

            $student = AcaStudent::firstOrCreate(
                [
                    'person_id' => $person->id,
                    'student_code' => trim($student[1])
                ],
                [
                    'new_student' => true
                ]
            );

            AcaCapRegistration::firstOrCreate([
                'student_id' => $student->id,
                'course_id' => $course_id
            ], [
                'status' => true,
                'modality_id' => $modality_id
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se registro correctamente",
            ]);
        } catch (\Exception $e) {

            //Log::error("Error de importación: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
    }

    public function getCertificates()
    {

        // Verificar si el usuario tiene el rol de "Alumno"
        if (!Auth::user()->hasRole('Alumno')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver estos datos.'
            ], 403);
        }

        // Obtener el student_id del usuario autenticado
        $student_id = AcaStudent::where('person_id', Auth::user()->person_id)->value('id');

        // Obtener los certificados del estudiante
        $certificates = AcaCertificate::with('course')
            ->where('student_id', $student_id)
            ->get();

        $items = [];
        if ($certificates) {
            $items = $certificates;
        }

        return response()->json([
            'success' => true,
            'certificates' => $items,
        ], 200);
    }

    public function historyStore(Request $request)
    {
        AcaStudentHistory::create([
            'user_id' => Auth::id(),
            'person_id' => Auth::user()->person_id,
            'course_id' => $request->get('course_id'),
            'module_id' => $request->get('module_id'),
            'theme_id' => $request->get('theme_id'),
            'content_id' => $request->get('content_id'),
            'type_content' => $request->get('type_content')
        ]);
    }
}
