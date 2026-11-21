<?php

namespace Modules\Academic\Services;

use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentAttendance;
use Modules\Academic\Entities\AcaStudentExam;
use Modules\Academic\Entities\AcaStudentGrade;
use Modules\Academic\Entities\AcaStudentHistory;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Entities\AcaTeacherCourse;

/**
 * Consultas de solo lectura sobre estudiantes, docentes y actividad académica.
 *
 * No modifica ninguna tabla, no consulta usuarios ni tokens y nunca devuelve
 * contraseñas. Las métricas se calculan con las mismas reglas de acceso que usa
 * el aula: matrícula vigente, suscripción activa o curso gratuito.
 */
class StudentInsightsService
{
    /** Días por defecto para considerar una suscripción "por vencer". */
    public const EXPIRING_DAYS = 15;

    /**
     * Listado paginado de estudiantes con sus conteos principales.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function listStudents(array $filtros = []): array
    {
        $hoy = Carbon::today();
        $porPagina = min(max((int) ($filtros['per_page'] ?? 25), 1), 100);
        $pagina = max((int) ($filtros['page'] ?? 1), 1);
        $dias = min(max((int) ($filtros['expiring_days'] ?? self::EXPIRING_DAYS), 1), 365);
        $busqueda = trim((string) ($filtros['search'] ?? ''));

        $consulta = AcaStudent::query()
            ->with(['person:id,full_name,names,father_lastname,mother_lastname,number,email,telephone,image'])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->whereHas('person', function ($persona) use ($busqueda) {
                    $persona->where('full_name', 'LIKE', '%' . $busqueda . '%')
                        ->orWhere('names', 'LIKE', '%' . $busqueda . '%')
                        ->orWhere('father_lastname', 'LIKE', '%' . $busqueda . '%')
                        ->orWhere('mother_lastname', 'LIKE', '%' . $busqueda . '%')
                        ->orWhere('email', 'LIKE', '%' . $busqueda . '%')
                        ->orWhere('number', '=', $busqueda);
                });
            })
            ->when($filtros['course_id'] ?? null, fn ($query, $courseId) => $query->whereHas(
                'registrations',
                fn ($registro) => $registro->where('course_id', (int) $courseId)
            ))
            ->when(($filtros['subscription_status'] ?? 'todas') !== 'todas', function ($query) use ($filtros, $hoy, $dias) {
                $estado = (string) ($filtros['subscription_status'] ?? 'todas');

                if ($estado === 'activa') {
                    $query->whereHas('subscriptions', fn ($sub) => $sub->activeNow());
                } elseif ($estado === 'por_vencer') {
                    $query->whereHas('subscriptions', function ($sub) use ($hoy, $dias) {
                        $sub->activeNow()
                            ->whereNotNull('date_end')
                            ->whereDate('date_end', '<=', $hoy->copy()->addDays($dias));
                    });
                } elseif ($estado === 'vencida') {
                    $query->whereHas('subscriptions', function ($sub) use ($hoy) {
                        $sub->whereDate('date_end', '<', $hoy);
                    });
                } elseif ($estado === 'sin_suscripcion') {
                    $query->whereDoesntHave('subscriptions');
                }
            })
            ->orderBy('id');

        $total = (clone $consulta)->count();
        $estudiantes = $consulta->forPage($pagina, $porPagina)->get();
        $ids = $estudiantes->pluck('id')->all();
        $personIds = $estudiantes->pluck('person_id')->filter()->all();

        $registros = AcaCapRegistration::query()
            ->whereIn('student_id', $ids)
            ->get(['id', 'student_id', 'course_id', 'status', 'date_start', 'date_end', 'unlimited']);
        $suscripciones = AcaStudentSubscription::query()
            ->with('subscription:id,title')
            ->whereIn('student_id', $ids)
            ->get();
        $actividad = AcaStudentHistory::query()
            ->whereIn('person_id', $personIds)
            ->selectRaw('person_id, MAX(created_at) as last_activity_at, COUNT(*) as views')
            ->groupBy('person_id')
            ->get()
            ->keyBy('person_id');

        $filas = $estudiantes->map(function (AcaStudent $estudiante) use ($registros, $suscripciones, $actividad, $hoy, $dias) {
            $propias = $registros->where('student_id', $estudiante->id);
            $cursos = $propias->pluck('course_id')->unique();
            $cursosActivos = $propias
                ->filter(fn (AcaCapRegistration $registro) => $this->registroActivo($registro, $hoy))
                ->pluck('course_id')
                ->unique();
            $planes = $suscripciones->where('student_id', $estudiante->id)->values();
            $resumen = $this->resumenSuscripciones($planes, $hoy, $dias);
            $historial = $actividad->get($estudiante->person_id);

            return array_merge($this->persona($estudiante), [
                'student_id' => $estudiante->id,
                'student_code' => $estudiante->student_code,
                'courses_count' => $cursos->count(),
                'active_courses_count' => $cursosActivos->count(),
                'subscriptions_count' => $planes->count(),
                'active_subscriptions_count' => $resumen['active_count'],
                'subscription_status' => $resumen['status'],
                'subscription_ends_at' => $resumen['ends_at'],
                'expiring_soon' => $resumen['expiring_soon'],
                'last_activity_at' => optional($historial?->last_activity_at)->toIso8601String(),
                'content_views' => (int) ($historial?->views ?? 0),
            ]);
        })->values()->all();

        return [
            'students' => $filas,
            'total' => $total,
            'page' => $pagina,
            'per_page' => $porPagina,
            'has_more' => $pagina * $porPagina < $total,
            'metric_note' => 'courses_count cuenta matrículas distintas. active_courses_count exige matrícula activa, ilimitada o con fecha de fin vigente. content_views cuenta visitas registradas a contenidos.',
        ];
    }

    /**
     * Detalle académico de un estudiante: cursos, acceso, avance, exámenes,
     * asistencias, notas, certificados y suscripciones.
     *
     * @return array<string, mixed>
     */
    public function studentDetail(AcaStudent $estudiante): array
    {
        $hoy = Carbon::today();
        $estudiante->loadMissing('person');

        $registros = AcaCapRegistration::query()
            ->with('course')
            ->where('student_id', $estudiante->id)
            ->orderByDesc('unlimited')
            ->orderByDesc('date_end')
            ->orderByDesc('id')
            ->get()
            ->unique('course_id')
            ->values();

        $suscripciones = AcaStudentSubscription::query()
            ->with('subscription')
            ->where('student_id', $estudiante->id)
            ->orderByDesc('date_end')
            ->get();
        $resumenSuscripciones = $this->resumenSuscripciones($suscripciones, $hoy, self::EXPIRING_DAYS);
        $permisos = SubscriptionContentPermissions::forStudent($estudiante->id);

        $historial = AcaStudentHistory::query()
            ->where('person_id', $estudiante->person_id)
            ->get(['course_id', 'content_id', 'created_at']);
        $historialPorCurso = $historial->groupBy('course_id');

        $courseIds = $registros->pluck('course_id')
            ->merge($historial->pluck('course_id'))
            ->filter()
            ->unique()
            ->values();
        $cursos = AcaCourse::query()
            ->with(['modules.themes.contents'])
            ->whereIn('id', $courseIds)
            ->get()
            ->keyBy('id');

        $examenes = AcaStudentExam::query()
            ->with(['exam:id,course_id,module_id,description,is_mock', 'exam.module:id,course_id,description'])
            ->where('student_id', $estudiante->id)
            ->get();
        $notas = AcaStudentGrade::query()
            ->where('student_id', $estudiante->id)
            ->get()
            ->keyBy('course_id');
        $asistencias = AcaStudentAttendance::query()
            ->with(['course:id,description', 'module:id,description', 'content:id,description'])
            ->where('student_id', $estudiante->id)
            ->orderByDesc('registered_at')
            ->get();
        $certificados = AcaCertificate::query()
            ->where('student_id', $estudiante->id)
            ->whereNull('module_id')
            ->get()
            ->keyBy('course_id');

        $cursosDetalle = $cursos->map(function (AcaCourse $curso) use ($estudiante, $registros, $historialPorCurso, $examenes, $asistencias, $notas, $certificados, $suscripciones, $permisos, $hoy) {
            $registro = $registros->firstWhere('course_id', $curso->id);
            $vistos = (int) $historialPorCurso->get($curso->id, collect())->unique('content_id')->count();
            $totalContenidos = $this->totalContenidos($curso);
            $examenesCurso = $this->examenesDelCurso($examenes, $curso->id);
            $asistenciasCurso = $asistencias->where('course_id', $curso->id);
            $nota = $notas->get($curso->id);
            $certificado = $certificados->get($curso->id);
            $acceso = $this->accesoCurso($curso, $registro, $suscripciones, $permisos, $hoy);

            return [
                'course_id' => $curso->id,
                'title' => $curso->description,
                'type' => $curso->type_description,
                'status' => $curso->status ? 'activo' : 'borrador',
                'registration' => $registro ? [
                    'id' => $registro->id,
                    'status' => (bool) $registro->status,
                    'date_start' => $this->fecha($registro->date_start)?->toDateString(),
                    'date_end' => $this->fecha($registro->date_end)?->toDateString(),
                    'unlimited' => (bool) $registro->unlimited,
                    'active' => $this->registroConAcceso($registro, $hoy),
                ] : null,
                'access' => $acceso,
                'progress' => [
                    'viewed_contents' => $vistos,
                    'total_contents' => $totalContenidos,
                    'percent' => $this->porcentaje($vistos, $totalContenidos),
                ],
                'exams' => $this->resumenExamenes($examenesCurso),
                'attendance' => [
                    'records' => $asistenciasCurso->count(),
                    'last_at' => optional($asistenciasCurso->first()?->registered_at)->toIso8601String(),
                ],
                'grade' => $nota ? [
                    'final_average' => $nota->final_average !== null ? (float) $nota->final_average : null,
                    'approved' => (bool) $nota->approved,
                    'registered_at' => optional($nota->registered_at)->toIso8601String(),
                ] : null,
                'certificate' => $certificado ? [
                    'id' => $certificado->id,
                    'created_at' => optional($certificado->created_at)->toIso8601String(),
                ] : null,
            ];
        })->values()->all();

        $actividadPorCurso = $historialPorCurso
            ->map(function (Collection $vistas, $courseId) use ($cursos) {
                return [
                    'course_id' => (int) $courseId,
                    'title' => $cursos->get($courseId)?->description,
                    'content_views' => $vistas->count(),
                    'distinct_contents' => $vistas->unique('content_id')->count(),
                    'last_activity_at' => optional($vistas->sortByDesc('created_at')->first()?->created_at)->toIso8601String(),
                ];
            })
            ->sortByDesc('content_views')
            ->values()
            ->take(10)
            ->all();

        $cursosAccesibles = AcaCourse::query()
            ->where('status', true)
            ->get(['id', 'type_description', 'price'])
            ->filter(fn (AcaCourse $curso) => $this->accesoCurso(
                $curso,
                $registros->firstWhere('course_id', $curso->id),
                $suscripciones,
                $permisos,
                $hoy
            )['can_access'])
            ->count();

        return array_merge($this->persona($estudiante), [
            'student_id' => $estudiante->id,
            'student_code' => $estudiante->student_code,
            'created_at' => optional($estudiante->created_at)->toIso8601String(),
            'summary' => [
                'courses_count' => $registros->count(),
                'accessible_courses_count' => $cursosAccesibles,
                'certificates_count' => $certificados->count(),
                'content_views' => $historial->count(),
                'last_activity_at' => optional($historial->sortByDesc('created_at')->first()?->created_at)->toIso8601String(),
                'exams' => $this->resumenExamenes($examenes),
                'attendance_records' => $asistencias->count(),
            ],
            'subscriptions' => $suscripciones->map(fn (AcaStudentSubscription $suscripcion) => [
                'plan_id' => $suscripcion->subscription_id,
                'plan' => $suscripcion->subscription?->title,
                'status' => (bool) $suscripcion->status,
                'active' => $this->suscripcionVigente($suscripcion, $hoy),
                'date_start' => $this->fecha($suscripcion->date_start)?->toDateString(),
                'date_end' => $this->fecha($suscripcion->date_end)?->toDateString(),
                'days_remaining' => $this->diasRestantes($suscripcion->date_end, $hoy),
                'expiring_soon' => $this->suscripcionPorVencer($suscripcion, $hoy, self::EXPIRING_DAYS),
            ])->values()->all(),
            'subscription_summary' => $resumenSuscripciones,
            'subscription_permissions' => $permisos,
            'courses' => $cursosDetalle,
            'activity' => [
                'top_courses' => $actividadPorCurso,
                'metric' => 'content_views: visitas registradas en aca_student_histories.',
            ],
            'exams' => [
                'summary' => $this->resumenExamenes($examenes),
                'recent' => $examenes
                    ->sortByDesc(fn (AcaStudentExam $examen) => $examen->date_start ?? $examen->created_at)
                    ->take(20)
                    ->map(fn (AcaStudentExam $examen) => [
                        'id' => $examen->id,
                        'exam' => $examen->exam?->description,
                        'course_id' => $this->cursoDeExamen($examen),
                        'module' => $examen->exam?->module?->description,
                        'is_mock' => (bool) ($examen->exam?->is_mock),
                        'score' => $examen->punctuation !== null ? (float) $examen->punctuation : null,
                        'status' => $examen->status,
                        'date_start' => $this->fechaHora($examen->date_start)?->toIso8601String(),
                        'date_end' => $this->fechaHora($examen->date_end)?->toIso8601String(),
                    ])->values()->all(),
            ],
            'attendance' => [
                'available' => $asistencias->isNotEmpty(),
                'records' => $asistencias->count(),
                'by_course' => $asistencias
                    ->groupBy('course_id')
                    ->map(fn (Collection $items, $courseId) => [
                        'course_id' => (int) $courseId,
                        'course' => $items->first()?->course?->description,
                        'records' => $items->count(),
                        'last_at' => optional($items->first()?->registered_at)->toIso8601String(),
                    ])->values()->all(),
                'recent' => $asistencias->take(20)->map(fn (AcaStudentAttendance $asistencia) => [
                    'id' => $asistencia->id,
                    'course_id' => $asistencia->course_id,
                    'course' => $asistencia->course?->description,
                    'module' => $asistencia->module?->description,
                    'content' => $asistencia->content?->description,
                    'registered_at' => optional($asistencia->registered_at)->toIso8601String(),
                    'observation' => $asistencia->observation,
                ])->values()->all(),
            ],
            'access_summary' => [
                'registered_courses' => $registros->count(),
                'accessible_courses' => $cursosAccesibles,
                'by_subscription' => $permisos,
                'note' => 'Un curso gratuito, una matrícula vigente o una suscripción activa pueden dar acceso. No se exponen contraseñas, tokens ni credenciales.',
            ],
        ]);
    }

