<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExcelExportJob;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleDocument;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Jobs\ExportSalesExcel;
use Modules\Academic\Jobs\ExportStudentPerformanceExcel;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaStudentGrade;
use Modules\Academic\Entities\AcaStudentGradeDetail;
use Modules\Academic\Entities\AcaStudentExam;
use Modules\Academic\Entities\AcaStudentAttendance;
use Modules\Academic\Entities\AcaStudentParticipation;

class AcaReportsController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Academic::Reports/Dashboard');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function studentPaymentBank()
    {
        $paymentMethods = PaymentMethod::with('bankAccount.bank')->get();

         return Inertia::render('Academic::Reports/StudentPaymentBank',[
            'paymentMethods' => $paymentMethods
         ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function studentPaymentBankTable(Request $request)
    {
        // 1. Validación de entradas
        $this->validate($request, [
            'search'           => 'nullable|string|max:255',
            'issue_date'       => [
                'required',
                'string',
                'regex:/^\d{4}-\d{2}-\d{2}( a \d{4}-\d{2}-\d{2})?$/'
            ],
            'paymentMethod_id' => 'nullable|integer',
        ]);

        // 2. Construcción de la consulta
        $query = Sale::query()->select([
            'id',
            'user_id',
            'client_id',
            'local_id',
            'total',
            'advancement',
            'total_discount',
            'payments',
            'sale_date',
        ]);

        // Carga de relaciones
        $query->with(['saleProduct', 'document', 'client']);

        // 3. Aplicación de Filtros

        // Filtro por método de pago en JSON
        if ($request->filled('paymentMethod_id')) {
            $methodId = (int) $request->paymentMethod_id;
            $query->whereJsonContains('payments', [['type' => $methodId]]);
        }

        // Filtro por rango de fechas
        if ($request->filled('issue_date')) {
            $dates = explode(' a ', $request->issue_date);
            try {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = count($dates) === 2
                    ? Carbon::parse($dates[1])->endOfDay()
                    : Carbon::parse($dates[0])->endOfDay();

                $query->whereBetween('sale_date', [$startDate, $endDate]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Formato de fecha inválido.'], 400);
            }
        }

        // Filtro por Nombre o DNI del Cliente
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('client', function ($q) use ($searchTerm) {
                $q->where('full_name', 'like', '%' . $searchTerm . '%')
                ->orWhere('number', 'like', '%' . $searchTerm . '%');
            });
        }

        // 4. Ejecución y Transformación de Datos
        $items = $query->get()->map(function ($sale) {
            $rawPayments = $sale->payments;

            // Limpieza de JSON: Maneja strings, nulls y doble serialización
            if (is_string($rawPayments)) {
                $decoded = json_decode($rawPayments, true);

                // Si después de decodificar sigue siendo un string, decodificamos de nuevo (doble escape)
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }

                $sale->payments = is_array($decoded) ? $decoded : [];
            } elseif (is_null($rawPayments)) {
                $sale->payments = [];
            }

            return $sale;
        });

        // 5. Respuesta
        return response()->json(['items' => $items]);
    }

    public function exportStudentPaymentBankSales(Request $request): JsonResponse
    {
        // Opcional: Validar los filtros que se pasan al Job
        $this->validate($request, [
            'search'           => 'nullable|string|max:255',
            'issue_date'       => [
                'required',
                'string',
                // Cambiamos la expresión regular para aceptar un solo YYYY-MM-DD O el rango
                'regex:/^\d{4}-\d{2}-\d{2}( a \d{4}-\d{2}-\d{2})?$/'
            ],
            'paymentMethod_id' => 'nullable|integer',
        ]);
        $filters = [
            'issue_date' => $request->get('issue_date'),
            'search' => $request->get('search'),
            'paymentMethod_id' => $request->get('paymentMethod_id')
        ];
        // 1. Crear un registro en la tabla `excel_export_jobs` para rastrear el estado
        $excelExportJob = ExcelExportJob::create([
            'user_id' => Auth::id(),
            'report_type' => 'VENTAS',
            'status' => 'pending',
            'filters' => json_encode($filters), // Guardar los filtros para referencia
        ]);

        // 2. Despachar el Job de exportación a la cola
        ExportSalesExcel::dispatch(
            $filters,
            $excelExportJob->id,
            Auth::id()
        )->onQueue('exports'); // O la cola que uses para exportaciones

        return response()->json([
            'message' => 'La exportación de ventas se ha iniciado. Se le notificará cuando esté lista para descargar.',
            'job_id' => $excelExportJob->id,
        ], 202); // 202 Accepted, indica que la petición ha sido aceptada para procesamiento.
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function exportStatus($id)
    {
       // Busca el job por ID y verifica que pertenezca al usuario
        $excelExportJob = ExcelExportJob::where('id', $id)
                                        ->where('user_id', Auth::id())
                                        ->first();

        if (!$excelExportJob) {
            return response()->json(['message' => 'Estado de exportación no encontrado o no autorizado.'], 404);
        }

        return response()->json($excelExportJob);
    }

    /**
     * Update the specified resource in storage.
     */
    public function expiredSubscriptions()
    {
        return Inertia::render('Academic::Reports/SubscriptionsExpired');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    public function studentPerformanceReport()
    {
        $courses = AcaCourse::where('status', 1)->orderBy('description')->get();

        return Inertia::render('Academic::Reports/StudentPerformanceReport', [
            'courses' => $courses
        ]);
    }

    public function studentPerformanceTable(Request $request)
    {
        $this->validate($request, [
            'course_id' => 'required|integer',  // Cambiado a required
            'year' => 'nullable|integer',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $courseId = $request->input('course_id');

        // Obtener el curso (siempre debe haber un courseId seleccionado)
        $course = AcaCourse::with(['modules'])->findOrFail($courseId);
        $modules = $course->modules->map(function ($module) {
            return [
                'id' => $module->id,
                'description' => $module->description,
            ];
        });

        $query = AcaCapRegistration::with(['student.person', 'course'])
            ->select('aca_cap_registrations.*')
            ->where('status', true)
            ->where('course_id', $courseId);

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        $registrations = $query->orderBy('created_at', 'desc')->get();

        // Obtener certificados del curso
        $certificates = AcaCertificate::where('course_id', $courseId)->get();

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
                        'participation_score' => $detail ? $detail->participation_score : null,
                        'attendance_score' => $detail ? $detail->attendance_score : null,
                        'exam_score' => $detail ? $detail->exam_score : null,
                        'average' => $detail ? $detail->average : null,
                    ];
                })->toArray();

                $finalAverage = $savedGrade->final_average;
            } else {
                // Si no existen calificaciones guardadas, calcular desde las tablas originales
                $studentModules = $modules->map(function ($module) use ($courseId, $reg) {
                    // Exámenes
                    $studentExams = AcaStudentExam::whereHas('exam', function($query) use ($courseId, $module) {
                        $query->where('course_id', $courseId);
                        if ($module) {
                            $query->where('module_id', $module['id']);
                        }
                    })->where('student_id', $reg->student->id)->get();

                    $exam = $studentExams->first();
                    $examScore = $exam ? (float) $exam->punctuation : null;

                    // Asistencias
                    $attendances = AcaStudentAttendance::where('course_id', $courseId)
                        ->where('student_id', $reg->student->id)
                        ->where('module_id', $module['id'] ?? null)
                        ->get();
                    $attendanceCount = $attendances->count();
                    $totalContents = AcaStudentAttendance::where('course_id', $courseId)
                        ->whereNotNull('module_id')
                        ->select('module_id')
                        ->selectRaw('COUNT(DISTINCT content_id) as total_contents')
                        ->groupBy('module_id')
                        ->get()
                        ->keyBy('module_id')
                        ->get($module['id'])?->total_contents ?? 0;
                    $attendanceScore = $totalContents > 0 ? round(($attendanceCount / $totalContents) * 12, 2) : null;

                    // Participaciones
                    $participations = AcaStudentParticipation::where('course_id', $courseId)
                        ->where('student_id', $reg->student->id)
                        ->where('module_id', $module['id'] ?? null)
                        ->get();
                    $participationCount = $participations->count();
                    $participationScore = $participationCount > 0
                        ? round($participations->sum('participation_score') / $participationCount, 2)
                        : null;

                    // Calcular promedio del módulo
                    $examVal = $examScore ?? 0;
                    $attendanceVal = $attendanceScore ?? 0;
                    $participationVal = $participationScore ?? 0;
                    $average = round(($examVal * 0.6) + ($attendanceVal * 0.2) + ($participationVal * 0.2), 2);

                    return [
                        'module_id' => $module['id'],
                        'module_name' => $module['description'],
                        'participation_score' => $participationScore,
                        'attendance_score' => $attendanceScore,
                        'exam_score' => $examScore,
                        'average' => $average,
                    ];
                })->toArray();

                // Calcular promedio final
                $finalAverage = collect($studentModules)->avg('average');
                $finalAverage = $finalAverage !== null ? round($finalAverage, 2) : null;
            }

            return [
                'id' => $reg->student->id,
                'registration_id' => $reg->id,
                'student' => [
                    'full_name' => $reg->student->person ? $reg->student->person->full_name : 'Sin nombre',
                    'dni' => $reg->student->person ? $reg->student->person->number : '',
                ],
                'course' => [
                    'description' => $reg->course->description ?? '',
                ],
                'created_at' => $reg->created_at->format('Y-m-d'),
                'status' => $reg->status,
                'has_certificate' => $hasCertificate,
                'final_average' => $finalAverage,
                'modules' => $studentModules,
            ];
        });

        return response()->json([
            'items' => $students,
            'modules' => $modules,
        ]);
    }

    public function exportStudentPerformance(Request $request)
    {
        $this->validate($request, [
            'course_id' => 'nullable|integer',
            'year' => 'nullable|integer',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $userId = Auth::id();

        $filters = $request->only(['course_id', 'year', 'month']);

        $excelExportJob = ExcelExportJob::create([
            'user_id' => $userId,
            'job' => 'ExportStudentPerformanceExcel',
            'report_type' => 'student_performance',
            'status' => 'pending',
            'progress' => 0,
            'filters' => $filters,
        ]);

        ExportStudentPerformanceExcel::dispatch($userId, $excelExportJob->id, $filters);

        return response()->json([
            'job_id' => $excelExportJob->id,
            'message' => 'Exportación iniciada',
        ]);
    }

    public function exportStudentPerformanceStatus(Request $request, $jobId)
    {
        $job = ExcelExportJob::find($jobId);

        if (!$job) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Trabajo no encontrado',
            ], 404);
        }

        return response()->json([
            'status' => $job->status,
            'progress' => $job->progress,
            'file_name' => $job->file_name,
            'download_url' => $job->download_url,
            'error_message' => $job->error_message,
        ]);
    }
}
