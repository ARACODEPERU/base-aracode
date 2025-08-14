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
        // Validar y sanear los inputs para evitar errores de fecha o tipos incorrectos
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

        // Extraer los parámetros de forma segura
        $searchTerm        = $request->get('search') ?? null;
        $issueDateRange    = $request->get('issue_date') ?? null;
        $paymentMethodId   = $request->get('paymentMethod_id') ?? null;

        // 1. Iniciar la consulta base
        // Se incluyen los campos directamente necesarios o los que se van a transformar.
        // Es una buena práctica usar 'id' en el select incluso si no se muestra,
        // ya que Eloquent a menudo lo necesita internamente.
        $query = Sale::query()->select([
            'id', // Siempre incluir el ID para Eloquent
            'user_id',
            'client_id',
            'local_id',
            'total',
            'advancement',
            'total_discount',
            'payments',
            'sale_date',
        ]);

        // Cargar las relaciones necesarias para evitar el problema N+1
        // `saleProduct` se cargará con cada venta.
        $query->with('saleProduct');
        $query->with('document');
        $query->with('client');
        // 2. Filtro por Tipo de Medio de Pago (desde JSON 'payments')
        // Si paymentMethodId es un solo ID numérico
        if ($paymentMethodId !== null) { // Usar !== null para manejar 0 si fuera un ID válido
            $query->whereJsonContains('payments',['type'=> $paymentMethodId]);
            // Nota: Si 'payments' puede ser un array de objetos JSON (e.g., [{"type": 4}, {"type": 5}]),
            // `whereJsonContains('payments', ['type' => $paymentMethodId])` es la forma correcta.
            // Si cada 'payment' es un objeto individual y 'type' está directamente en la raíz del JSON,
            // (e.g., {"type": 4, "amount": "670.00"}), la forma `whereJsonContains('payments', ['type' => $paymentMethodId])` sigue siendo la mejor opción.
        }

        // 3. Filtro por Rango/Fecha Única de Venta ('sale_date')
        if ($issueDateRange) {
            // Intentar dividir la cadena. Si es un solo día, explode devolverá un array con un solo elemento.
            $dates = explode(' a ', $issueDateRange);

            try {
                if (count($dates) === 2) {
                    // Es un rango de fechas
                    $startDate = Carbon::parse($dates[0])->startOfDay();
                    $endDate = Carbon::parse($dates[1])->endOfDay();
                } else {
                    // Es una sola fecha
                    $startDate = Carbon::parse($dates[0])->startOfDay();
                    $endDate = Carbon::parse($dates[0])->endOfDay(); // El final del mismo día
                }

                $query->whereBetween('sale_date', [$startDate, $endDate]);

            } catch (\Exception $e) {
                // Loguear el error para depuración
                Log::error("Error al parsear fecha en issue_date: {$issueDateRange} - " . $e->getMessage());
                return response()->json(['error' => 'Formato de fecha no válido en el filtro de fecha.'], 400);
            }
        }
        // 4. Filtro por Nombre o DNI del Cliente ('full_name' o 'number' en tabla 'people')
        // Asumiendo la relación `client` en el modelo Sale
        if ($searchTerm) {
            $query->whereHas('client', function (Builder $q) use ($searchTerm) {
                // Buscamos 'full_name' que contenga el término O 'number' que sea EXACTAMENTE el término
                // Es importante si 'number' es DNI y se espera una coincidencia exacta.
                $q->where('full_name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('number', $searchTerm); // Coincidencia exacta para DNI/RUC
            });
        }

        // 5. Obtener los resultados
        // Considera paginación si la tabla es grande. Usar `paginate()` en lugar de `get()`.
        // Para una tabla, generalmente quieres paginación.
        // Ejemplo con paginación (ajusta según tus necesidades):
        // $perPage = $request->input('per_page', 15);
        // $items = $query->paginate($perPage);
        // $query->ddRawSql();
        // Si solo quieres todos los resultados sin paginación (como en tu original):
        $items = $query->get();

        // 6. Transformar los datos si es necesario (parsear 'payments' de JSON a array PHP)
        // Esto solo es necesario si `payments` no está casteado a 'json' en el modelo.
        // Si ya tienes `protected $casts = ['payments' => 'json'];` en tu modelo `Sale`,
        // Laravel lo hará automáticamente y esta transformación no sería estrictamente necesaria,
        // pero no hace daño si quieres asegurarte.
        $items->transform(function ($sale) {
            // Laravel ya debería haber casteado 'payments' a un array/objeto si tienes `protected $casts = ['payments' => 'json'];`
            // Si `payments` sigue siendo una cadena JSON aquí, la línea de abajo es necesaria.
            if (is_string($sale->payments)) {
                $sale->payments = json_decode($sale->payments, true);
            }
            return $sale;
        });

        // Retornar la respuesta JSON
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