    /**
     * Comprueba si un estudiante puede entrar a un curso y por qué.
     *
     * @return array<string, mixed>
     */
    public function studentCourseAccess(AcaStudent $estudiante, AcaCourse $curso): array
    {
        $hoy = Carbon::today();
        $suscripciones = AcaStudentSubscription::query()
            ->with('subscription')
            ->where('student_id', $estudiante->id)
            ->get();
        $registro = AcaCapRegistration::query()
            ->where('student_id', $estudiante->id)
            ->where('course_id', $curso->id)
            ->orderByDesc('unlimited')
            ->orderByDesc('date_end')
            ->first();
        $permisos = SubscriptionContentPermissions::forStudent($estudiante->id);
        $acceso = $this->accesoCurso($curso, $registro, $suscripciones, $permisos, $hoy);

        $planes = $suscripciones
            ->filter(fn (AcaStudentSubscription $suscripcion) => $this->suscripcionVigente($suscripcion, $hoy)
                && $this->planDaAcceso($suscripcion->subscription, $curso->type_description))
            ->map(fn (AcaStudentSubscription $suscripcion) => [
                'plan_id' => $suscripcion->subscription_id,
                'plan' => $suscripcion->subscription?->title,
                'date_end' => $this->fecha($suscripcion->date_end)?->toDateString(),
                'days_remaining' => $this->diasRestantes($suscripcion->date_end, $hoy),
            ])->values()->all();

        return [
            'student' => $this->persona($estudiante),
            'course' => [
                'course_id' => $curso->id,
                'title' => $curso->description,
                'type' => $curso->type_description,
                'status' => $curso->status ? 'activo' : 'borrador',
                'price' => $curso->price !== null ? (float) $curso->price : null,
            ],
            'can_access' => $acceso['can_access'],
            'sources' => $acceso['sources'],
            'registration' => $registro ? [
                'id' => $registro->id,
                'status' => (bool) $registro->status,
                'date_start' => $this->fecha($registro->date_start)?->toDateString(),
                'date_end' => $this->fecha($registro->date_end)?->toDateString(),
                'unlimited' => (bool) $registro->unlimited,
                'active' => $this->registroConAcceso($registro, $hoy),
            ] : null,
            'subscriptions_granting' => $planes,
            'subscription_permissions' => $permisos,
            'note' => 'Se aplican las mismas reglas del aula: curso gratuito, matrícula vigente o suscripción activa. Un Programa de Especialización requiere matrícula.',
        ];
    }

