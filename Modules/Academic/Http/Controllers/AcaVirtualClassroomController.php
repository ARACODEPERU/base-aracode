<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaSchoolArea;
use Modules\Academic\Entities\AcaSchoolSection;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualActivityFile;
use Modules\Academic\Entities\AcaVirtualActivityOption;
use Modules\Academic\Entities\AcaVirtualActivityQuestion;
use Modules\Academic\Entities\AcaVirtualCourse;
use Modules\Academic\Entities\AcaVirtualLesson;
use Modules\Academic\Entities\AcaVirtualQuizAnswer;
use Modules\Academic\Entities\AcaVirtualQuizAttempt;
use Modules\Academic\Entities\AcaVirtualResource;
use Modules\Academic\Entities\AcaVirtualSubmission;
use Modules\Academic\Entities\AcaVirtualUnit;
use Modules\Academic\Services\SchoolContextService;
use Modules\Academic\Services\VirtualQuizGrader;

class AcaVirtualClassroomController extends Controller
{
    public function __construct(
        private SchoolContextService $context,
        private VirtualQuizGrader $grader
    ) {
    }

    public function index(Request $request)
    {
        $school = $this->context->currentSchool();

        if (! $school) {
            return Inertia::render('Academic::School/VirtualClassroom/Index', [
                'school' => null,
                'years' => [],
                'yearId' => null,
                'activeYearId' => null,
                'yearIsActive' => false,
                'courses' => [],
                'canManage' => false,
            ]);
        }

        $years = AcaSchoolYear::where('school_id', $school->id)->orderByDesc('year')->get(['id', 'year', 'status']);
        $activeYear = $years->firstWhere('status', AcaSchoolYear::STATUS_ACTIVE);
        $yearId = (int) ($request->query('year_id') ?: ($activeYear->id ?? ($years->first()->id ?? 0)));

        if (! $yearId) {
            return Inertia::render('Academic::School/VirtualClassroom/Index', [
                'school' => ['id' => $school->id, 'name' => $school->name],
                'years' => $years,
                'yearId' => null,
                'activeYearId' => $activeYear?->id,
                'yearIsActive' => false,
                'courses' => [],
                'canManage' => $this->canManage(),
            ]);
        }

        if ($activeYear && (int) $yearId === (int) $activeYear->id) {
            $this->ensureCoursesForCurrentUser($school->id, (int) $yearId);
        }

        $personId = Auth::user()?->person_id;
        $canManage = $this->canManage();

        $query = AcaVirtualCourse::with(['section.grade.level', 'area', 'teacher'])
            ->where('school_id', $school->id)
            ->where('year_id', $yearId);

        if (! $canManage && $personId) {
            $query->where('teacher_person_id', $personId);
        } elseif (! $canManage && ! $personId) {
            $query->whereRaw('1 = 0');
        }

        $courses = $query->orderBy('id')->get()->map(function (AcaVirtualCourse $course) use ($activeYear, $yearId) {
            $unitsCount = $course->units()->count();
            $yearIsActive = $activeYear && (int) $yearId === (int) $activeYear->id;

            return [
                'id' => $course->id,
                'name' => $course->name ?: trim(($course->area?->name ?? '').' - '.($course->section?->grade?->name ?? '').' '.($course->section?->name ?? '')),
                'area_name' => $course->area?->name,
                'level_name' => $course->section?->grade?->level?->name,
                'grade_name' => $course->section?->grade?->name,
                'section_name' => $course->section?->name,
                'teacher_name' => $course->teacher?->full_name,
                'units_count' => $unitsCount,
                'can_copy' => $yearIsActive && $unitsCount === 0,
            ];
        })->values();

        return Inertia::render('Academic::School/VirtualClassroom/Index', [
            'school' => ['id' => $school->id, 'name' => $school->name],
            'years' => $years,
            'yearId' => $yearId,
            'activeYearId' => $activeYear?->id,
            'yearIsActive' => $activeYear && (int) $yearId === (int) $activeYear->id,
            'courses' => $courses,
            'canManage' => $canManage,
            'canGenerate' => $this->can('aca_virtual_configurar'),
            'canManageUnits' => $this->can('aca_virtual_unidad_gestionar'),
            'sectionsCount' => AcaSchoolSection::where('school_id', $school->id)
                ->where('year_id', $yearId)
                ->where('status', true)
                ->count(),
        ]);
    }

