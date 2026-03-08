<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaStudentExam;
use Modules\Academic\Entities\AcaStudentAttendance;
use Modules\Academic\Entities\AcaStudentParticipation;
use Modules\Academic\Entities\AcaStudentGrade;
use Modules\Academic\Entities\AcaStudentGradeDetail;
use Auth;

class AcaGradeManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = AcaCourse::where('status', true)->orderBy('description', 'ASC')->get();

        return Inertia::render('Academic::Courses/GradeManagement', [
            'courses' => $courses,
        ]);
    }

    /**
     * Search students with grades for a course
     */
    public function search(Request $request)
    {
        $request->validate([
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $courseId = $request->input('course_id');

        $course = AcaCourse::with(['modules'])->findOrFail($courseId);

        // Obtener estudiantes registrados en el curso con filtro de fechas
        $query = AcaCapRegistration::with(['student.person'])
            ->where('course_id', $courseId)
            ->where('status', true);

        // Filtrar por rango de fechas (date_start de la matrícula)
        if ($request->date_start) {
            $query->where('date_start', '>=', $request->date_start);
        }
        if ($request->date_end) {
            $query->where('date_start', '<=', $request->date_end);
        }

        $registrations = $query->get();

        // Obtener certificados del curso
        $certificates = AcaCertificate::where('course_id', $courseId)->get();

        // Obtener módulos del curso
        $modules = $course->modules->map(function ($module) {
            return [
                'id' => $module->id,
                'description' => $module->description,
            ];
        });

        // Preparar datos de estudiantes con estructura para notas
        $students = $registrations->map(function ($reg) use ($certificates, $modules, $courseId) {
            $hasCertificate = $certificates->contains('student_id', $reg->student->id);

            // Verificar si existen calificaciones guardadas para este estudiante
            $savedGrade = AcaStudentGrade::where('registration_id', $reg->id)->first();

            // Estructura de módulos con notas
            if ($savedGrade) {
                // Si existen calificaciones guardadas, cargarlas
                $savedDetails = AcaStudentGradeDetail::where('grade_id', $savedGrade->id)->get();
                
                $studentModules = $modules->map(function ($module) use ($savedDetails) {
                    $detail = $savedDetails->firstWhere('module_id', $module['id']);
                    
                    return [
                        'module_id' => $module['id'],
                        'module_name' => $module['description'],
                        'exam_score' => $detail ? $detail->exam_score : null,
                        'attendance_score' => $detail ? $detail->attendance_score : null,
                        'participation_score' => $detail ? $detail->participation_score : null,
                        'average' => $detail ? $detail->average : null,
                    ];
                })->toArray();

                $finalAverage = $savedGrade->final_average;
            } else {
                // Si no existen calificaciones guardadas, calcular desde las tablas originales
                // Obtener exámenes del estudiante para este curso
                $studentExams = AcaStudentExam::whereHas('exam', function($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })->where('student_id', $reg->student->id)->get();

                // Obtener asistencias del estudiante para este curso (agrupadas por módulo)
                $studentAttendances = AcaStudentAttendance::where('course_id', $courseId)
                    ->where('student_id', $reg->student->id)
                    ->get();

                // Obtener conteo de contenidos con control de asistencia por módulo
                $contentsWithAttendanceControl = AcaStudentAttendance::where('course_id', $courseId)
                    ->select('module_id')
                    ->selectRaw('COUNT(DISTINCT content_id) as total_contents')
                    ->groupBy('module_id')
                    ->get()
                    ->keyBy('module_id');

                // Obtener participaciones del estudiante para este curso
                $studentParticipations = AcaStudentParticipation::where('course_id', $courseId)
                    ->where('student_id', $reg->student->id)
                    ->get();

                $studentModules = $modules->map(function ($module) use ($studentExams, $studentAttendances, $contentsWithAttendanceControl, $studentParticipations) {
                    // Buscar examen del estudiante para este módulo
                    $exam = $studentExams->first(function ($se) use ($module) {
                        return $se->exam && $se->exam->module_id == $module['id'];
                    });

                    // Buscar asistencia del estudiante para este módulo
                    $attendances = $studentAttendances->where('module_id', $module['id']);
                    $attendanceCount = $attendances->count();

                    // Obtener total de contenidos con control de asistencia para este módulo
                    $totalContents = $contentsWithAttendanceControl->get($module['id'])?->total_contents ?? 0;

                    // Calcular promedio de asistencia: (asistencias / contenidos_controlados) * 12
                    $attendanceScore = $totalContents > 0
                        ? round(($attendanceCount / $totalContents) * 12, 2)
                        : null;

                    // Buscar participaciones del estudiante para este módulo
                    $participations = $studentParticipations->where('module_id', $module['id']);
                    $participationCount = $participations->count();

                    // Calcular promedio de participación: suma de scores / cantidad
                    $participationScore = $participationCount > 0
                        ? round($participations->sum('participation_score') / $participationCount, 2)
                        : null;

                    // Calcular promedio del módulo
                    // Fórmula: (examen * 60%) + (asistencia * 20%) + (participación * 20%)
                    $examVal = $exam ? (float) $exam->punctuation : 0;
                    $attendanceVal = $attendanceScore ?? 0;
                    $participationVal = $participationScore ?? 0;

                    $average = round(($examVal * 0.6) + ($attendanceVal * 0.2) + ($participationVal * 0.2), 2);

                    return [
                        'module_id' => $module['id'],
                        'module_name' => $module['description'],
                        'exam_score' => $exam ? (float) $exam->punctuation : null,
                        'attendance_score' => $attendanceScore,
                        'participation_score' => $participationScore,
                        'average' => $average,
                    ];
                })->toArray();

                // Calcular promedio final del estudiante (promedio de todos los módulos)
                $finalAverage = collect($studentModules)->avg('average');
                $finalAverage = $finalAverage !== null ? round($finalAverage, 2) : null;
            }

            return [
                'id' => $reg->student->id,
                'registration_id' => $reg->id,
                'name' => $reg->student->person ? $reg->student->person->full_name : 'Sin nombre',
                'course_name' => $reg->course->description ?? '',
                'has_certificate' => $hasCertificate,
                'modules' => $studentModules,
                'final_average' => $finalAverage,
            ];
        });

        return response()->json([
            'success' => true,
            'students' => $students,
            'course' => [
                'id' => $course->id,
                'description' => $course->description,
            ],
            'modules' => $modules,
        ]);
    }

    /**
     * Store or update grades for students
     */
    public function store(Request $request)
    {
        $request->validate([
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:aca_students,id',
            'grades.*.registration_id' => 'required|exists:aca_cap_registrations,id',
            'grades.*.course_id' => 'required|exists:aca_courses,id',
            'grades.*.final_average' => 'nullable|numeric|min:0|max:20',
            'grades.*.modules' => 'required|array',
            'grades.*.modules.*.module_id' => 'required|exists:aca_modules,id',
            'grades.*.modules.*.exam_score' => 'nullable|numeric|min:0|max:20',
            'grades.*.modules.*.attendance_score' => 'nullable|numeric|min:0|max:12',
            'grades.*.modules.*.participation_score' => 'nullable|numeric|min:0|max:20',
            'grades.*.modules.*.average' => 'nullable|numeric|min:0|max:20',
        ]);

        $userId = Auth::id();

        foreach ($request->grades as $gradeData) {
            $studentId = $gradeData['student_id'];
            $registrationId = $gradeData['registration_id'];
            $courseId = $gradeData['course_id'];
            $finalAverage = $gradeData['final_average'];
            $modules = $gradeData['modules'];
            $observations = $gradeData['observations'] ?? null;

            // Calcular promedio final (promedio de todos los promedios de módulos)
            $averages = array_filter(array_column($modules, 'average'), function($val) {
                return $val !== null && $val !== '';
            });
            $calculatedFinalAverage = count($averages) > 0 
                ? round(array_sum($averages) / count($averages), 2) 
                : null;
            
            $finalAvg = $finalAverage ?? $calculatedFinalAverage;
            $approved = $finalAvg !== null && $finalAvg >= 11;

            // Buscar o crear registro en aca_student_grades
            $studentGrade = AcaStudentGrade::updateOrCreate(
                [
                    'registration_id' => $registrationId,
                ],
                [
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'final_average' => $finalAvg,
                    'approved' => $approved,
                    'observations' => $observations,
                    'created_by' => $userId,
                    'registered_at' => now(),
                ]
            );

            // Agregar al historial de ediciones
            if ($studentGrade->wasRecentlyCreated === false) {
                $studentGrade->addEditHistory($userId);
                $studentGrade->save();
            }

            // Guardar detalles por módulo
            foreach ($modules as $moduleData) {
                $moduleId = $moduleData['module_id'];
                $examScore = $moduleData['exam_score'];
                $attendanceScore = $moduleData['attendance_score'];
                $participationScore = $moduleData['participation_score'];
                $average = $moduleData['average'];

                // Calcular promedio del módulo
                $examVal = $examScore !== null && $examScore !== '' ? (float)$examScore : 0;
                $attendanceVal = $attendanceScore !== null && $attendanceScore !== '' ? (float)$attendanceScore : 0;
                $participationVal = $participationScore !== null && $participationScore !== '' ? (float)$participationScore : 0;

                $calculatedAverage = ($examVal * 0.6) + ($attendanceVal * 0.2) + ($participationVal * 0.2);
                $avg = $average ?? $calculatedAverage;
                $moduleApproved = $avg !== null && $avg >= 11;

                AcaStudentGradeDetail::updateOrCreate(
                    [
                        'grade_id' => $studentGrade->id,
                        'module_id' => $moduleId,
                    ],
                    [
                        'exam_score' => $examScore,
                        'attendance_score' => $attendanceScore,
                        'participation_score' => $participationScore,
                        'average' => round($avg, 2),
                        'module_approved' => $moduleApproved,
                    ]
                );
            }

            // Si está aprobado y no tiene certificado, crear uno
            if ($approved) {
                AcaCertificate::firstOrCreate(
                    [
                        'registration_id' => $registrationId,
                        'course_id' => $courseId,
                    ],
                    [
                        'student_id' => $studentId,
                        'image' => null,
                        'content' => null,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Calificaciones guardadas correctamente',
        ]);
    }
}