    /**
     * Resumen general para informes: distribución de cursos, avance, exámenes,
     * asistencias y suscripciones por vencer.
     *
     * @return array<string, mixed>
     */
    public function overview(int $dias = 30, int $top = 10): array
    {
        $hoy = Carbon::today();
        $dias = min(max($dias, 1), 365);
        $top = min(max($top, 1), 50);
        $desde = $hoy->copy()->subDays($dias);

        $estudiantes = AcaStudent::query()
            ->with('person:id,full_name,names,father_lastname,mother_lastname')
            ->get();
        $studentIds = $estudiantes->pluck('id')->all();
        $personIds = $estudiantes->pluck('person_id')->filter()->all();

        $registros = AcaCapRegistration::query()
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'course_id', 'status', 'date_start', 'date_end', 'unlimited']);
        $suscripciones = AcaStudentSubscription::query()
            ->with('subscription:id,title')
            ->whereIn('student_id', $studentIds)
            ->get();
        $historial = AcaStudentHistory::query()
            ->whereIn('person_id', $personIds)
            ->get(['person_id', 'course_id', 'content_id', 'created_at']);
        $historialPeriodo = $historial->where('created_at', '>=', $desde);
        $examenes = AcaStudentExam::query()
            ->with('exam:id,course_id,module_id,description,is_mock')
            ->whereIn('student_id', $studentIds)
            ->get();
        $asistencias = AcaStudentAttendance::query()
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'course_id', 'registered_at']);
        $notas = AcaStudentGrade::query()
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'course_id', 'final_average', 'approved']);
        $certificados = AcaCertificate::query()
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'course_id']);

        $cursos = AcaCourse::query()
            ->with(['modules.themes.contents'])
            ->get();
        $cursosPorId = $cursos->keyBy('id');
        $totalesContenido = $cursos->mapWithKeys(fn (AcaCourse $curso) => [$curso->id => $this->totalContenidos($curso)]);

        $personaPorEstudiante = $estudiantes->pluck('person_id', 'id');
        $registrosActivos = $registros->filter(fn (AcaCapRegistration $registro) => $this->registroActivo($registro, $hoy));
        $suscripcionesActivas = $suscripciones->filter(fn (AcaStudentSubscription $suscripcion) => $this->suscripcionVigente($suscripcion, $hoy));

        $avances = [];
        $vistosPorPersonaCurso = $historial
            ->groupBy(fn ($vista) => $vista->person_id . '-' . $vista->course_id)
            ->map(fn (Collection $vistas) => $vistas->unique('content_id')->count());
        foreach ($registrosActivos->unique(fn ($registro) => $registro->student_id . '-' . $registro->course_id) as $registro) {
            $personId = $personaPorEstudiante->get($registro->student_id);
            $total = (int) ($totalesContenido[$registro->course_id] ?? 0);
            $vistos = (int) ($vistosPorPersonaCurso[$personId . '-' . $registro->course_id] ?? 0);
            if ($total > 0) {
                $avances[] = $this->porcentaje($vistos, $total);
            }
        }

        $cursosPorEstudiante = $registros
            ->unique(fn (AcaCapRegistration $registro) => $registro->student_id . '-' . $registro->course_id)
            ->groupBy('student_id')
            ->map->count();
        $distribucion = [
            'sin_cursos' => $estudiantes->filter(fn (AcaStudent $estudiante) => ! $cursosPorEstudiante->has($estudiante->id))->count(),
            'uno_a_dos' => $cursosPorEstudiante->filter(fn ($total) => $total >= 1 && $total <= 2)->count(),
            'tres_a_cinco' => $cursosPorEstudiante->filter(fn ($total) => $total >= 3 && $total <= 5)->count(),
            'seis_o_mas' => $cursosPorEstudiante->filter(fn ($total) => $total >= 6)->count(),
        ];

        $topMatriculas = $registros
            ->groupBy('course_id')
            ->map(fn (Collection $items, $courseId) => [
                'course_id' => (int) $courseId,
                'title' => $cursosPorId->get($courseId)?->description,
                'students' => $items->pluck('student_id')->unique()->count(),
                'registrations' => $items->count(),
            ])
            ->sortByDesc('students')
            ->values()
            ->take($top)
            ->all();

        $topVisitas = $historialPeriodo
            ->groupBy('course_id')
            ->map(fn (Collection $items, $courseId) => [
                'course_id' => (int) $courseId,
                'title' => $cursosPorId->get($courseId)?->description,
                'views' => $items->count(),
                'students' => $items->pluck('person_id')->unique()->count(),
            ])
            ->sortByDesc('views')
            ->values()
            ->take($top)
            ->all();

        $examenResumen = $this->resumenExamenes($examenes);
        $notasConValor = $notas->whereNotNull('final_average');
        $suscripcionesPorVencerTotal = $suscripciones
            ->filter(fn (AcaStudentSubscription $suscripcion) => $this->suscripcionPorVencer($suscripcion, $hoy, self::EXPIRING_DAYS))
            ->count();
        $suscripcionesPorVencer = $suscripciones
            ->filter(fn (AcaStudentSubscription $suscripcion) => $this->suscripcionPorVencer($suscripcion, $hoy, self::EXPIRING_DAYS))
            ->sortBy('date_end')
            ->take($top)
            ->map(fn (AcaStudentSubscription $suscripcion) => [
                'student_id' => $suscripcion->student_id,
                'plan' => $suscripcion->subscription?->title,
                'date_end' => $this->fecha($suscripcion->date_end)?->toDateString(),
                'days_remaining' => $this->diasRestantes($suscripcion->date_end, $hoy),
            ])->values()->all();

        $estudiantesActivos = $estudiantes->filter(function (AcaStudent $estudiante) use ($registrosActivos, $suscripcionesActivas, $historialPeriodo) {
            return $registrosActivos->where('student_id', $estudiante->id)->isNotEmpty()
                || $suscripcionesActivas->where('student_id', $estudiante->id)->isNotEmpty()
                || $historialPeriodo->where('person_id', $estudiante->person_id)->isNotEmpty();
        })->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'period_days' => $dias,
            'totals' => [
                'students' => $estudiantes->count(),
                'active_students' => $estudiantesActivos,
                'students_with_active_registration' => $registrosActivos->pluck('student_id')->unique()->count(),
                'students_with_active_subscription' => $suscripcionesActivas->pluck('student_id')->unique()->count(),
                'students_with_activity' => $historialPeriodo->pluck('person_id')->unique()->count(),
                'courses' => $cursos->count(),
                'courses_with_enrollments' => $registros->pluck('course_id')->unique()->count(),
                'active_courses' => $registrosActivos->pluck('course_id')->unique()->count(),
                'certificates' => $certificados->count(),
                'average_courses_per_student' => $estudiantes->count() > 0
                    ? round($registros->unique(fn (AcaCapRegistration $registro) => $registro->student_id . '-' . $registro->course_id)->count() / $estudiantes->count(), 2)
                    : 0,
            ],
            'courses_distribution' => $distribucion,
            'progress' => [
                'average_percent' => $avances !== [] ? round(array_sum($avances) / count($avances), 1) : 0,
                'evaluated_student_courses' => count($avances),
            ],
            'subscriptions' => [
                'active' => $suscripcionesActivas->count(),
                'expiring_in_' . self::EXPIRING_DAYS . '_days' => $suscripcionesPorVencerTotal,
                'expired' => $suscripciones->filter(fn (AcaStudentSubscription $suscripcion) => $this->fecha($suscripcion->date_end)?->lt($hoy))->count(),
                'expiring_list' => $suscripcionesPorVencer,
            ],
            'exams' => $examenResumen,
            'attendance' => [
                'records' => $asistencias->count(),
                'students_with_attendance' => $asistencias->pluck('student_id')->unique()->count(),
                'courses_with_attendance' => $asistencias->pluck('course_id')->unique()->count(),
            ],
            'grades' => [
                'recorded' => $notasConValor->count(),
                'average' => $notasConValor->isNotEmpty() ? round($notasConValor->avg('final_average'), 2) : null,
                'approved' => $notas->where('approved', true)->count(),
            ],
            'top_courses_by_enrollment' => $topMatriculas,
            'top_courses_by_activity' => $topVisitas,
            'metric_note' => 'progress.average_percent promedia el avance de cada matrícula activa (contenidos vistos / contenidos del curso). exams usa los intentos registrados; attendance cuenta registros de asistencia, no una asistencia esperada.',
        ];
    }

    /**
     * Suscripciones próximas a vencer, o vencidas si se piden.
     *
     * @return array<string, mixed>
     */
    public function expiringSubscriptions(int $dias = self::EXPIRING_DAYS, bool $incluirVencidas = false, int $pagina = 1, int $porPagina = 25): array
    {
        $hoy = Carbon::today();
        $dias = min(max($dias, 1), 365);
        $pagina = max($pagina, 1);
        $porPagina = min(max($porPagina, 1), 100);
        $limite = $hoy->copy()->addDays($dias);

        $consulta = AcaStudentSubscription::query()
            ->with(['student.person:id,full_name,names,father_lastname,mother_lastname,number,email,telephone', 'subscription:id,title'])
            ->whereNotNull('date_end')
            ->whereDate('date_end', '<=', $limite)
            ->when(! $incluirVencidas, function ($query) use ($hoy) {
                $query->where(function ($vigente) use ($hoy) {
                    $vigente->where(function ($porEstado) use ($hoy) {
                        $porEstado->where('status', true)->whereDate('date_end', '>=', $hoy);
                    })->orWhere(function ($porFechas) use ($hoy) {
                        $porFechas->whereNotNull('date_start')
                            ->whereDate('date_start', '<=', $hoy)
                            ->whereDate('date_end', '>=', $hoy);
                    });
                });
            })
            ->orderBy('date_end');

        $total = (clone $consulta)->count();
        $items = $consulta->forPage($pagina, $porPagina)->get();

        return [
            'subscriptions' => $items->map(function (AcaStudentSubscription $suscripcion) use ($hoy) {
                $diasRestantes = $this->diasRestantes($suscripcion->date_end, $hoy);

                return [
                    'student_id' => $suscripcion->student_id,
                    'student' => $suscripcion->student?->person?->full_name,
                    'student_code' => $suscripcion->student?->student_code,
                    'plan_id' => $suscripcion->subscription_id,
                    'plan' => $suscripcion->subscription?->title,
                    'status' => (bool) $suscripcion->status,
                    'date_start' => $this->fecha($suscripcion->date_start)?->toDateString(),
                    'date_end' => $this->fecha($suscripcion->date_end)?->toDateString(),
                    'days_remaining' => $diasRestantes,
                    'expired' => $diasRestantes !== null && $diasRestantes < 0,
                    'expiring_soon' => $diasRestantes !== null && $diasRestantes >= 0 && $diasRestantes <= self::EXPIRING_DAYS,
                ];
            })->values()->all(),
            'total' => $total,
            'page' => $pagina,
            'per_page' => $porPagina,
            'has_more' => $pagina * $porPagina < $total,
            'days_window' => $dias,
            'include_expired' => $incluirVencidas,
        ];
    }

    /**
     * Docente con sus cursos, cantidad de alumnos y promedios de avance,
     * exámenes y asistencias. Solo lectura.
     *
     * @return array<string, mixed>
     */
    public function teacherDetail(AcaTeacher $docente): array
    {
        $hoy = Carbon::today();
        $docente->loadMissing('person');

        $courseIds = AcaTeacherCourse::query()
            ->where('teacher_id', $docente->id)
            ->pluck('course_id')
            ->merge(AcaCourse::query()->where('teacher_id', $docente->id)->pluck('id'))
            ->unique()
            ->values();

        $cursos = AcaCourse::query()
            ->with(['modules.themes.contents'])
            ->whereIn('id', $courseIds)
            ->get();
        $registros = AcaCapRegistration::query()
            ->whereIn('course_id', $courseIds)
            ->get(['student_id', 'course_id', 'status', 'date_start', 'date_end', 'unlimited']);
        $personaPorEstudiante = AcaStudent::query()
            ->whereIn('id', $registros->pluck('student_id')->unique())
            ->pluck('person_id', 'id');
        $historial = AcaStudentHistory::query()
            ->whereIn('person_id', $personaPorEstudiante->values())
            ->whereIn('course_id', $courseIds)
            ->get(['person_id', 'course_id', 'content_id']);
        $vistos = $historial
            ->groupBy(fn ($vista) => $vista->person_id . '-' . $vista->course_id)
            ->map(fn (Collection $items) => $items->unique('content_id')->count());
        $examenes = AcaStudentExam::query()
            ->with('exam:id,course_id,module_id,description,is_mock')
            ->whereIn('exam_id', function ($query) use ($courseIds) {
                $query->select('id')->from('aca_exams')->whereIn('course_id', $courseIds);
            })
            ->whereIn('student_id', $registros->pluck('student_id')->unique())
            ->get();
        $asistencias = AcaStudentAttendance::query()
            ->whereIn('course_id', $courseIds)
            ->get(['student_id', 'course_id']);
        $certificados = AcaCertificate::query()
            ->whereIn('course_id', $courseIds)
            ->get(['student_id', 'course_id']);

        $detalle = $cursos->map(function (AcaCourse $curso) use ($registros, $vistos, $personaPorEstudiante, $examenes, $asistencias, $certificados, $hoy) {
            $delCurso = $registros->where('course_id', $curso->id);
            $activos = $delCurso->filter(fn (AcaCapRegistration $registro) => $this->registroActivo($registro, $hoy));
            $total = $this->totalContenidos($curso);
            $avances = $activos
                ->unique('student_id')
                ->map(function (AcaCapRegistration $registro) use ($vistos, $personaPorEstudiante, $curso, $total) {
                    if ($total === 0) {
                        return null;
                    }
                    $personId = $personaPorEstudiante->get($registro->student_id);
                    $vistosCurso = (int) ($vistos[$personId . '-' . $curso->id] ?? 0);

                    return $this->porcentaje($vistosCurso, $total);
                })->filter(fn ($valor) => $valor !== null);

            return [
                'course_id' => $curso->id,
                'title' => $curso->description,
                'status' => $curso->status ? 'activo' : 'borrador',
                'students_count' => $delCurso->pluck('student_id')->unique()->count(),
                'active_students_count' => $activos->pluck('student_id')->unique()->count(),
                'average_progress' => $avances->isNotEmpty() ? round($avances->avg(), 1) : 0,
                'exams' => $this->resumenExamenes($examenes->filter(fn (AcaStudentExam $examen) => (int) $this->cursoDeExamen($examen) === (int) $curso->id)),
                'attendance_records' => $asistencias->where('course_id', $curso->id)->count(),
                'certificates' => $certificados->where('course_id', $curso->id)->count(),
            ];
        })->values()->all();

        return array_merge($this->personaDocente($docente), [
            'teacher_id' => $docente->id,
            'teacher_code' => $docente->teacher_code,
            'summary' => [
                'courses_count' => $cursos->count(),
                'students_count' => $registros->pluck('student_id')->unique()->count(),
                'active_students_count' => $registros
                    ->filter(fn (AcaCapRegistration $registro) => $this->registroActivo($registro, $hoy))
                    ->pluck('student_id')
                    ->unique()
                    ->count(),
                'average_progress' => $this->promedio($detalle, 'average_progress'),
                'attendance_records' => $asistencias->count(),
            ],
            'courses' => $detalle,
            'note' => 'Solo lectura. Los promedios usan los contenidos vistos por los alumnos activos y los exámenes del curso.',
        ]);
    }

    /**
     * Acceso según las reglas del aula.
     *
     * @param  Collection<int, AcaStudentSubscription>  $suscripciones
     * @param  array<string, bool>  $permisos
     * @return array{can_access: bool, sources: array<int, string>}
     */
    private function accesoCurso(
        AcaCourse $curso,
        ?AcaCapRegistration $registro,
        Collection $suscripciones,
        array $permisos,
        Carbon $hoy
    ): array {
        $fuentes = [];
        $esEspecializacion = SubscriptionContentPermissions::isSpecializationCourse($curso->type_description);
        $registroConAcceso = $registro !== null && $this->registroConAcceso($registro, $hoy);

        if ($esEspecializacion) {
            // Igual que el aula: un Programa de Especialización se abre con
            // matrícula en el programa.
            if ($registro !== null) {
                $fuentes[] = $registroConAcceso ? 'matricula_vigente' : 'matricula';
            }
        } elseif ($registroConAcceso) {
            $fuentes[] = 'matricula';
        }

        if (SubscriptionContentPermissions::canAccessCourse($permisos, $curso->type_description)) {
            $fuentes[] = 'suscripcion';
        }

        if ((float) ($curso->price ?? 0) === 0.0) {
            $fuentes[] = 'curso_gratuito';
        }

        return [
            'can_access' => $fuentes !== [],
            'sources' => array_values(array_unique($fuentes)),
        ];
    }

    private function registroActivo(AcaCapRegistration $registro, Carbon $hoy): bool
    {
        if (! $registro->status) {
            return false;
        }

        if ($registro->unlimited) {
            return true;
        }

        $fin = $this->fecha($registro->date_end);

        return $fin !== null && $fin->gte($hoy);
    }

    /**
     * Misma comprobación de matrícula que AcaStudentController::checkCourseAccess:
     * ilimitada o con fecha de fin vigente. El estado administrativo no se usa
     * para bloquear el aula.
     */
    private function registroConAcceso(AcaCapRegistration $registro, Carbon $hoy): bool
    {
        if ($registro->unlimited) {
            return true;
        }

        $fin = $this->fecha($registro->date_end);

        return $fin !== null && $fin->gte($hoy);
    }

    private function planDaAcceso(?AcaSubscriptionType $plan, ?string $courseType): bool
    {
        if (! $plan) {
            return false;
        }

        if (SubscriptionContentPermissions::isSpecializationCourse($courseType)) {
            return (bool) $plan->access_specialization_programs;
        }

        if (SubscriptionContentPermissions::isWebinarCourse($courseType)) {
            return (bool) $plan->access_all_webinars;
        }

        return (bool) $plan->access_all_courses;
    }

    private function suscripcionVigente(AcaStudentSubscription $suscripcion, Carbon $hoy): bool
    {
        $inicio = $this->fecha($suscripcion->date_start);
        $fin = $this->fecha($suscripcion->date_end);

        if ($suscripcion->status && ($fin === null || $fin->gte($hoy))) {
            return true;
        }

        return $inicio !== null && $fin !== null && $inicio->lte($hoy) && $fin->gte($hoy);
    }

    private function suscripcionPorVencer(AcaStudentSubscription $suscripcion, Carbon $hoy, int $dias): bool
    {
        $restantes = $this->diasRestantes($suscripcion->date_end, $hoy);

        return $restantes !== null && $restantes >= 0 && $restantes <= $dias && $this->suscripcionVigente($suscripcion, $hoy);
    }

    private function diasRestantes($fecha, Carbon $hoy): ?int
    {
        $fin = $this->fecha($fecha);

        return $fin === null ? null : (int) $hoy->diffInDays($fin->startOfDay(), false);
    }

    private function fecha($valor): ?Carbon
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function fechaHora($valor): ?Carbon
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor);
        } catch (\Throwable) {
            return null;
        }
    }

    private function hoy(): Carbon
    {
        return Carbon::today();
    }

    private function totalContenidos(AcaCourse $curso): int
    {
        return (int) $curso->modules->sum(function ($modulo) {
            return $modulo->themes->sum(function ($tema) {
                return $tema->contents->reject(fn ($contenido) => (int) $contenido->is_file === 4)->count();
            });
        });
    }

    private function porcentaje(int $vistos, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return min(100, (int) round(($vistos / $total) * 100));
    }

    /**
     * @param  Collection<int, AcaStudentExam>  $examenes
     * @return array<string, mixed>
     */
    private function resumenExamenes(Collection $examenes): array
    {
        $calificados = $examenes->filter(fn (AcaStudentExam $examen) => $examen->punctuation !== null && $examen->punctuation !== '');
        $promedio = $calificados->isNotEmpty()
            ? round($calificados->avg(fn (AcaStudentExam $examen) => (float) $examen->punctuation), 2)
            : null;
        $aprobados = $calificados->filter(fn (AcaStudentExam $examen) => (float) $examen->punctuation >= 11)->count();

        return [
            'attempts' => $examenes->count(),
            'graded' => $calificados->count(),
            'average_score' => $promedio,
            'passed' => $aprobados,
            'pass_rate' => $calificados->isNotEmpty() ? round(($aprobados / $calificados->count()) * 100, 1) : null,
        ];
    }

    /**
     * @param  Collection<int, AcaStudentExam>  $examenes
     * @return Collection<int, AcaStudentExam>
     */
    private function examenesDelCurso(Collection $examenes, int $courseId): Collection
    {
        return $examenes->filter(fn (AcaStudentExam $examen) => (int) $this->cursoDeExamen($examen) === $courseId)->values();
    }

    private function cursoDeExamen(AcaStudentExam $examen): ?int
    {
        if ($examen->exam?->course_id) {
            return (int) $examen->exam->course_id;
        }

        return $examen->exam?->module?->course_id;
    }

    /**
     * @param  Collection<int, AcaStudentSubscription>  $suscripciones
     * @return array<string, mixed>
     */
    private function resumenSuscripciones(Collection $suscripciones, Carbon $hoy, int $dias): array
    {
        $vigentes = $suscripciones->filter(fn (AcaStudentSubscription $suscripcion) => $this->suscripcionVigente($suscripcion, $hoy));
        $porVencer = $vigentes->filter(fn (AcaStudentSubscription $suscripcion) => $this->suscripcionPorVencer($suscripcion, $hoy, $dias));
        $vencidas = $suscripciones->filter(fn (AcaStudentSubscription $suscripcion) => $this->fecha($suscripcion->date_end)?->lt($hoy));
        $conFin = $vigentes->filter(fn (AcaStudentSubscription $suscripcion) => $this->fecha($suscripcion->date_end) !== null);
        $primeraPorVencer = $conFin
            ->sortBy(fn (AcaStudentSubscription $suscripcion) => $this->fecha($suscripcion->date_end)?->timestamp)
            ->first();

        return [
            'active_count' => $vigentes->count(),
            'expiring_count' => $porVencer->count(),
            'expired_count' => $vencidas->count(),
            'status' => $porVencer->isNotEmpty() ? 'por_vencer' : ($vigentes->isNotEmpty() ? 'activa' : ($vencidas->isNotEmpty() ? 'vencida' : 'sin_suscripcion')),
            'ends_at' => $primeraPorVencer
                ? $this->fecha($primeraPorVencer->date_end)?->toDateString()
                : null,
            'expiring_soon' => $porVencer->isNotEmpty(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function persona(AcaStudent $estudiante): array
    {
        return [
            'name' => $this->nombrePersona($estudiante->person),
            'document' => $estudiante->person?->number,
            'email' => $estudiante->person?->email,
            'telephone' => $estudiante->person?->telephone,
            'image' => $estudiante->person?->image,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function personaDocente(AcaTeacher $docente): array
    {
        return [
            'name' => $this->nombrePersona($docente->person),
            'document' => $docente->person?->number,
            'email' => $docente->person?->email,
            'telephone' => $docente->person?->telephone,
            'occupation' => $docente->person?->ocupacion,
            'image' => $docente->person?->image,
        ];
    }

    private function nombrePersona(?Person $persona): string
    {
        if (! $persona) {
            return 'Sin nombre';
        }

        return trim((string) ($persona->full_name
            ?: implode(' ', array_filter([
                $persona->father_lastname,
                $persona->mother_lastname,
                $persona->names,
            ]))));
    }

    private function promedio(array $filas, string $campo): float
    {
        $valores = collect($filas)->pluck($campo)->filter(fn ($valor) => is_numeric($valor));

        return $valores->isNotEmpty() ? round((float) $valores->avg(), 1) : 0.0;
    }
}
