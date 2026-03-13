<?php

namespace Modules\Academic\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaBrochure;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaInstitution;
use Modules\Academic\Entities\AcaModality;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Entities\AcaTeacherCourse;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaStudentAttendance;
use Modules\Academic\Entities\AcaThemeComment;
use Modules\Academic\Entities\AcaStudentParticipation;

class AcaCourseController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    protected $P000010; ///token Tiny
    protected $P000018;

    protected $RPTABLE;

    public function __construct()
    {
        $this->RPTABLE = env('RECORDS_PAGE_TABLE') ?? 10;
        $this->P000010  = Parameter::where('parameter_code', 'P000010')->value('value_default');
        $this->P000018  = Parameter::where('parameter_code', 'P000018')->value('value_default');
    }

    public function index()
    {
        //dd(request()->all('status'));
        $courses = (new AcaCourse())->newQuery();
        if (request()->has('search')) {
            $courses->where('description', 'like', '%' . request()->input('search') . '%');
        }
        if (request()->has('modality')) {
            $courses->where('modality_id', '=', request()->input('modality'));
        }
        if (request()->has('status')) {

            if (request()->get('status') == 1) {
                $courses->where('status', true);
            }

            if (request()->get('status') == 0) {
                 $courses->where('status', false);
            }
        }
        $courses->orderBy('id', 'DESC');
        $courses->with([
            'category',
            'modality',
            'exam' => function ($query){
                $query->whereNull('module_id');
            }
        ]);
        $courses = $courses->paginate($this->RPTABLE)->onEachSide(2);

        $categories = AcaCategoryCourse::get();
        $modalities = AcaModality::get();
        $types = getEnumValues('aca_courses', 'type_description');

        $institutions = AcaInstitution::where('status', true)->get();

        return Inertia::render('Academic::Courses/List', [
            'courses'       => $courses,
            'institutions'  => $institutions,
            'categories' => $categories,
            'modalities' => $modalities,
            'types' => $types,
            'coursesActive' => AcaCourse::where('status', true)->count(),
            'filters' => request()->all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $categories = AcaCategoryCourse::all();
        $modalities = AcaModality::all();
        $types = getEnumValues('aca_courses', 'type_description');
        $sectors = getEnumValues('aca_courses', 'sector_description');

        return Inertia::render('Academic::Courses/Create', [
            'modalities'    => $modalities,
            'categories'    => $categories,
            'types'    => $types,
            'sectors'    => $sectors,
            'P000018' => $this->P000018
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'description' => 'required',
                'course_date' => 'required',
                'category_id' => 'required',
                'image' => 'required',
                'modality_id' => 'required',
                'type_description' => 'required',
            ]
        );

        $timestamp = strtotime($request->get('course_date'));

        $courseNew = AcaCourse::create([
            'status'            => $request->get('status') ? true : false,
            'description'       => $request->get('description'),
            'course_day'        => date("d", $timestamp),
            'course_month'      => date("m", $timestamp),
            'course_year'       => date("Y", $timestamp),
            'category_id'       => $request->get('category_id'),
            'modality_id'       => $request->get('modality_id'),
            'type_description'  => $request->get('type_description'),
            'sector_description' => $request->get('sector_description'),
            'price'                     => $request->get('price') ?? 0,
            'certificate_description'   => trim($request->get('certificate_description')) ?? null,
            'discount'  => $request->get('discount'),
            'discount_applies'  => $request->get('discount_applies')
        ]);

        $path = null;

        $destination = 'uploads/courses';
        $base64Image = $request->get('image');

        if ($base64Image) {
            $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            if (PHP_OS == 'WINNT') {
                $tempFile = tempnam(sys_get_temp_dir(), 'img');
            } else {
                $tempFile = tempnam('/var/www/html/img_temp', 'img');
            }
            file_put_contents($tempFile, $fileData);
            $mime = mime_content_type($tempFile);

            $name = uniqid('', true) . '.' . str_replace('image/', '', $mime);
            $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);

            if ($file) {
                // $original_name = strtolower(trim($file->getClientOriginalName()));
                // $file_name = time() . rand(100, 999) . $original_name;
                $original_name = strtolower(trim($file->getClientOriginalName()));
                $original_name = str_replace(" ", "_", $original_name);
                $extension = $file->getClientOriginalExtension();
                $file_name = time() . rand(100, 999) . '.' . $extension;
                $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                $courseNew->image = $path;
                $courseNew->save();
            }
        }


        return redirect()->route('aca_courses_information', $courseNew->id)
            ->with('message', 'Curso creado con éxito, registrar informacion del curso');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function information($id)
    {
        $course_teachers = AcaTeacherCourse::with('teacher.person')->where('course_id', $id)->get();

        $teachers = AcaTeacher::with('person')->get();

        if (count($teachers) > 0) {
            $teachers = $teachers->toArray();
        }
        if (count($course_teachers) > 0) {
            $course_teachers = $course_teachers->toArray();
        }

        return Inertia::render('Academic::Courses/Information', [
            'brochure' => AcaBrochure::where('course_id', $id)->first(),
            'course' => AcaCourse::find($id),
            'tiny_api_key' => $this->P000010,
            'teachers' => $teachers,
            'course_teachers' => $course_teachers
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $categories = AcaCategoryCourse::all();
        $modalities = AcaModality::all();
        $types = getEnumValues('aca_courses', 'type_description');
        $sectors = getEnumValues('aca_courses', 'sector_description');

        return Inertia::render('Academic::Courses/Edit', [
            'course'        => AcaCourse::find($id),
            'modalities'    => $modalities,
            'categories'    => $categories,
            'types'    => $types,
            'sectors'    => $sectors,
            'P000018' => $this->P000018
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
        $id = $request->get('id');

        $this->validate(
            $request,
            [
                'description' => 'required',
                'course_date' => 'required',
                'category_id' => 'required',
                'modality_id' => 'required',
                'type_description' => 'required',
            ]
        );

        $course = AcaCourse::find($id);
        $timestamp = strtotime($request->get('course_date'));

        //dd($request->get('category_id'));
        $course->status           = $request->get('status') ? true : false;
        $course->description      = $request->get('description');
        $course->course_day       = date("d", $timestamp);
        $course->course_month     = date("m", $timestamp);
        $course->course_year       = date("Y", $timestamp);
        $course->category_id      = $request->get('category_id');
        $course->modality_id       = $request->get('modality_id');
        $course->type_description  = $request->get('type_description');
        $course->sector_description = $request->get('sector_description');
        $course->price                   = $request->get('price') ?? 0;
        $course->certificate_description  = trim($request->get('certificate_description')) ?? null;
        $course->discount = $request->get('discount') ?? 0;
        $course->discount_applies = $request->get('discount_applies') ?? null;

        $destination = 'uploads/courses';
        $base64Image = $request->get('image');

        if ($base64Image) {
            $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            if (PHP_OS == 'WINNT') {
                $tempFile = tempnam(sys_get_temp_dir(), 'img');
            } else {
                $tempFile = tempnam('/var/www/html/img_temp', 'img');
            }
            file_put_contents($tempFile, $fileData);
            $mime = mime_content_type($tempFile);

            $name = uniqid('', true) . '.' . str_replace('image/', '', $mime);
            $file = new UploadedFile(realpath($tempFile), $name, $mime, null, true);

            if ($file) {
                // $original_name = strtolower(trim($file->getClientOriginalName()));
                // $file_name = time() . rand(100, 999) . $original_name;
                $original_name = strtolower(trim($file->getClientOriginalName()));
                $original_name = str_replace(" ", "_", $original_name);
                $extension = $file->getClientOriginalExtension();
                $file_name = time() . rand(100, 999) . '.' . $extension;
                $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);
                $course->image = $path;
            }
        }

        $course->save();
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

            // Verificamos si existe.
            $item = AcaCourse::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            $message =  'Curso eliminado correctamente';
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

    public function getCoursesTeacherNull()
    {
        $courses = [];
        if (Auth::user()->hasAnyRole(['admin', 'Docente', 'Administrador'])) {
            $courses = AcaCourse::whereNull('teacher_id')->get();
        }


        return response()->json([
            'courses' => $courses
        ]);
    }

    public function enrolledStudents($id)
    {
        $course = AcaCourse::find($id);
        $search = request()->input('search');

        $students = AcaCapRegistration::with(['student.person', 'document'])
            ->where('course_id', $id)
            // Aplicamos el filtro solo si existe búsqueda
            ->when($search, function ($query) use ($search) {
                $query->whereHas('student.person', function ($q) use ($search) {
                    $q->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('number', '=', $search);
                });
            })
            ->paginate(20)
            ->through(function ($registration) {
                $registration->checkbox = false;
                // Corregido: Si tiene document_id O sale_note_id se considera enviado (ajusta según tu lógica si es AND u OR)
                $registration->email_send = $registration->document_id || $registration->sale_note_id ? true : false;
                return $registration;
            });

        return Inertia::render('Academic::Courses/EnrolledStudents', [
            'course' => $course,
            'students' => $students,
            'filters' => request()->all()
        ]);
    }

    /**
     * Crear o actualizar examen final del curso
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrCreateCourseExam(Request $request)
    {
        // 1. Validación de los campos recibidos
        $this->validate($request, [
            'course_id'        => 'required',
            'description'      => 'required|string|max:255',
            'date_start'       => 'required|date',
            'date_end'         => 'required|date|after_or_equal:date_start',
            'duration_minutes' => 'required|numeric|min:1',
            'attempts'         => 'required|numeric|min:1',
            'status'           => 'required',
            'answer_key_pdf'   => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // 2. Preparar los datos básicos para la persistencia
        $data = [
            'course_id'        => $request->get('course_id'),
            'description'      => $request->get('description'),
            'date_start'       => $request->get('date_start'),
            'date_end'         => $request->get('date_end'),
            'duration_minutes' => (int) $request->get('duration_minutes'),
            'attempts'         => (int) $request->get('attempts'),
            'status'           => $request->get('status'),
        ];

        // 3. Lógica de subida de archivo personalizada
        if ($request->hasFile('answer_key_pdf')) {
            $file = $request->file('answer_key_pdf');

            // Procesar nombre original
            $original_name = strtolower(trim($file->getClientOriginalName()));
            $original_name = str_replace(" ", "_", $original_name);

            $extension = $file->getClientOriginalExtension();
            $file_name = time() . rand(100, 999) . '.' . $extension;

            $destination = 'uploads/courses/exams';

            // Guardar el archivo con el nombre generado
            $path = Storage::disk('public')->putFileAs($destination, $file, $file_name);

            // Asignar a los campos correspondientes
            $data['file_resolved_name'] = $original_name;
            $data['file_resolved_path'] = $path;
        }

        // 4. Update or Create basado solo en course_id (para examen de curso)
        AcaExam::updateOrCreate(
            [
                'id' => $request->id,
            ],
            $data
        );

    }

    /**
     * Vista de participaciones de estudiantes
     *
     * @param int $courseId ID del curso
     * @return \Inertia\Response
     */
    public function participations()
    {
        $courses = AcaCourse::with([
            'modules.themes.contents' => function ($query) {
                $query->where('is_file', 3); // Solo videoconferencias (Zoom)
            }
        ])->get();
        return Inertia::render('Academic::Courses/StudentParticipations', [
            'courses' => $courses
        ]);
    }

    /**
     * Buscar estudiantes con filtros para participaciones
     *
     * @param Request $request
     * @param int $courseId ID del curso
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchParticipations(Request $request, $courseId)
    {
        $request->validate([
            'module_id' => 'nullable|exists:aca_modules,id',
            'theme_id' => 'nullable|exists:aca_themes,id',
            'content_id' => 'nullable|exists:aca_contents,id',
        ]);

        // Obtener estudiantes del curso
        $registrations = AcaCapRegistration::with(['student.person'])
            ->where('course_id', $courseId)
            ->where('status', true)
            ->get();

        // Obtener participaciones existentes según filtros
        $participationsQuery = AcaStudentParticipation::where('course_id', $courseId);

        if ($request->module_id) {
            $participationsQuery->where('module_id', $request->module_id);
        }

        if ($request->theme_id) {
            $participationsQuery->where('theme_id', $request->theme_id);
        }

        if ($request->content_id) {
            $participationsQuery->where('content_id', $request->content_id);
        }

        $participations = $participationsQuery->get();
        //dd($registrations);
        // Combinar estudiantes con sus participaciones
        $students = $registrations->map(function ($reg) use ($participations, $request, $courseId) {
            $participation = $participations->first(function ($p) use ($reg, $request) {
                return $p->student_id === $reg->student->id;
            });

            // Si no existe participación, verificar asistencia
            $participationScore = null;
            $hasAttendance = false;
            if (!$participation) {
                // Buscar si existe registro de asistencia
                $attendanceQuery = AcaStudentAttendance::where('student_id', $reg->student->id)
                    ->where('course_id', $courseId);

                if ($request->module_id) {
                    $attendanceQuery->where('module_id', $request->module_id);
                }

                if ($request->content_id) {
                    $attendanceQuery->where('content_id', $request->content_id);
                }

                $hasAttendance = $attendanceQuery->exists();

                if ($hasAttendance) {
                    $participationScore = 12; // Nota por haber asistido
                }
            }

            return [
                'id' => $reg->student->id,
                'name' => $reg->student->person ? $reg->student->person->full_name : 'Sin nombre',
                'number' => $reg->student->person ? $reg->student->person->number : 'Sin número',
                'email' => $reg->student->person ? $reg->student->person->email : '',
                'participation' => $participation ? [
                    'id' => $participation->id,
                    'participation_score' => $participation->participation_score,
                    'teacher_comment' => $participation->teacher_comment,
                ] : ($hasAttendance ? [
                    'id' => null,
                    'participation_score' => $participationScore,
                    'teacher_comment' => '',
                ] : null),
            ];
        });

        return response()->json([
            'success' => true,
            'students' => $students,
        ]);
    }

    /**
     * Guardar o actualizar participación de estudiante
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeParticipation(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:aca_students,id',
            'course_id' => 'nullable|exists:aca_courses,id',
            'module_id' => 'nullable|exists:aca_modules,id',
            'theme_id' => 'nullable|exists:aca_themes,id',
            'content_id' => 'nullable|exists:aca_contents,id',
            'participation_score' => 'nullable|numeric|min:0|max:20',
            'teacher_comment' => 'nullable|string',
        ]);

        $existingParticipation = AcaStudentParticipation::where('student_id', $request->student_id)
            ->where('course_id', $request->course_id)
            ->where('module_id', $request->module_id)
            ->where('theme_id', $request->theme_id)
            ->where('content_id', $request->content_id)
            ->first();

        $userId = Auth::user()->id;

        if ($existingParticipation) {
            $existingParticipation->participation_score = $request->participation_score;
            $existingParticipation->teacher_comment = $request->teacher_comment;

            $history = $existingParticipation->edited_by ?? [];
            $history[] = [
                'user_id' => $userId,
                'updated_at' => now()->toISOString(),
            ];
            $existingParticipation->edited_by = $history;

            $existingParticipation->save();

            return response()->json([
                'success' => true,
                'message' => 'Participación actualizada correctamente',
                'participation' => $existingParticipation
            ]);
        }

        $participation = AcaStudentParticipation::create([
            'student_id' => $request->student_id,
            'course_id' => $request->course_id,
            'module_id' => $request->module_id,
            'theme_id' => $request->theme_id,
            'content_id' => $request->content_id,
            'participation_score' => $request->participation_score,
            'teacher_comment' => $request->teacher_comment,
            'created_by' => $userId,
            'edited_by' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participación guardada correctamente',
            'participation' => $participation
        ]);
    }

    /**
     * Guardar todas las participaciones de estudiantes en una sola acción
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAllParticipations(Request $request)
    {
        $request->validate([
            'participations' => 'required|array',
            'participations.*.student_id' => 'required|exists:aca_students,id',
            'participations.*.course_id' => 'nullable|exists:aca_courses,id',
            'participations.*.module_id' => 'nullable|exists:aca_modules,id',
            'participations.*.theme_id' => 'nullable|exists:aca_themes,id',
            'participations.*.content_id' => 'nullable|exists:aca_contents,id',
            'participations.*.participation_score' => 'nullable|numeric|min:0|max:20',
            'participations.*.teacher_comment' => 'nullable|string',
        ]);

        $userId = Auth::user()->id;
        $savedCount = 0;

        foreach ($request->participations as $participationData) {
            $existingParticipation = AcaStudentParticipation::where('student_id', $participationData['student_id'])
                ->where('course_id', $participationData['course_id'])
                ->where('module_id', $participationData['module_id'])
                ->where('theme_id', $participationData['theme_id'])
                ->where('content_id', $participationData['content_id'])
                ->first();

            if ($existingParticipation) {
                $existingParticipation->participation_score = $participationData['participation_score'];
                $existingParticipation->teacher_comment = $participationData['teacher_comment'];

                $history = $existingParticipation->edited_by ?? [];
                $history[] = [
                    'user_id' => $userId,
                    'updated_at' => now()->toISOString(),
                ];
                $existingParticipation->edited_by = $history;
                $existingParticipation->save();
            } else {
                AcaStudentParticipation::create([
                    'student_id' => $participationData['student_id'],
                    'course_id' => $participationData['course_id'],
                    'module_id' => $participationData['module_id'],
                    'theme_id' => $participationData['theme_id'],
                    'content_id' => $participationData['content_id'],
                    'participation_score' => $participationData['participation_score'],
                    'teacher_comment' => $participationData['teacher_comment'],
                    'created_by' => $userId,
                    'edited_by' => null,
                ]);
            }
            $savedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Se guardaron {$savedCount} participaciones correctamente",
        ]);
    }
}
