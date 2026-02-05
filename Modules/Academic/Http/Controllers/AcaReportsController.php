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
}