    /**
     * Genera los cursos del año activo a partir de las secciones registradas y
     * las áreas curriculares activas de su nivel. Si el horario ya tiene un
     * docente para esa sección y área, se le asigna; si no, el curso queda sin
     * docente para que el administrador lo complete.
     */
    public function generateCourses(Request $request)
    {
        $school = $this->context->currentSchool();
        abort_if(! $school, 404);

        if (! $this->can('aca_virtual_configurar')) {
            abort(403);
        }

        $activeYear = AcaSchoolYear::where('school_id', $school->id)
            ->where('status', AcaSchoolYear::STATUS_ACTIVE)
            ->first();

        if (! $activeYear) {
            return back()->withErrors(['generate' => 'El colegio no tiene un año escolar activo. Actívalo en Años escolares antes de generar los cursos.']);
        }

        $sections = AcaSchoolSection::with('grade.level')
            ->where('school_id', $school->id)
            ->where('year_id', $activeYear->id)
            ->where('status', true)
            ->get();

        if ($sections->isEmpty()) {
            return back()->withErrors(['generate' => 'Aún no hay secciones registradas para el año '.$activeYear->year.'. Crea la estructura académica antes de generar los cursos.']);
        }

        $areasByLevel = AcaSchoolArea::where('school_id', $school->id)
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('level');

        if ($areasByLevel->isEmpty()) {
            return back()->withErrors(['generate' => 'El colegio no tiene áreas curriculares activas. Regístralas en Áreas Curriculares antes de generar los cursos.']);
        }

        $schedules = DB::table('aca_school_schedules')
            ->where('school_id', $school->id)
            ->where('year_id', $activeYear->id)
            ->where('status', true)
            ->whereNotNull('area_id')
            ->whereNotNull('teacher_person_id')
            ->orderBy('id')
            ->get()
            ->keyBy(fn ($row) => $row->section_id.'-'.$row->area_id);

        $created = 0;
        $existing = 0;
        $withoutArea = 0;

        DB::transaction(function () use ($school, $activeYear, $sections, $areasByLevel, $schedules, &$created, &$existing, &$withoutArea) {
            foreach ($sections as $section) {
                $levelCode = $section->grade?->level?->code;
                $areas = $areasByLevel->get($levelCode, collect());

                if ($areas->isEmpty()) {
                    $withoutArea++;
                    continue;
                }

                foreach ($areas as $area) {
                    $teacherId = $schedules->get($section->id.'-'.$area->id)?->teacher_person_id;

                    $course = AcaVirtualCourse::firstOrCreate(
                        [
                            'school_id' => $school->id,
                            'year_id' => $activeYear->id,
                            'section_id' => $section->id,
                            'area_id' => $area->id,
                        ],
                        [
                            'teacher_person_id' => $teacherId,
                            'status' => true,
                            'sort_order' => $area->sort_order ?? 0,
                        ]
                    );

                    if ($course->wasRecentlyCreated) {
                        $created++;
                        continue;
                    }

                    // Si el curso ya existía sin docente y el horario ya lo define,
                    // se completa la asignación sin tocar el contenido registrado.
                    if (! $course->teacher_person_id && $teacherId) {
                        $course->update(['teacher_person_id' => $teacherId]);
                    }

                    $existing++;
                }
            }
        });

        $message = 'Se generaron '.$created.' curso(s) para el año '.$activeYear->year.'.';

        if ($existing > 0) {
            $message .= ' '.$existing.' ya existían.';
        }
        if ($withoutArea > 0) {
            $message .= ' '.$withoutArea.' sección(es) no tienen áreas activas en su nivel.';
        }

        return back()->with('message', $message);
    }

