<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExcelExportJob;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
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
use Modules\Academic\Jobs\ExportEnrollmentDocumentsExcel;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaStudentGrade;
use Modules\Academic\Entities\AcaStudentGradeDetail;

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
            'course_id' => 'required|integer',
            'year' => 'nullable|integer',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $courseId = $request->input('course_id');

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

        $certificates = AcaCertificate::where('course_id', $courseId)->get();

        $students = $registrations->map(function ($reg) use ($certificates, $modules) {
            $hasCertificate = $certificates->contains('student_id', $reg->student->id);

            $savedGrade = AcaStudentGrade::where('registration_id', $reg->id)->first();

            if ($savedGrade) {
                $savedDetails = AcaStudentGradeDetail::where('grade_id', $savedGrade->id)->get();

                $studentModules = $modules->map(function ($module) use ($savedDetails) {
                    $detail = $savedDetails->firstWhere('module_id', $module['id']);

                    return [
                        'module_id' => $module['id'],
                        'module_name' => $module['description'],
                        'participation_score' => $detail ? $detail->participation_score : null,
                        'exam_score' => $detail ? $detail->exam_score : null,
                        'average' => $detail ? $detail->average : null,
                    ];
                })->toArray();

                $finalAverage = $savedGrade->final_average;
            } else {
                $studentModules = $modules->map(function ($module) {
                    return [
                        'module_id' => $module['id'],
                        'module_name' => $module['description'],
                        'participation_score' => null,
                        'exam_score' => null,
                        'average' => null,
                    ];
                })->toArray();

                $finalAverage = null;
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

    public function enrollmentDocumentsReport()
    {
        // Obtener usuarios que tienen registros en aca_cap_registrations
        $userIds = \Modules\Academic\Entities\AcaCapRegistration::whereNotNull('user_id_registers')
            ->distinct()
            ->pluck('user_id_registers');

        $users = \App\Models\User::whereIn('id', $userIds)->get(['id', 'name']);

        return Inertia::render('Academic::Reports/EnrollmentDocumentsReport', [
            'users' => $users
        ]);
    }

    public function enrollmentDocumentsTable(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'nullable',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $userId = $request->input('user_id');
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');

        $query = AcaCapRegistration::with([
            'student.person',
            'course',
            'document'
        ])->where('status', true);

        // Filtrar por usuario responsable
        if ($userId === 'sin') {
            $query->whereNull('user_id_registers');
        } elseif ($userId === 'con') {
            $query->whereNotNull('user_id_registers');
        } elseif ($userId) {
            $query->where('user_id_registers', $userId);
        }

        // Filtrar por rango de fechas
        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        $registrations = $query->orderBy('created_at', 'desc')->get();

        // Transformar datos
        $items = $registrations->map(function ($reg) {
            return [
                'id' => $reg->id,
                'student_name' => $reg->student?->person?->full_name ?? 'Sin nombre',
                'registration_date' => $reg->created_at->format('Y-m-d H:i:s'),
                'course_name' => $reg->course?->description ?? 'Sin curso',
                'comprobante' => $reg->saleDocument
                    ? $reg->saleDocument->invoice_serie . '-' . $reg->saleDocument->invoice_correlative
                    : '-',
                'responsible' => $reg->user_id_registers
                    ? (User::find($reg->user_id_registers)?->name ?? 'Usuario no encontrado')
                    : 'Sin responsable',
            ];
        });

        return response()->json([
            'items' => $items,
        ]);
    }

    public function exportEnrollmentDocuments(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'nullable',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $userId = Auth::id();

        $filters = $request->only(['user_id', 'date_start', 'date_end']);

        $excelExportJob = ExcelExportJob::create([
            'user_id' => $userId,
            'job' => 'ExportEnrollmentDocumentsExcel',
            'report_type' => 'enrollment_documents',
            'status' => 'pending',
            'progress' => 0,
            'filters' => $filters,
        ]);

        ExportEnrollmentDocumentsExcel::dispatch($userId, $excelExportJob->id, $filters);

        return response()->json([
            'job_id' => $excelExportJob->id,
            'message' => 'Exportación iniciada',
        ]);
    }

    public function exportEnrollmentDocumentsStatus(Request $request, $jobId)
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
