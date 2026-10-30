<?php

namespace Modules\Academic\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolLevel;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualActivityQuestion;
use Modules\Academic\Entities\AcaVirtualCourse;
use Modules\Academic\Entities\AcaVirtualQuizAttempt;
use Modules\Academic\Entities\AcaVirtualResource;
use Modules\Academic\Entities\AcaVirtualSubmission;
use Modules\Academic\Services\SchoolContextService;
use Modules\Academic\Services\StudentPortalAuth;
use Modules\Academic\Services\StudentQuizService;
use Throwable;

/**
 * Portal del alumno del aula virtual de Inicial y Primaria.
 *
 * El acceso reutiliza la tabla de usuarios del sistema, pero solo deja entrar
 * a quien tiene una matrícula ACTIVA del año escolar vigente en uno de los
 * niveles habilitados. Los cursos, unidades y sesiones salen del aula virtual
 * que configura el administrador; el alumno solo puede abrir los de su sección.
 *
 * Los dos niveles comparten las mismas vistas: al iniciar sesión se resuelve
 * el nivel de la matrícula y las pantallas lo reciben en
 * $portal['grade']['level']. Secundaria tendrá sus propias vistas más adelante.
 */
class ColegioAulaController extends ColegioPublicController
{
    /**
     * Niveles que puede atender cada portal. El mismo controlador sirve las
     * vistas infantiles de Inicial/Primaria y las juveniles de Secundaria; la
     * ruta desde la que se entra decide qué grupo de niveles se valida.
     *
     * @var array<string, array<int, string>>
     */
    private const LEVELS_BY_SCOPE = [
        'aula' => [AcaSchoolLevel::INICIAL, AcaSchoolLevel::PRIMARIA],
        'secundaria' => [AcaSchoolLevel::SECUNDARIA],
    ];

    /**
     * Niveles que pueden iniciar sesión desde el login compartido.
     *
     * @var array<int, string>
     */
    private const LOGIN_LEVELS = [
        AcaSchoolLevel::INICIAL,
        AcaSchoolLevel::PRIMARIA,
        AcaSchoolLevel::SECUNDARIA,
    ];

    public function __construct(
        private SchoolContextService $context,
        private StudentPortalAuth $studentAuth,
        private StudentQuizService $quizService
    ) {
    }

    /**
     * Pantalla de bienvenida (GET). Si ya hay una sesión válida, se salta el
     * formulario y se dirige al panel que corresponde al nivel del alumno.
     */
    public function create()
    {
        if (Auth::check()) {
            $membership = $this->studentAuth->membership(Auth::user());
            $target = $this->dashboardForLevel($membership['level']?->code ?? null);

            if ($target) {
                return redirect()->route($target);
            }
        }

        return view($this->scope() === 'secundaria' ? 'colegio.secundaria.login' : 'colegio.aula.login', [
            'school' => $this->school(),
            'year' => now()->year,
            'openLogin' => session()->has('errors') || session()->has('status'),
        ]);
    }