    public function show(Request $request, int $courseId)
    {
        $school = $this->context->currentSchool();
        $course = AcaVirtualCourse::with([
            'section.grade.level',
            'area',
            'year',
            'teacher',
            'units.lessons',
            'resources.unit',
            'resources.lesson',
            'activities.unit',
            'activities.lesson',
            'activities.files',
            'activities.questions.options',
            'activities.attempts.answers.question',
            'activities.submissions.student.person',
            'activities.submissions.gradedBy',
        ])
            ->where('school_id', $school?->id)
            ->findOrFail($courseId);

        $activeYear = AcaSchoolYear::where('school_id', $school?->id)
            ->where('status', AcaSchoolYear::STATUS_ACTIVE)
            ->first();

        $yearIsActive = $activeYear && (int) $course->year_id === (int) $activeYear->id;

        if (! $this->canAccessCourse($course)) {
            abort(403);
        }

        $canCopy = $yearIsActive && $this->can('aca_virtual_unidad_gestionar') && $course->units()->count() === 0;

        return Inertia::render('Academic::School/VirtualClassroom/Course', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name ?: trim(($course->area?->name ?? '').' - '.($course->section?->grade?->name ?? '').' '.($course->section?->name ?? '')),
                'area_name' => $course->area?->name,
                'section_label' => trim(($course->section?->grade?->name ?? '').' '.($course->section?->name ?? '')),
                'level_name' => $course->section?->grade?->level?->name,
                'teacher_name' => $course->teacher?->full_name,
                'year' => $course->year?->year,
            ],
            'units' => $course->units->map(fn (AcaVirtualUnit $unit) => [
                'id' => $unit->id,
                'title' => $unit->title,
                'purpose' => $unit->purpose,
                'significant_situation' => $unit->significant_situation,
                'competencies' => $unit->competencies,
                'capabilities' => $unit->capabilities,
                'performances' => $unit->performances,
                'duration_hours' => $unit->duration_hours,
                'start_date' => $unit->start_date,
                'end_date' => $unit->end_date,
                'sort_order' => $unit->sort_order,
                'status' => $unit->status,
                'lessons' => $unit->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'purpose' => $lesson->purpose,
                    'intro_activity' => $lesson->intro_activity,
                    'development' => $lesson->development,
                    'closure' => $lesson->closure,
                    'metacognition' => $lesson->metacognition,
                    'evaluation' => $lesson->evaluation,
                    'duration_minutes' => $lesson->duration_minutes,
                    'sort_order' => $lesson->sort_order,
                    'status' => $lesson->status,
                ])->values(),
            ])->values(),
            'resources' => $course->resources->map(fn (AcaVirtualResource $resource) => [
                'id' => $resource->id,
                'type' => $resource->type,
                'title' => $resource->title,
                'url' => $resource->url,
                'content' => $resource->content,
                'unit_id' => $resource->unit_id,
                'unit_title' => $resource->unit?->title,
                'lesson_id' => $resource->lesson_id,
                'lesson_title' => $resource->lesson?->title,
                'mime_type' => $resource->mime_type,
                'size_kb' => $resource->size_kb,
                'visibility' => $resource->visibility,
                'status' => $resource->status,
                'file_url' => $resource->path ? Storage::disk('public')->url($resource->path) : null,
                'file_name' => $resource->path ? basename($resource->path) : null,
            ])->values(),
            'activities' => $course->activities->map(function (AcaVirtualActivity $activity) {
                // Con varios intentos, la revisión del docente usa el último de
                // cada alumno: su nota es la que reemplaza a la anterior.
                $attemptsByStudent = $activity->attempts
                    ->sortByDesc('attempt_number')
                    ->unique('student_id')
                    ->keyBy('student_id');

                return [
                'id' => $activity->id,
                'type' => $activity->type,
                'title' => $activity->title,
                'instructions' => $activity->instructions,
                'unit_id' => $activity->unit_id,
                'unit_title' => $activity->unit?->title,
                'lesson_id' => $activity->lesson_id,
                'lesson_title' => $activity->lesson?->title,
                'open_at' => $activity->open_at?->format('Y-m-d\\TH:i'),
                'close_at' => $activity->close_at?->format('Y-m-d\\TH:i'),
                'allow_late' => $activity->allow_late,
                'attempts_allowed' => max(1, (int) ($activity->attempts_allowed ?? 1)),
                'allow_result_download' => (bool) $activity->allow_result_download,
                'duration_minutes' => max(1, (int) ($activity->duration_minutes ?? 30)),
                'max_score' => $activity->max_score,
                'grading_scale' => $activity->grading_scale,
                'sort_order' => $activity->sort_order,
                'status' => $activity->status,
                'submissions_count' => $activity->submissions->count(),
                'files_count' => $activity->files->count(),
                'files' => $activity->files->map(fn (AcaVirtualActivityFile $file) => [
                    'id' => $file->id,
                    'name' => $file->original_name ?: basename($file->path),
                    'size_kb' => $file->size_kb,
                    'mime_type' => $file->mime_type,
                    'file_url' => Storage::disk('public')->url($file->path),
                ])->values(),
                'questions_count' => $activity->questions->count(),
                'questions_points' => round((float) $activity->questions->sum('points'), 2),
                'auto_graded_count' => $activity->attempts
                    ->where('status', AcaVirtualQuizAttempt::STATUS_FINISHED)
                    ->unique('student_id')
                    ->count(),
                'questions' => $activity->questions->map(fn (AcaVirtualActivityQuestion $question) => [
                    'id' => $question->id,
                    'type' => $question->type,
                    'question' => $question->question,
                    'points' => (float) $question->points,
                    'answer_text' => $question->answer_text,
                    'options' => $question->options->map(fn (AcaVirtualActivityOption $option) => [
                        'id' => $option->id,
                        'text' => $option->text,
                        'is_correct' => (bool) $option->is_correct,
                    ])->values(),
                ])->values(),
                'submissions' => $activity->submissions->map(function (AcaVirtualSubmission $submission) use ($attemptsByStudent) {
                    $attempt = $attemptsByStudent->get($submission->student_id);

                    return [
                        'id' => $submission->id,
                        'attempt_id' => $attempt?->id,
                        'student_name' => $submission->student?->person?->full_name,
                        'student_code' => $submission->student?->student_code,
                        'status' => $submission->status,
                        'content' => $submission->content,
                        'score' => $submission->score,
                        'grade_text' => $submission->grade_text,
                        'feedback' => $submission->feedback,
                        'submitted_at' => $submission->submitted_at?->format('d/m/Y H:i'),
                        'graded_at' => $submission->graded_at?->format('d/m/Y H:i'),
                        'graded_by' => $submission->gradedBy?->full_name,
                        'auto_graded' => $submission->status === 'graded' && ! $submission->graded_by_person_id,
                        'file_url' => $submission->path ? Storage::disk('public')->url($submission->path) : null,
                        'file_name' => $submission->path ? basename($submission->path) : null,
                        // Respuestas abiertas del intento: el docente puede ajustar sus puntos.
                        'open_answers' => $attempt
                            ? $attempt->answers
                                ->filter(fn (AcaVirtualQuizAnswer $answer) => $answer->question?->type === AcaVirtualActivityQuestion::TYPE_SHORT)
                                ->map(fn (AcaVirtualQuizAnswer $answer) => [
                                    'question_id' => $answer->question_id,
                                    'question' => $answer->question?->question,
                                    'max_points' => (float) $answer->question?->points,
                                    'answer_text' => $answer->answer_text,
                                    'points_earned' => (float) $answer->points_earned,
                                    'manually_graded' => (bool) $answer->manually_graded,
                                ])->values()
                            : collect(),
                    ];
                })->values(),
            ];
        })->values(),
            'yearIsActive' => $yearIsActive,
            'canManage' => $this->canManage() && $yearIsActive,
            'canManageUnits' => $yearIsActive && $this->can('aca_virtual_unidad_gestionar'),
            'canManageLessons' => $yearIsActive && $this->can('aca_virtual_sesion_gestionar'),
            'canManageResources' => $yearIsActive && $this->can('aca_virtual_recurso_gestionar'),
            'canManageActivities' => $yearIsActive && $this->can('aca_virtual_actividad_gestionar'),
            'canGrade' => $yearIsActive && $this->can('aca_virtual_actividad_gestionar'),
            'canCopy' => $canCopy,
        ]);
    }

    public function copyFromPrevious(Request $request, int $courseId)
    {
        $school = $this->context->currentSchool();
        $course = AcaVirtualCourse::where('school_id', $school?->id)->findOrFail($courseId);

        if (! $this->can('aca_virtual_unidad_gestionar') || ! $this->canAccessCourse($course)) {
            abort(403);
        }

        $activeYear = AcaSchoolYear::where('school_id', $school?->id)->where('status', AcaSchoolYear::STATUS_ACTIVE)->first();
        if (! $activeYear || (int) $course->year_id !== (int) $activeYear->id) {
            return back()->withErrors(['copy' => 'Solo se puede copiar al año vigente.']);
        }
        if ($course->units()->count() > 0) {
            return back()->withErrors(['copy' => 'Este curso ya tiene contenido. Solo se copia cuando está vacío.']);
        }

        $previousCourse = AcaVirtualCourse::where('school_id', $school->id)
            ->where('section_id', $course->section_id)
            ->where('area_id', $course->area_id)
            ->where('year_id', '!=', $course->year_id)
            ->orderByDesc('year_id')
            ->first();

        if (! $previousCourse) {
            return back()->withErrors(['copy' => 'No se encontró contenido del año anterior para copiar.']);
        }

        DB::transaction(function () use ($course, $previousCourse) {
            $unitsMap = [];
            $lessonsMap = [];

            foreach ($previousCourse->units()->orderBy('sort_order')->get() as $unit) {
                $newUnit = AcaVirtualUnit::create([
                    'course_id' => $course->id,
                    'title' => $unit->title,
                    'purpose' => $unit->purpose,
                    'significant_situation' => $unit->significant_situation,
                    'competencies' => $unit->competencies,
                    'capabilities' => $unit->capabilities,
                    'performances' => $unit->performances,
                    'duration_hours' => $unit->duration_hours,
                    'start_date' => null,
                    'end_date' => null,
                    'sort_order' => $unit->sort_order,
                    'status' => $unit->status,
                ]);
                $unitsMap[$unit->id] = $newUnit->id;

                foreach ($unit->lessons()->orderBy('sort_order')->get() as $lesson) {
                    $newLesson = AcaVirtualLesson::create([
                        'unit_id' => $newUnit->id,
                        'title' => $lesson->title,
                        'purpose' => $lesson->purpose,
                        'intro_activity' => $lesson->intro_activity,
                        'development' => $lesson->development,
                        'closure' => $lesson->closure,
                        'metacognition' => $lesson->metacognition,
                        'evaluation' => $lesson->evaluation,
                        'duration_minutes' => $lesson->duration_minutes,
                        'sort_order' => $lesson->sort_order,
                        'status' => $lesson->status,
                    ]);
                    $lessonsMap[$lesson->id] = $newLesson->id;
                }
            }

            foreach ($previousCourse->resources()->orderBy('sort_order')->get() as $resource) {
                $path = null;

                // Los archivos se copian al nuevo curso para que la copia quede
                // realmente utilizable; si el original ya no existe se omite.
                if ($resource->path && Storage::disk('public')->exists($resource->path)) {
                    $extension = pathinfo($resource->path, PATHINFO_EXTENSION);
                    $destination = 'colegios/'.$course->school_id.'/aula-virtual/curso-'.$course->id.'/'.uniqid('recurso_').($extension ? '.'.$extension : '');

                    if (Storage::disk('public')->copy($resource->path, $destination)) {
                        $path = $destination;
                    }
                }

                AcaVirtualResource::create([
                    'course_id' => $course->id,
                    'unit_id' => $unitsMap[$resource->unit_id] ?? null,
                    'lesson_id' => $lessonsMap[$resource->lesson_id] ?? null,
                    'type' => $resource->type,
                    'title' => $resource->title,
                    'url' => $resource->url,
                    'content' => $resource->content,
                    'path' => $path,
                    'mime_type' => $resource->mime_type,
                    'size_kb' => $resource->size_kb,
                    'visibility' => $resource->visibility,
                    'sort_order' => $resource->sort_order,
                    'status' => $resource->status,
                ]);
            }

            foreach ($previousCourse->activities()->orderBy('sort_order')->get() as $activity) {
                AcaVirtualActivity::create([
                    'course_id' => $course->id,
                    'unit_id' => $unitsMap[$activity->unit_id] ?? null,
                    'lesson_id' => $lessonsMap[$activity->lesson_id] ?? null,
                    'type' => $activity->type,
                    'title' => $activity->title,
                    'instructions' => $activity->instructions,
                    'open_at' => null,
                    'close_at' => null,
                    'allow_late' => $activity->allow_late,
                    'attempts_allowed' => max(1, (int) ($activity->attempts_allowed ?? 1)),
                    'allow_result_download' => (bool) $activity->allow_result_download,
                    'duration_minutes' => max(1, (int) ($activity->duration_minutes ?? 30)),
                    'max_score' => $activity->max_score,
                    'grading_scale' => $activity->grading_scale,
                    'sort_order' => $activity->sort_order,
                    'status' => $activity->status,
                ]);
            }
        });

        return back()->with('message', 'Contenido copiado desde el año anterior correctamente.');
    }

    private function ensureEditable(AcaVirtualCourse $course, string $permission = 'aca_virtual_unidad_gestionar'): void
    {
        $school = $this->context->currentSchool();
        if (! $school || (int) $course->school_id !== (int) $school->id) {
            abort(403);
        }

        $activeYear = AcaSchoolYear::where('school_id', $school->id)->where('status', AcaSchoolYear::STATUS_ACTIVE)->first();
        if (! $activeYear || (int) $course->year_id !== (int) $activeYear->id) {
            abort(403, 'Solo el año vigente admite cambios.');
        }
        if (! $this->can($permission) || ! $this->canAccessCourse($course)) {
            abort(403);
        }
    }

    public function storeUnit(Request $request, int $courseId)
    {
        $course = AcaVirtualCourse::where('school_id', $this->context->currentSchool()?->id)->findOrFail($courseId);
        $this->ensureEditable($course);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'purpose' => 'nullable|string|max:500',
            'significant_situation' => 'nullable|string',
            'competencies' => 'nullable|string',
            'capabilities' => 'nullable|string',
            'performances' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:0|max:99',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|boolean',
        ]);

        $maxOrder = AcaVirtualUnit::where('course_id', $course->id)->max('sort_order');

        AcaVirtualUnit::create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'purpose' => $validated['purpose'] ?? null,
            'significant_situation' => $validated['significant_situation'] ?? null,
            'competencies' => $validated['competencies'] ?? null,
            'capabilities' => $validated['capabilities'] ?? null,
            'performances' => $validated['performances'] ?? null,
            'duration_hours' => $validated['duration_hours'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sort_order' => ($maxOrder ?? -1) + 1,
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('message', 'Unidad didáctica registrada correctamente.');
    }

    public function updateUnit(Request $request, int $unitId)
    {
        $unit = AcaVirtualUnit::with('course')->findOrFail($unitId);
        $this->ensureEditable($unit->course);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'purpose' => 'nullable|string|max:500',
            'significant_situation' => 'nullable|string',
            'competencies' => 'nullable|string',
            'capabilities' => 'nullable|string',
            'performances' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:0|max:99',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|boolean',
        ]);

        $unit->update([
            'title' => $validated['title'],
            'purpose' => $validated['purpose'] ?? null,
            'significant_situation' => $validated['significant_situation'] ?? null,
            'competencies' => $validated['competencies'] ?? null,
            'capabilities' => $validated['capabilities'] ?? null,
            'performances' => $validated['performances'] ?? null,
            'duration_hours' => $validated['duration_hours'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => $request->has('status') ? $request->boolean('status') : $unit->status,
        ]);

        return back()->with('message', 'Unidad didáctica actualizada correctamente.');
    }

    public function destroyUnit(Request $request, int $unitId)
    {
        $unit = AcaVirtualUnit::with('course')->findOrFail($unitId);
        $this->ensureEditable($unit->course);
        $unit->delete();

        return back()->with('message', 'Unidad didáctica eliminada correctamente.');
    }

    public function reorderUnits(Request $request, int $courseId)
    {
        $course = AcaVirtualCourse::where('school_id', $this->context->currentSchool()?->id)->findOrFail($courseId);
        $this->ensureEditable($course);
        $order = $request->input('order', []);
        foreach ($order as $index => $unitId) {
            AcaVirtualUnit::where('course_id', $course->id)->where('id', $unitId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function storeLesson(Request $request, int $unitId)
    {
        $unit = AcaVirtualUnit::with('course')->findOrFail($unitId);
        $this->ensureEditable($unit->course);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'purpose' => 'nullable|string',
            'intro_activity' => 'nullable|string',
            'development' => 'nullable|string',
            'closure' => 'nullable|string',
            'metacognition' => 'nullable|string',
            'evaluation' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:0|max:600',
            'status' => 'nullable|boolean',
        ]);

        $maxOrder = AcaVirtualLesson::where('unit_id', $unit->id)->max('sort_order');

        AcaVirtualLesson::create([
            'unit_id' => $unit->id,
            'title' => $validated['title'],
            'purpose' => $validated['purpose'] ?? null,
            'intro_activity' => $validated['intro_activity'] ?? null,
            'development' => $validated['development'] ?? null,
            'closure' => $validated['closure'] ?? null,
            'metacognition' => $validated['metacognition'] ?? null,
            'evaluation' => $validated['evaluation'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'sort_order' => ($maxOrder ?? -1) + 1,
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('message', 'Sesión de aprendizaje registrada correctamente.');
    }

    public function updateLesson(Request $request, int $lessonId)
    {
        $lesson = AcaVirtualLesson::with('unit.course')->findOrFail($lessonId);
        $this->ensureEditable($lesson->unit->course);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'purpose' => 'nullable|string',
            'intro_activity' => 'nullable|string',
            'development' => 'nullable|string',
            'closure' => 'nullable|string',
            'metacognition' => 'nullable|string',
            'evaluation' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:0|max:600',
            'status' => 'nullable|boolean',
        ]);

        $lesson->update([
            'title' => $validated['title'],
            'purpose' => $validated['purpose'] ?? null,
            'intro_activity' => $validated['intro_activity'] ?? null,
            'development' => $validated['development'] ?? null,
            'closure' => $validated['closure'] ?? null,
            'metacognition' => $validated['metacognition'] ?? null,
            'evaluation' => $validated['evaluation'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'status' => $request->has('status') ? $request->boolean('status') : $lesson->status,
        ]);

        return back()->with('message', 'Sesión de aprendizaje actualizada correctamente.');
    }

    public function destroyLesson(Request $request, int $lessonId)
    {
        $lesson = AcaVirtualLesson::with('unit.course')->findOrFail($lessonId);
        $this->ensureEditable($lesson->unit->course);
        $lesson->delete();

        return back()->with('message', 'Sesión de aprendizaje eliminada correctamente.');
    }

    public function reorderLessons(Request $request, int $unitId)
    {
        $unit = AcaVirtualUnit::with('course')->findOrFail($unitId);
        $this->ensureEditable($unit->course);
        $order = $request->input('order', []);
        foreach ($order as $index => $lessonId) {
            AcaVirtualLesson::where('unit_id', $unit->id)->where('id', $lessonId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /* ------------------------- Recursos del curso ------------------------- */

    public function storeResource(Request $request, int $courseId)
    {
        $course = AcaVirtualCourse::where('school_id', $this->context->currentSchool()?->id)->findOrFail($courseId);
        $this->ensureEditable($course, 'aca_virtual_recurso_gestionar');

        [$unitId, $lessonId] = $this->resolveScope($course, $request);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['file', 'link', 'embed'])],
            'title' => 'required|string|max:200',
            'url' => 'nullable|string|max:2000',
            'content' => 'nullable|string|max:20000',
            'visibility' => 'nullable|in:all,students',
            'status' => 'nullable|boolean',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,png,jpg,jpeg,webp,mp4',
        ]);

        $this->validateResourcePayload($validated, $request);

        $path = null;
        $mimeType = null;
        $sizeKb = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('colegios/'.$course->school_id.'/aula-virtual/curso-'.$course->id, 'public');
            $mimeType = $file->getClientMimeType();
            $sizeKb = (int) ceil($file->getSize() / 1024);
        }

        $maxOrder = AcaVirtualResource::where('course_id', $course->id)->max('sort_order');

        AcaVirtualResource::create([
            'course_id' => $course->id,
            'unit_id' => $unitId,
            'lesson_id' => $lessonId,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'url' => $validated['type'] === 'link' ? ($validated['url'] ?? null) : null,
            'content' => $validated['type'] === 'embed' ? ($validated['content'] ?? null) : null,
            'path' => $path,
            'mime_type' => $mimeType,
            'size_kb' => $sizeKb,
            'visibility' => $validated['visibility'] ?? 'students',
            'sort_order' => ($maxOrder ?? -1) + 1,
            'status' => $request->boolean('status', true),
        ]);

        return back()->with('message', 'Recurso agregado correctamente.');
    }

    public function updateResource(Request $request, int $resourceId)
    {
        $resource = AcaVirtualResource::with('course')->findOrFail($resourceId);
        $course = $resource->course;
        $this->ensureEditable($course, 'aca_virtual_recurso_gestionar');

        [$unitId, $lessonId] = $this->resolveScope($course, $request);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['file', 'link', 'embed'])],
            'title' => 'required|string|max:200',
            'url' => 'nullable|string|max:2000',
            'content' => 'nullable|string|max:20000',
            'visibility' => 'nullable|in:all,students',
            'status' => 'nullable|boolean',
            'file' => 'nullable|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,png,jpg,jpeg,webp,mp4',
        ]);

        $this->validateResourcePayload($validated, $request, $resource);

        $path = $resource->path;
        $mimeType = $resource->mime_type;
        $sizeKb = $resource->size_kb;

        if ($request->hasFile('file')) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            $file = $request->file('file');
            $path = $file->store('colegios/'.$course->school_id.'/aula-virtual/curso-'.$course->id, 'public');
            $mimeType = $file->getClientMimeType();
            $sizeKb = (int) ceil($file->getSize() / 1024);
        } elseif ($validated['type'] !== 'file' && $path) {
            // El recurso dejó de ser un archivo: no se conservan huérfanos en disco.
            Storage::disk('public')->delete($path);
            $path = null;
            $mimeType = null;
            $sizeKb = null;
        }

        $resource->update([
            'unit_id' => $unitId,
            'lesson_id' => $lessonId,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'url' => $validated['type'] === 'link' ? ($validated['url'] ?? null) : null,
            'content' => $validated['type'] === 'embed' ? ($validated['content'] ?? null) : null,
            'path' => $path,
            'mime_type' => $mimeType,
            'size_kb' => $sizeKb,
            'visibility' => $validated['visibility'] ?? $resource->visibility,
            'status' => $request->has('status') ? $request->boolean('status') : $resource->status,
        ]);

        return back()->with('message', 'Recurso actualizado correctamente.');
    }

    public function destroyResource(Request $request, int $resourceId)
    {
        $resource = AcaVirtualResource::with('course')->findOrFail($resourceId);
        $this->ensureEditable($resource->course, 'aca_virtual_recurso_gestionar');

        if ($resource->path) {
            Storage::disk('public')->delete($resource->path);
        }

        $resource->delete();

        return back()->with('message', 'Recurso eliminado correctamente.');
    }

    /* ------------------------ Actividades del curso ----------------------- */

    public function storeActivity(Request $request, int $courseId)
    {
        $course = AcaVirtualCourse::where('school_id', $this->context->currentSchool()?->id)->findOrFail($courseId);
        $this->ensureEditable($course, 'aca_virtual_actividad_gestionar');

        [$unitId, $lessonId] = $this->resolveScope($course, $request);
        $validated = $this->validateActivityPayload($request);

        $maxOrder = AcaVirtualActivity::where('course_id', $course->id)->max('sort_order');

        $activity = AcaVirtualActivity::create([
            'course_id' => $course->id,
            'unit_id' => $unitId,
            'lesson_id' => $lessonId,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'open_at' => $validated['open_at'] ?? null,
            'close_at' => $validated['close_at'] ?? null,
            'allow_late' => $request->boolean('allow_late'),
            'attempts_allowed' => $validated['type'] === 'quiz' ? max(1, (int) ($validated['attempts_allowed'] ?? 1)) : 1,
            'allow_result_download' => $validated['type'] === 'quiz' && $request->boolean('allow_result_download'),
            // La duración solo se usa en evaluaciones; en otros tipos queda el
            // valor por defecto para mantener la columna completa.
            'duration_minutes' => max(1, min(300, (int) ($validated['duration_minutes'] ?? 30))),
            'max_score' => $validated['max_score'] ?? 20,
            'grading_scale' => $validated['grading_scale'] ?? 'numeric',
            'sort_order' => ($maxOrder ?? -1) + 1,
            'status' => $request->boolean('status', true),
        ]);

        $this->syncActivityFiles($request, $activity);

        return back()->with('message', 'Actividad registrada correctamente.');
    }

    public function updateActivity(Request $request, int $activityId)
    {
        $activity = AcaVirtualActivity::with('course')->findOrFail($activityId);
        $this->ensureEditable($activity->course, 'aca_virtual_actividad_gestionar');

        [$unitId, $lessonId] = $this->resolveScope($activity->course, $request);
        $validated = $this->validateActivityPayload($request);

        $this->ensureMaxScoreCoversQuestions($activity, (float) ($validated['max_score'] ?? $activity->max_score));

        $activity->update([
            'unit_id' => $unitId,
            'lesson_id' => $lessonId,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'open_at' => $validated['open_at'] ?? null,
            'close_at' => $validated['close_at'] ?? null,
            'allow_late' => $request->boolean('allow_late'),
            'attempts_allowed' => $validated['type'] === 'quiz' ? max(1, (int) ($validated['attempts_allowed'] ?? 1)) : 1,
            'allow_result_download' => $validated['type'] === 'quiz' && $request->boolean('allow_result_download'),
            // La duración solo se usa en evaluaciones; en otros tipos queda el
            // valor por defecto para mantener la columna completa.
            'duration_minutes' => max(1, min(300, (int) ($validated['duration_minutes'] ?? 30))),
            'max_score' => $validated['max_score'] ?? $activity->max_score,
            'grading_scale' => $validated['grading_scale'] ?? $activity->grading_scale,
            'status' => $request->has('status') ? $request->boolean('status') : $activity->status,
        ]);

        $this->syncActivityFiles($request, $activity);

        return back()->with('message', 'Actividad actualizada correctamente.');
    }

    public function destroyActivity(Request $request, int $activityId)
    {
        $activity = AcaVirtualActivity::with(['course', 'files'])->findOrFail($activityId);
        $this->ensureEditable($activity->course, 'aca_virtual_actividad_gestionar');

        foreach ($activity->files as $file) {
            Storage::disk('public')->delete($file->path);
        }

        $activity->delete();

        return back()->with('message', 'Actividad eliminada correctamente.');
    }

    public function gradeSubmission(Request $request, int $submissionId)
    {
        $submission = AcaVirtualSubmission::with('activity.course')->findOrFail($submissionId);
        $activity = $submission->activity;
        $this->ensureEditable($activity->course, 'aca_virtual_actividad_gestionar');

        $validated = $request->validate([
            'score' => 'nullable|numeric|min:0|max:'.(float) $activity->max_score,
            'grade_text' => 'nullable|string|max:50',
            'feedback' => 'nullable|string|max:2000',
        ]);

        if ($activity->grading_scale === 'qualitative' && blank($validated['grade_text'] ?? null)) {
            throw ValidationException::withMessages(['grade_text' => 'Ingresa la calificación literal del estudiante.']);
        }

        if ($activity->grading_scale === 'numeric' && ($validated['score'] ?? null) === null) {
            throw ValidationException::withMessages(['score' => 'Ingresa la nota del estudiante.']);
        }

        $submission->update([
            'score' => $validated['score'] ?? null,
            'grade_text' => $validated['grade_text'] ?? null,
            'feedback' => $validated['feedback'] ?? null,
            'status' => 'graded',
            'graded_at' => now(),
            'graded_by_person_id' => Auth::user()?->person_id,
        ]);

        return back()->with('message', 'Entrega calificada correctamente.');
    }

    /**
     * Revisión manual de las respuestas abiertas de una evaluación: el docente
     * asigna los puntos de cada una y el sistema recalcula la nota del alumno.
     */
    public function reviewAttempt(Request $request, int $attemptId)
    {
        $attempt = AcaVirtualQuizAttempt::with('activity.course')->findOrFail($attemptId);
        $activity = $attempt->activity;
        $this->ensureEditable($activity->course, 'aca_virtual_actividad_gestionar');

        if ($attempt->status !== AcaVirtualQuizAttempt::STATUS_FINISHED) {
            throw ValidationException::withMessages([
                'answers' => 'El estudiante todavía no terminó la evaluación.',
            ]);
        }

        $validated = $request->validate([
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer',
            'answers.*.points_earned' => 'required|numeric|min:0',
        ], [
            'answers.*.points_earned.required' => 'Indica los puntos de cada respuesta abierta.',
        ]);

        $this->grader->reviewOpenAnswers($attempt, Auth::user()?->person_id, $validated['answers']);

        return back()->with('message', 'Revisión guardada y nota recalculada.');
    }

    /**
     * Valida que la unidad y la sesión elegidas pertenezcan al curso. Evita que
     * un formulario manipulado adjunte contenido a cursos de otro colegio.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveScope(AcaVirtualCourse $course, Request $request): array
    {
        $unitId = $request->filled('unit_id') ? (int) $request->input('unit_id') : null;
        $lessonId = $request->filled('lesson_id') ? (int) $request->input('lesson_id') : null;

        if ($unitId && ! AcaVirtualUnit::where('course_id', $course->id)->whereKey($unitId)->exists()) {
            throw ValidationException::withMessages(['unit_id' => 'La unidad elegida no pertenece a este curso.']);
        }

        if ($lessonId) {
            $lesson = AcaVirtualLesson::with('unit')->find($lessonId);

            if (! $lesson || (int) $lesson->unit?->course_id !== (int) $course->id) {
                throw ValidationException::withMessages(['lesson_id' => 'La sesión elegida no pertenece a este curso.']);
            }

            $unitId = $unitId ?: $lesson->unit_id;
        }

        return [$unitId, $lessonId];
    }

    private function validateResourcePayload(array $validated, Request $request, ?AcaVirtualResource $resource = null): void
    {
        if ($validated['type'] === 'link' && blank($validated['url'] ?? null)) {
            throw ValidationException::withMessages(['url' => 'Ingresa el enlace del recurso.']);
        }

        if ($validated['type'] === 'embed' && blank($validated['content'] ?? null)) {
            throw ValidationException::withMessages(['content' => 'Escribe el contenido de la página.']);
        }

        if ($validated['type'] === 'file' && ! $request->hasFile('file') && ! $resource?->path) {
            throw ValidationException::withMessages(['file' => 'Selecciona el archivo que deseas compartir.']);
        }
    }

    /**
     * La nota de una evaluación no puede quedar por debajo de los puntos que ya
     * suman sus preguntas; de lo contrario la nota final superaría el máximo.
     */
    private function ensureMaxScoreCoversQuestions(AcaVirtualActivity $activity, float $maxScore): void
    {
        $assigned = round((float) $activity->questions()->sum('points'), 2);

        if ($assigned > 0 && round($maxScore, 2) < $assigned) {
            throw ValidationException::withMessages([
                'max_score' => sprintf(
                    'Las preguntas de esta evaluación ya suman %s puntos. La nota máxima no puede ser menor: ajusta primero las preguntas.',
                    rtrim(rtrim(number_format($assigned, 2, '.', ''), '0'), '.')
                ),
            ]);
        }
    }

    private function validateActivityPayload(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['task', 'quiz', 'forum'])],
            'title' => 'required|string|max:200',
            'instructions' => 'nullable|string|max:20000',
            'open_at' => 'nullable|date',
            'close_at' => 'nullable|date|after_or_equal:open_at',
            'allow_late' => 'nullable|boolean',
            'attempts_allowed' => 'nullable|integer|min:1|max:20',
            'allow_result_download' => 'nullable|boolean',
            'duration_minutes' => 'nullable|integer|min:1|max:300',
            'max_score' => 'nullable|numeric|min:0|max:100',
            'grading_scale' => ['nullable', Rule::in(['numeric', 'qualitative'])],
            'status' => 'nullable|boolean',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,png,jpg,jpeg,webp',
            'remove_file_ids' => 'nullable|array',
            'remove_file_ids.*' => 'integer',
        ], [
            'files.*.mimes' => 'El material debe ser PDF, Word, PowerPoint, Excel, ZIP o imagen.',
            'files.*.max' => 'Cada archivo puede pesar como máximo 10 MB.',
        ]);
    }

    /**
     * Guarda el material de apoyo de la actividad y elimina los adjuntos que el
     * docente marcó para quitar. Los archivos quedan en el disco público del
     * colegio, igual que los recursos del curso.
     */
    private function syncActivityFiles(Request $request, AcaVirtualActivity $activity): void
    {
        $course = $activity->course;

        $removeIds = collect($request->input('remove_file_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($removeIds->isNotEmpty()) {
            $files = AcaVirtualActivityFile::where('activity_id', $activity->id)
                ->whereIn('id', $removeIds)
                ->get();

            foreach ($files as $file) {
                Storage::disk('public')->delete($file->path);
                $file->delete();
            }
        }

        $uploads = $request->file('files', []);
        $uploads = is_array($uploads) ? $uploads : [$uploads];

        foreach ($uploads as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store(
                'colegios/'.$course->school_id.'/aula-virtual/curso-'.$course->id.'/actividades',
                'public'
            );

            AcaVirtualActivityFile::create([
                'activity_id' => $activity->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size_kb' => (int) ceil($file->getSize() / 1024),
            ]);
        }
    }

    private function can(string $permission): bool
    {
        return (bool) Auth::user()?->can($permission);
    }

    private function canManage(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->can('aca_virtual_configurar')
            || $user->can('aca_virtual_unidad_gestionar')
            || $user->can('aca_virtual_sesion_gestionar')
            || $user->can('aca_virtual_recurso_gestionar')
            || $user->can('aca_virtual_actividad_gestionar');
    }

    private function canAccessCourse(AcaVirtualCourse $course): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->can('aca_virtual_configurar')) {
            return true;
        }
        $personId = $user->person_id;

        return $personId && (int) $course->teacher_person_id === (int) $personId;
    }

    private function ensureCoursesForCurrentUser(int $schoolId, int $yearId): void
    {
        $user = Auth::user();
        $personId = $user?->person_id;
        if (! $personId) {
            return;
        }

        $assignments = DB::table('aca_school_schedules')
            ->join('aca_school_sections', 'aca_school_sections.id', '=', 'aca_school_schedules.section_id')
            ->join('aca_school_areas', 'aca_school_areas.id', '=', 'aca_school_schedules.area_id')
            ->where('aca_school_schedules.school_id', $schoolId)
            ->where('aca_school_schedules.year_id', $yearId)
            ->where('aca_school_schedules.teacher_person_id', $personId)
            ->where('aca_school_schedules.status', true)
            ->select('aca_school_schedules.section_id', 'aca_school_schedules.area_id')
            ->distinct()
            ->get();

        foreach ($assignments as $assignment) {
            AcaVirtualCourse::firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'year_id' => $yearId,
                    'section_id' => $assignment->section_id,
                    'area_id' => $assignment->area_id,
                ],
                [
                    'teacher_person_id' => $personId,
                    'status' => true,
                    'sort_order' => 0,
                ]
            );
        }
    }
}