    /**
     * Valida credenciales y la matricula antes de abrir la sesion.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'codigo' => ['required', 'string', 'max:60'],
                'clave' => ['required', 'string'],
            ],
            [
                'codigo.required' => 'Escribe tu código de alumno.',
                'clave.required' => 'Escribe tu contraseña.',
            ]
        );

        // Código de alumno + contraseña (DNI la primera vez). El servicio
        // resuelve la matrícula activa y el nivel al que pertenece. El acceso
        // de Secundaria solo admite ese nivel; el login compartido admite los
        // tres y luego dirige a cada alumno a sus vistas.
        $result = $this->studentAuth->authenticate(
            $data['codigo'],
            $data['clave'],
            $this->scope() === 'secundaria' ? [AcaSchoolLevel::SECUNDARIA] : self::LOGIN_LEVELS
        );

        if (isset($result['error'])) {
            return back()
                ->withInput($request->only('codigo'))
                ->withErrors(['codigo' => $this->levelMessage($result)]);
        }

        $target = $this->dashboardForLevel($result['level']?->code ?? null);

        if (! $target) {
            return back()
                ->withInput($request->only('codigo'))
                ->withErrors(['codigo' => 'El aula virtual de tu nivel todavía no está habilitada.']);
        }

        Auth::login($result['user'], $request->boolean('recordar'));
        $request->session()->regenerate();

        return redirect()
            ->route($target)
            ->with('status', '¡Bienvenido, ' . $this->firstName($result['student']) . '!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route($this->loginRoute())
            ->with('status', 'Cerraste sesión. ¡Te esperamos pronto!');
    }

    /**
     * Dashboard principal del alumno: sus cursos con unidades y sesiones.
     */
    public function dashboard()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view($this->viewFor('dashboard'), [
            'portal' => $portal,
            'courses' => $portal['courses'],
            'messages' => $this->bubbleMessages($portal),
            'dockActive' => 'inicio',
        ]);
    }

    /**
     * Contenido de un curso: unidades, sesiones, materiales y actividades.
     */
    public function course(int $course)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $model = $portal['courses']->firstWhere('id', $course);

        // Solo los cursos de la seccion del alumno son visibles, aunque
        // escriba el id de otro a mano.
        abort_if(! $model, 404);

        $model->load([
            'units' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'units.lessons' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'units.lessons.resources' => fn ($query) => $query->where('status', true)
                ->whereIn('visibility', ['all', 'students'])
                ->orderBy('sort_order')
                ->orderBy('id'),
            'units.lessons.activities' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'units.lessons.activities.questions',
            'units.resources' => fn ($query) => $query->where('status', true)
                ->whereIn('visibility', ['all', 'students'])
                ->orderBy('sort_order')
                ->orderBy('id'),
            'units.activities' => fn ($query) => $query->where('status', true)->orderBy('sort_order')->orderBy('id'),
            'units.activities.questions',
            'activities' => fn ($query) => $query->where('status', true)->whereNull('unit_id')->orderBy('sort_order')->orderBy('id'),
            'activities.questions',
            'resources' => fn ($query) => $query->whereNull('unit_id')
                ->where('status', true)
                ->whereIn('visibility', ['all', 'students'])
                ->orderBy('sort_order'),
        ]);

        $student = $this->studentForPortal($portal);
        $activities = $model->units
            ->flatMap(fn ($unit) => $unit->activities)
            ->merge($model->units->flatMap(fn ($unit) => $unit->lessons->flatMap(fn ($lesson) => $lesson->activities)))
            ->merge($model->activities)
            ->unique('id')
            ->values();

        return view($this->viewFor('curso'), [
            'portal' => $portal,
            'course' => $model,
            // Lista unica del curso: alimenta el modal de materiales de apoyo.
            'materials' => $this->courseMaterials($model),
            // Estado real de evaluaciones y tareas del alumno: intentos, nota
            // vigente y entrega, sin exponer respuestas correctas.
            'activityStates' => $this->activityStates($student, $activities),
            'messages' => $this->bubbleMessages($portal),
            'dockActive' => 'cursos',
        ]);
    }

    /**
     * Muestra un material dentro del aula (imagen o PDF en el visor del modal).
     */
    public function viewMaterial(int $resource)
    {
        return $this->sendMaterial($resource, false);
    }

    /**
     * Descarga un material con un nombre legible para el alumno.
     */
    public function downloadMaterial(int $resource)
    {
        return $this->sendMaterial($resource, true);
    }

    /**
     * Entrega el archivo de un material solo si pertenece a un curso del alumno.
     */
    private function sendMaterial(int $resourceId, bool $download)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $resource = AcaVirtualResource::query()
            ->whereIn('course_id', $portal['courses']->pluck('id'))
            ->where('status', true)
            ->whereIn('visibility', ['all', 'students'])
            ->find($resourceId);

        $disk = Storage::disk('public');

        abort_if(! $resource || ! $resource->path || ! $disk->exists($resource->path), 404);

        $path = $disk->path($resource->path);
        $extension = mb_strtolower(pathinfo($resource->path, PATHINFO_EXTENSION));
        $name = Str::slug($resource->title ?: 'material') . ($extension ? '.' . $extension : '');

        // Solo se sirven dentro del visor los formatos que el navegador puede
        // mostrar; el resto se descarga para evitar contenido activo inline.
        $inlineMimes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
        ];

        if ($download || ! isset($inlineMimes[$extension])) {
            return response()->download($path, $name, ['X-Content-Type-Options' => 'nosniff']);
        }

        return response()->file($path, [
            'Content-Type' => $inlineMimes[$extension],
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Formulario para rendir una evaluación. No incluye respuestas correctas:
     * el alumno solo ve las preguntas y sus alternativas.
     */
    public function quiz(int $activityId)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $student = $this->studentForPortal($portal);
        $activity = $this->resolvePortalActivity($portal, $activityId, 'quiz');
        $course = $portal['courses']->firstWhere('id', $activity->course_id);

        // Si el alumno dejó un intento abierto y el tiempo ya terminó, se
        // califica y se cierra antes de mostrar cualquier pregunta.
        $active = $this->quizService->activeAttempt($activity, $student);

        if ($active && $this->quizService->expireIfNeeded($active)) {
            return redirect()
                ->route('colegio.' . $this->scope() . '.quiz.result', $activity->id)
                ->with('quiz_status', 'El tiempo de tu evaluación terminó y se cerró automáticamente.');
        }

        $summary = $this->quizService->summary($activity, $student);
        $active = $summary['active'];

        if ($summary['error']) {
            return redirect()
                ->route('colegio.' . $this->scope() . '.course', $activity->course_id)
                ->withErrors(['evaluacion' => $summary['error']]);
        }

        if (! $active && $summary['remaining'] <= 0) {
            return redirect()->route('colegio.' . $this->scope() . '.quiz.result', $activity->id);
        }

        if ($active) {
            // El orden se fija al iniciar el intento para que no cambie al
            // recargar y para que cada alumno reciba uno distinto.
            $questions = $this->quizService->orderedQuestions($activity, $active);

            foreach ($questions as $question) {
                $question->setRelation('options', $this->quizService->orderedOptions($question, $active));
            }

            $activity->setRelation('questions', $questions);
        }

        return view($this->viewFor('evaluacion'), [
            'portal' => $portal,
            'course' => $course,
            'activity' => $activity,
            'summary' => $summary,
            'activeAttempt' => $active,
            'remainingSeconds' => $this->quizService->remainingSeconds($active),
            'totalSeconds' => $this->quizService->totalSeconds($active),
            'messages' => $this->bubbleMessages($portal),
            'dockActive' => 'cursos',
        ]);
    }

    /**
     * Inicia el intento desde el aviso bloqueado. El tiempo corre desde este
     * momento y termina en la hora de cierre del examen, si es anterior.
     */
    public function startQuiz(int $activityId)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $student = $this->studentForPortal($portal);
        $activity = $this->resolvePortalActivity($portal, $activityId, 'quiz');
        $summary = $this->quizService->summary($activity, $student);

        if ($summary['error']) {
            return redirect()
                ->route('colegio.' . $this->scope() . '.quiz', $activity->id)
                ->withErrors(['evaluacion' => $summary['error']]);
        }

        if ($summary['remaining'] <= 0 && ! $summary['in_progress']) {
            return redirect()->route('colegio.' . $this->scope() . '.quiz.result', $activity->id);
        }

        try {
            $this->quizService->startAttempt($activity, $student);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('colegio.' . $this->scope() . '.quiz', $activity->id)
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('colegio.' . $this->scope() . '.quiz', $activity->id)
            ->with('quiz_status', '¡Examen iniciado! El tiempo ya está corriendo.');
    }

    /**
     * Califica el intento enviado y lleva al alumno a su nota. El historial
     * conserva los intentos anteriores, pero solo la última nota es la vigente.
     */
    public function submitQuiz(Request $request, int $activityId)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $student = $this->studentForPortal($portal);
        $activity = $this->resolvePortalActivity($portal, $activityId, 'quiz');

        $validated = $request->validate([
            'answers' => 'nullable|array',
            'answers.*' => 'array',
            'answers.*.option_ids' => 'nullable|array',
            'answers.*.option_ids.*' => 'integer',
            'answers.*.answer_text' => 'nullable|string|max:2000',
            'auto_submitted' => 'nullable|boolean',
        ]);

        $attempt = $this->quizService->activeAttempt($activity, $student);

        // Solo se califica el intento que el alumno abrió desde el botón
        // «Iniciar examen». Así un envío duplicado o de una pestaña vieja no
        // crea un intento nuevo ni reemplaza la nota por accidente.
        if (! $attempt) {
            $summary = $this->quizService->summary($activity, $student);

            if ($summary['latest']) {
                return redirect()
                    ->route('colegio.' . $this->scope() . '.quiz.result', $activity->id)
                    ->with('quiz_status', 'Esta evaluación ya fue enviada. Esta es tu nota vigente.');
            }

            return redirect()
                ->route('colegio.' . $this->scope() . '.quiz', $activity->id)
                ->withErrors(['evaluacion' => 'Inicia el examen antes de enviar tus respuestas.']);
        }

        $answers = collect($validated['answers'] ?? [])
            ->map(fn ($row, $questionId) => [
                'question_id' => (int) $questionId,
                'option_ids' => $row['option_ids'] ?? [],
                'answer_text' => $row['answer_text'] ?? null,
            ])
            ->values()
            ->all();

        // El servidor también cierra el examen: pasado el margen de red, las
        // respuestas no se aceptan y el intento se califica como cerrado.
        $late = $attempt->expires_at !== null
            && now()->greaterThan($attempt->expires_at->copy()->addSeconds(StudentQuizService::SUBMIT_GRACE_SECONDS));

        if ($late) {
            $answers = [];
        }

        try {
            $this->quizService->gradeAttempt($attempt, $answers);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $message = $late || $request->boolean('auto_submitted')
            ? 'El tiempo de tu evaluación terminó y se cerró automáticamente.'
            : 'Tu evaluación fue enviada. Esta es tu nota.';

        return redirect()
            ->route('colegio.' . $this->scope() . '.quiz.result', $activity->id)
            ->with('quiz_status', $message);
    }

    /**
     * Nota vigente del último intento, con la opción de volver a intentarlo si
     * todavía quedan intentos habilitados por el docente.
     */
    public function quizResult(int $activityId)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $student = $this->studentForPortal($portal);
        $activity = $this->resolvePortalActivity($portal, $activityId, 'quiz');
        $active = $this->quizService->activeAttempt($activity, $student);

        if ($active) {
            $this->quizService->expireIfNeeded($active);
        }

        $summary = $this->quizService->summary($activity, $student);

        if (! $summary['latest']) {
            return redirect()->route('colegio.' . $this->scope() . '.quiz', $activity->id);
        }

        return view($this->viewFor('resultado'), [
            'portal' => $portal,
            'course' => $portal['courses']->firstWhere('id', $activity->course_id),
            'activity' => $activity,
            'summary' => $summary,
            'gradeLabel' => $this->quizService->gradeLabel($activity, $summary),
            'messages' => $this->bubbleMessages($portal),
            'dockActive' => 'cursos',
        ]);
    }

    /**
     * PDF con las respuestas del alumno y su nota. Solo se entrega cuando el
     * docente habilitó la descarga; nunca incluye las respuestas correctas.
     */
    public function downloadQuizResult(int $activityId)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $student = $this->studentForPortal($portal);
        $activity = $this->resolvePortalActivity($portal, $activityId, 'quiz');
        $summary = $this->quizService->summary($activity, $student);

        abort_unless($activity->allow_result_download && $summary['latest'], 403);

        $rows = $summary['latest']->answers()
            ->with(['question', 'question.options'])
            ->get()
            ->sortBy(fn ($answer) => $answer->question?->sort_order ?? 0)
            ->map(function ($answer) {
                $options = $answer->question?->options->keyBy('id') ?? collect();
                $selected = collect($answer->option_ids ?? [])
                    ->map(fn ($id) => $options->get((int) $id)?->text)
                    ->filter()
                    ->values();

                return [
                    'question' => $answer->question?->question,
                    'answer' => $answer->answer_text
                        ? $answer->answer_text
                        : $selected->implode(', '),
                ];
            })
            ->values();

        $pdf = Pdf::loadView('colegio.pdf.examen-resuelto', [
            'portal' => $portal,
            'activity' => $activity,
            'rows' => $rows,
            'gradeLabel' => $this->quizService->gradeLabel($activity, $summary),
            'finishedAt' => $summary['finished_at'],
        ])->setPaper('a4');

        return $pdf->download(Str::slug($activity->title ?: 'evaluacion') . '.pdf');
    }

    /**
     * Entrega de una tarea: el alumno sube un PDF y puede reemplazarlo mientras
     * el docente no la haya calificado.
     */
    public function submitTask(Request $request, int $activityId)
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $student = $this->studentForPortal($portal);
        $activity = $this->resolvePortalActivity($portal, $activityId, 'task');
        $error = $this->taskAvailability($activity);

        if ($error) {
            return back()->withErrors(['tarea' => $error]);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ], [
            'file.required' => 'Selecciona el PDF de tu tarea.',
            'file.mimes' => 'La tarea debe ser un archivo PDF.',
            'file.max' => 'El PDF puede pesar como máximo 10 MB.',
        ]);

        $submission = AcaVirtualSubmission::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->first();

        if ($submission?->status === 'graded') {
            return back()->withErrors([
                'tarea' => 'Tu tarea ya fue calificada. Si necesitas corregirla, avísale a tu docente.',
            ]);
        }

        $file = $request->file('file');
        $oldPath = $submission?->path;
        $path = $file->store(
            'colegios/' . $activity->course->school_id . '/aula-virtual/curso-' . $activity->course_id . '/entregas',
            'public'
        );

        DB::transaction(function () use ($activity, $student, $file, $path, $submission, $oldPath) {
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            AcaVirtualSubmission::updateOrCreate(
                ['activity_id' => $activity->id, 'student_id' => $student->id],
                [
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size_kb' => (int) ceil($file->getSize() / 1024),
                    'status' => 'submitted',
                    'score' => null,
                    'grade_text' => null,
                    'feedback' => null,
                    'submitted_at' => now(),
                    'graded_at' => null,
                    'graded_by_person_id' => null,
                ]
            );
        });

        return redirect()
            ->route('colegio.' . $this->scope() . '.course', $activity->course_id)
            ->with('task_status', 'Tu tarea se entregó correctamente. Tu docente la revisará.');
    }

    /**
     * Estado de las evaluaciones y tareas visibles en el curso.
     *
     * @param  \Illuminate\Support\Collection<int, AcaVirtualActivity>  $activities
     * @return array<int, array<string, mixed>>
     */
    private function activityStates(AcaSchoolStudent $student, $activities): array
    {
        $ids = $activities->pluck('id');

        $attempts = AcaVirtualQuizAttempt::where('student_id', $student->id)
            ->whereIn('activity_id', $ids)
            ->orderBy('attempt_number')
            ->get()
            ->groupBy('activity_id');

        $submissions = AcaVirtualSubmission::where('student_id', $student->id)
            ->whereIn('activity_id', $ids)
            ->get()
            ->keyBy('activity_id');

        $states = [];

        foreach ($activities as $activity) {
            $submission = $submissions->get($activity->id);

            if ($activity->type === 'quiz') {
                $quizAttempts = $attempts->get($activity->id) ?? collect();
                $finished = $quizAttempts
                    ->where('status', AcaVirtualQuizAttempt::STATUS_FINISHED)
                    ->sortBy('attempt_number')
                    ->values();
                $active = $quizAttempts
                    ->where('status', AcaVirtualQuizAttempt::STATUS_IN_PROGRESS)
                    ->sortByDesc('attempt_number')
                    ->first();
                $active = $active && ! $active->isExpired() ? $active : null;
                $allowed = max(1, (int) ($activity->attempts_allowed ?? 1));
                $error = $this->quizService->availabilityError($activity);

                $states[$activity->id] = [
                    'type' => 'quiz',
                    'allowed' => $allowed,
                    'used' => $finished->count(),
                    'remaining' => max(0, $allowed - $quizAttempts->count()),
                    'latest' => $finished->last(),
                    'score' => $finished->last()?->score !== null ? (float) $finished->last()->score : null,
                    'grade_text' => $submission?->grade_text,
                    'feedback' => $submission?->feedback,
                    'error' => $error,
                    'in_progress' => $active !== null,
                    'can_take' => $error === null && ($active !== null || $finished->count() < $allowed),
                    'can_download' => (bool) $activity->allow_result_download && $finished->isNotEmpty(),
                ];

                continue;
            }

            $error = $activity->type === 'task' ? $this->taskAvailability($activity) : null;

            $states[$activity->id] = [
                'type' => $activity->type,
                'submission' => $submission,
                'error' => $error,
                'can_submit' => $activity->type === 'task'
                    && $error === null
                    && $submission?->status !== 'graded',
            ];
        }

        return $states;
    }

    private function taskAvailability(AcaVirtualActivity $activity): ?string
    {
        $now = now();

        if ($activity->open_at && $now->lt($activity->open_at)) {
            return 'La tarea estará disponible el ' . $activity->open_at->format('d/m/Y H:i') . '.';
        }

        if ($activity->close_at && $now->gt($activity->close_at) && ! $activity->allow_late) {
            return 'El plazo para entregar esta tarea terminó.';
        }

        return null;
    }

    /**
     * Carga una actividad publicada de un curso del alumno y comprueba que el
     * nivel pueda usar evaluaciones y entregas.
     */
    private function resolvePortalActivity(array $portal, int $activityId, ?string $type = null): AcaVirtualActivity
    {
        abort_unless(
            in_array($portal['grade']['level_code'], [AcaSchoolLevel::PRIMARIA, AcaSchoolLevel::SECUNDARIA], true),
            404
        );

        $activity = AcaVirtualActivity::with(['course', 'questions.options'])
            ->whereIn('course_id', $portal['courses']->pluck('id'))
            ->where('status', true)
            ->findOrFail($activityId);

        if ($type) {
            abort_unless($activity->type === $type, 404);
        }

        return $activity;
    }

    private function studentForPortal(array $portal): AcaSchoolStudent
    {
        $schoolId = $this->context->currentSchool()?->id;

        return AcaSchoolStudent::where('school_id', $schoolId)
            ->where('student_code', $portal['student']['code'])
            ->firstOrFail();
    }

    /**
     * Zona de juegos. Cinco tarjetas abren su juego real y el resto sigue
     * marcado como proximamente.
     */
    public function games()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.aula.juegos', [
            'portal' => $portal,
            'messages' => $this->bubbleMessages($portal),
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego de rompecabezas. La partida corre en el navegador; el portal solo
     * comprueba, igual que el resto del aula, que el alumno tenga matricula.
     */
    public function puzzle()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.rompecabezas', [
            'portal' => $portal,
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego «Contando frutas»: diez niveles para contar del 1 al 10. La
     * partida se resuelve en el navegador y no necesita datos adicionales.
     */
    public function fruits()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.contando-frutas', [
            'portal' => $portal,
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego «Reconoce la letra»: diez niveles para reconocer el abecedario
     * español con la voz o el teclado. La partida se resuelve en el navegador.
     */
    public function letters()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.reconoce-letra', [
            'portal' => $portal,
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego «Colores y formas»: diez niveles para reconocer colores, figuras,
     * parejas, clasificación y secuencias. La partida se resuelve en el navegador.
     */
    public function colors()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.colores-formas', [
            'portal' => $portal,
            'dockActive' => 'juegos',
        ]);
    }

    /**
     * Juego «Memoria de animales»: diez niveles con sonidos y fotografías
     * reales de animales, y una mecánica de parejas. La configuración de los
     * animales y sus créditos vive en config/colegio_animales.php.
     */
    public function memory()
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        return view('colegio.juegos.memoria-animales', [
            'portal' => $portal,
            'dockActive' => 'juegos',
            'animals' => $this->animalGameAnimals(),
        ]);
    }

    /**
     * Animales del juego con las URLs reales de sus fotos y sonidos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function animalGameAnimals(): array
    {
        return collect(config('colegio_animales.animals', []))
            ->map(function (array $animal): array {
                $animal['photo_url'] = asset($animal['photo']);
                $animal['audio_url'] = asset($animal['audio']);

                return $animal;
            })
            ->values()
            ->all();
    }

    /**
     * Cambio de contraseña desde «Mi perfil».
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        [$portal, $redirect] = $this->guard();

        if (! $portal) {
            return $redirect;
        }

        $data = $request->validateWithBag('perfil', [
            'clave_actual' => ['required', 'string'],
            'clave_nueva' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'clave_actual.required' => 'Escribe tu contraseña actual.',
            'clave_nueva.required' => 'Escribe tu nueva contraseña.',
            'clave_nueva.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'clave_nueva.confirmed' => 'Las contraseñas nuevas no coinciden.',
        ]);

        $result = $this->studentAuth->changePassword(
            Auth::user(),
            $data['clave_actual'],
            $data['clave_nueva']
        );

        if (isset($result['error'])) {
            return back()->withErrors(['clave_actual' => $result['error']], 'perfil');
        }

        return back()->with('perfil_status', 'Tu contraseña se actualizó. Úsala la próxima vez que entres.');
    }

    /**
     * Datos listos para las vistas. Devuelve [portal, redireccion].
     */
    private function guard(): array
    {
        if (! Auth::check()) {
            return [null, redirect()->route($this->loginRoute())];
        }

        $membership = $this->studentAuth->membership(Auth::user(), $this->levelsForScope());

        if (isset($membership['error'])) {
            // Si la matrícula pertenece al otro portal (Inicial/Primaria o
            // Secundaria), se envía a las vistas de su propio nivel.
            $target = $this->dashboardForLevel($membership['level']?->code ?? null);

            if ($target && $target !== $this->dashboardRoute()) {
                return [null, redirect()->route($target)];
            }

            return [null, redirect()->route($this->loginRoute())->withErrors(['codigo' => $this->levelMessage($membership)])];
        }

        return [$this->buildPortal($membership), null];
    }

    /**
     * Portal que se está sirviendo según la ruta (colegio.aula.* o
     * colegio.secundaria.*).
     */
    private function scope(): string
    {
        return str_starts_with((string) request()->route()?->getName(), 'colegio.secundaria.')
            ? 'secundaria'
            : 'aula';
    }

    /**
     * @return array<int, string>
     */
    private function levelsForScope(): array
    {
        return self::LEVELS_BY_SCOPE[$this->scope()];
    }

    private function dashboardRoute(): string
    {
        return $this->scope() === 'secundaria'
            ? 'colegio.secundaria.dashboard'
            : 'colegio.aula.dashboard';
    }

    private function loginRoute(): string
    {
        return $this->scope() === 'secundaria'
            ? 'colegio.secundaria.login'
            : 'colegio.aula.login';
    }

    /**
     * Ruta del panel que corresponde al nivel de la matrícula.
     */
    private function dashboardForLevel(?string $levelCode): ?string
    {
        return match ($levelCode) {
            AcaSchoolLevel::SECUNDARIA => 'colegio.secundaria.dashboard',
            AcaSchoolLevel::INICIAL, AcaSchoolLevel::PRIMARIA => 'colegio.aula.dashboard',
            default => null,
        };
    }

    /**
     * Vista del portal actual: las vistas infantiles viven en colegio.aula y
     * las juveniles en colegio.secundaria.
     */
    private function viewFor(string $page): string
    {
        return 'colegio.' . $this->scope() . '.' . $page;
    }

    /**
     * Mensaje que ve el alumno cuando su nivel todavía no tiene aula virtual.
     *
     * @param  array{error?: string, level?: AcaSchoolLevel|null}  $membership
     */
    private function levelMessage(array $membership): string
    {
        $levelCode = ($membership['level'] ?? null)?->code;

        // Entrar por la puerta de Secundaria con una matrícula de otro nivel
        // se explica con la puerta correcta, no con un error genérico.
        if ($this->scope() === 'secundaria'
            && in_array($levelCode, [AcaSchoolLevel::INICIAL, AcaSchoolLevel::PRIMARIA], true)) {
            return 'Este acceso es exclusivo de Secundaria. Ingresa desde el acceso de Inicial y Primaria.';
        }

        return $membership['error'] ?? 'No pudimos revisar tu matrícula. Consulta en secretaría.';
    }

    private function buildPortal(array $membership): array
    {
        /** @var AcaSchoolStudent $student */
        $student = $membership['student'];
        /** @var AcaSchoolEnrollment $enrollment */
        $enrollment = $membership['enrollment'];

        $school = $this->context->currentSchool();
        $person = $student->person;
        $section = $enrollment->section;
        $grade = $section?->grade;

        $level = $membership['level'] ?? $grade?->level;

        $courses = $this->studentCourses((int) $school->id, (int) $enrollment->year_id, (int) $enrollment->section_id);

        $courseIds = $courses->pluck('id');

        $activitiesByCourse = AcaVirtualActivity::whereIn('course_id', $courseIds)
            ->where('status', true)
            ->get(['id', 'course_id'])
            ->groupBy('course_id');

        $activityIds = $activitiesByCourse->flatten()->pluck('id');

        $completedActivityIds = AcaVirtualSubmission::where('student_id', $student->id)
            ->whereIn('activity_id', $activityIds)
            ->pluck('activity_id')
            ->unique();

        // Avance por curso: solo se muestran actividades entregadas sobre las
        // actividades publicadas, sin inventar porcentajes cuando no hay.
        $courses->each(function (AcaVirtualCourse $course) use ($activitiesByCourse, $completedActivityIds) {
            $aula = $course->aula;
            $ids = $activitiesByCourse->get($course->id, collect())->pluck('id');
            $done = $ids->filter(fn ($id) => $completedActivityIds->contains($id))->count();

            $aula['completed'] = $done;
            $aula['activities_total'] = $ids->count();
            $aula['percent'] = $ids->count() > 0 ? (int) round($done * 100 / $ids->count()) : 0;

            $course->setAttribute('aula', $aula);
        });

        $completed = $completedActivityIds->count();
        $total = $activityIds->count();

        return [
            'school' => $this->school(),
            'student' => [
                'name' => $this->firstName($student) ?: 'Amiguito',
                'full_name' => $person?->full_name ?: $this->firstName($student),
                'code' => $student->student_code,
                'avatar_url' => $this->personImageUrl($person?->image),
            ],
            'grade' => [
                'name' => $grade?->name ?? $level?->name ?? 'Aula',
                'section' => $section?->name,
                'level' => $level?->name ?? 'Aula',
                'level_code' => $level?->code,
                'level_ages' => $this->levelAges($level?->code),
                'year' => $enrollment->year?->year,
                'label' => $this->gradeLabel($grade?->name, $section?->name),
            ],
            'progress' => [
                'level' => 1 + intdiv($completed, 5),
                'completed' => $completed,
                'total' => $total,
                'percent' => $total > 0 ? (int) round($completed * 100 / $total) : 0,
            ],
            'stats' => [
                'courses' => $courses->count(),
                'units' => $courses->sum(fn (AcaVirtualCourse $course) => $course->aula['units']),
                'sessions' => $courses->sum(fn (AcaVirtualCourse $course) => $course->aula['sessions']),
            ],
            'courses' => $courses,
        ];
    }

    /**
     * Rango de edades que acompaña al nombre del nivel en las vistas.
     */
    private function levelAges(?string $levelCode): ?string
    {
        return match ($levelCode) {
            AcaSchoolLevel::INICIAL => '3 a 5 años',
            AcaSchoolLevel::PRIMARIA => '6 a 11 años',
            AcaSchoolLevel::SECUNDARIA => '12 a 16 años',
            default => null,
        };
    }

    /**
     * Cursos del aula virtual de la sección del alumno en su año escolar.
     */
    private function studentCourses(int $schoolId, int $yearId, int $sectionId)
    {
        $courses = AcaVirtualCourse::with(['area', 'teacher', 'units.lessons', 'units.activities'])
            ->where('school_id', $schoolId)
            ->where('year_id', $yearId)
            ->where('section_id', $sectionId)
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $courses->each(function (AcaVirtualCourse $course, int $index) {
            $units = $course->units->where('status', true);
            $lessons = $units->flatMap(fn ($unit) => $unit->lessons->where('status', true));
            $activities = $units->flatMap(fn ($unit) => $unit->activities->where('status', true));

            $areaName = $course->area?->name ?? $course->name ?? 'Mi curso';
            $theme = $this->themeFor($areaName, $index);

            $course->setAttribute('aula', [
                'name' => $course->name ?: $areaName,
                'area' => $areaName,
                'theme' => $theme['key'],
                'tone' => $theme['tone'],
                'units' => $units->count(),
                'sessions' => $lessons->count(),
                'activities' => $activities->count(),
                'sec_theme' => $this->secondaryThemeFor($areaName),
                'teacher' => $course->teacher?->full_name,
            ]);
        });

        return $courses;
    }

    /**
     * Icono y color de la tarjeta segun el area curricular.
     *
     * @return array{key: string, tone: string}
     */
    private function themeFor(string $areaName, int $index): array
    {
        $name = mb_strtolower($areaName);

        $themes = [
            ['needle' => ['matem', 'número', 'numero'], 'key' => 'numbers', 'tone' => 'orange'],
            ['needle' => ['comunic', 'desarrollo personal', 'lengua', 'lectura', 'inglés', 'ingles'], 'key' => 'book', 'tone' => 'blue'],
            ['needle' => ['ciencia', 'ambiente', 'tecnolog'], 'key' => 'leaf', 'tone' => 'green'],
            ['needle' => ['psicomotric', 'educación física', 'educacion fisica'], 'key' => 'ball', 'tone' => 'orange'],
            ['needle' => ['arte', 'cultura', 'dibujo', 'música', 'musica'], 'key' => 'pencil', 'tone' => 'lilac'],
            ['needle' => ['religio'], 'key' => 'heart', 'tone' => 'lilac'],
            ['needle' => ['social', 'personal', 'cívica', 'civica'], 'key' => 'globe', 'tone' => 'blue'],
        ];

        foreach ($themes as $theme) {
            foreach ($theme['needle'] as $needle) {
                if (str_contains($name, $needle)) {
                    return ['key' => $theme['key'], 'tone' => $theme['tone']];
                }
            }
        }

        $tones = ['green', 'blue', 'lilac', 'yellow'];

        return ['key' => 'star', 'tone' => $tones[$index % count($tones)]];
    }

    /**
     * Identidad cromática de cada área en las vistas de Secundaria. No se usa
     * en las vistas infantiles: allí manda themeFor() con los personajes del
     * aula de Inicial y Primaria.
     */
    private function secondaryThemeFor(string $areaName): string
    {
        $name = mb_strtolower($areaName);

        return match (true) {
            str_contains($name, 'matem') => 'math',
            str_contains($name, 'historia'), str_contains($name, 'social') => 'history',
            str_contains($name, 'comunic'), str_contains($name, 'lengua'), str_contains($name, 'literat') => 'language',
            str_contains($name, 'inglés'), str_contains($name, 'ingles') => 'english',
            str_contains($name, 'arte'), str_contains($name, 'música'), str_contains($name, 'musica') => 'art',
            str_contains($name, 'educación física'), str_contains($name, 'educacion fisica') => 'sport',
            str_contains($name, 'ciencia'), str_contains($name, 'tecnolog'), str_contains($name, 'biolog'),
            str_contains($name, 'física'), str_contains($name, 'fisica'), str_contains($name, 'químic'),
            str_contains($name, 'quimic') => 'science',
            default => 'discover',
        };
    }

    /**
     * Materiales visibles en el modal: los del curso, los de sus unidades y
     * los de cada sesion, sin repetir el mismo recurso.
     *
     * @return array<int, array<string, mixed>>
     */
    private function courseMaterials(AcaVirtualCourse $course): array
    {
        $seen = [];
        $materials = [];

        $push = function (AcaVirtualResource $resource, string $origin) use (&$seen, &$materials) {
            if (isset($seen[$resource->id])) {
                return;
            }

            $seen[$resource->id] = true;
            $materials[] = $this->materialPayload($resource, $origin);
        };

        foreach ($course->resources as $resource) {
            $push($resource, 'Material del curso');
        }

        foreach ($course->units as $unit) {
            foreach ($unit->resources as $resource) {
                $push($resource, 'Unidad · ' . $unit->title);
            }

            foreach ($unit->lessons as $lesson) {
                foreach ($lesson->resources as $resource) {
                    $push($resource, 'Sesión · ' . $lesson->title);
                }
            }
        }

        return $materials;
    }

    /**
     * Datos que usa el modal para previsualizar y descargar un material.
     *
     * @return array<string, mixed>
     */
    private function materialPayload(AcaVirtualResource $resource, string $origin): array
    {
        $mime = (string) $resource->mime_type;
        $extension = $resource->path ? mb_strtolower(pathinfo($resource->path, PATHINFO_EXTENSION)) : '';
        $hasFile = (bool) $resource->path;

        if (str_starts_with($mime, 'image/') || in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
            $kind = $hasFile ? 'image' : 'link';
        } elseif ($mime === 'application/pdf' || $extension === 'pdf') {
            $kind = $hasFile ? 'pdf' : 'link';
        } elseif (! $hasFile && $resource->type === 'embed') {
            $kind = 'embed';
        } elseif ($hasFile) {
            $kind = 'file';
        } else {
            $kind = 'link';
        }

        $materialRoutes = $this->scope() === 'secundaria'
            ? 'colegio.secundaria.material'
            : 'colegio.aula.material';

        return [
            'id' => (int) $resource->id,
            'title' => $resource->title ?: 'Material',
            'kind' => $kind,
            'origin' => $origin,
            'size' => $resource->size_kb ? $this->formatSize((int) $resource->size_kb) : null,
            'preview_url' => $hasFile ? route($materialRoutes . '.view', $resource->id) : null,
            'download_url' => $hasFile ? route($materialRoutes . '.download', $resource->id) : null,
            'external_url' => $resource->url ?: null,
            'embed_html' => $resource->type === 'embed' ? $resource->content : null,
        ];
    }

    /**
     * Tamano en KB o MB con formato legible para el alumno.
     */
    private function formatSize(int $sizeKb): string
    {
        if ($sizeKb >= 1024) {
            return number_format($sizeKb / 1024, 1, ',', '.') . ' MB';
        }

        return $sizeKb . ' KB';
    }

    /**
     * Frases de la mascota. Solo usan datos reales del alumno y su aula.
     */
    private function bubbleMessages(array $portal): array
    {
        $name = $portal['student']['name'];
        $courses = $portal['courses'];

        $messages = ['¡Hola, ' . $name . '! Estoy feliz de verte por aquí.'];

        if ($courses->isNotEmpty()) {
            $first = $courses->first()->aula['name'];
            $messages[] = 'Hoy podemos empezar con «' . $first . '». ¿Te animas?';
        }

        if ($portal['stats']['sessions'] > 0) {
            $messages[] = 'Tu aula tiene ' . $portal['stats']['sessions'] . ' ' .
                ($portal['stats']['sessions'] === 1 ? 'sesión' : 'sesiones') . ' esperando por ti.';
        }

        $messages[] = 'Si necesitas ayuda, pide a tu profesora que te acompañe.';
        $messages[] = 'Recuerda descansar y tomar agua mientras aprendes.';

        return $messages;
    }

    /**
     * Etiqueta del aula. Algunas secciones ya se llaman «Aula Rojo», así que
     * solo se antepone la palabra cuando el nombre no la trae.
     */
    private function gradeLabel(?string $gradeName, ?string $sectionName): string
    {
        $gradeName = trim((string) ($gradeName ?: 'Aula'));
        $sectionName = trim((string) $sectionName);

        if ($sectionName === '') {
            return $gradeName;
        }

        $sectionLabel = str_starts_with(mb_strtolower($sectionName), 'aula')
            ? $sectionName
            : 'Aula ' . $sectionName;

        return $gradeName . ' · ' . $sectionLabel;
    }

    /**
     * Solo el primer nombre: el saludo se lee mejor para los niños.
     */
    private function firstName(AcaSchoolStudent $student): ?string
    {
        $person = $student->person;
        $names = trim((string) ($person?->names ?: $person?->full_name));

        if ($names === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $names) ?: [$names];

        return \Illuminate\Support\Str::title($parts[0]);
    }

    private function personImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }
}
