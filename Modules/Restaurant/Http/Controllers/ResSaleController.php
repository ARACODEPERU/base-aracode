<?php

namespace Modules\Restaurant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\PettyCash;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Restaurant\Entities\ResCategory;
use Modules\Restaurant\Entities\ResSale;
use Modules\Restaurant\Entities\ResSaleDetail;
use Modules\Restaurant\Services\ResRecipeConsumptionService;

class ResSaleController extends Controller
{
    public function __construct(
        protected ResRecipeConsumptionService $consumptionService
    ) {}
    public function index()
    {
        $sales = ResSale::query()
            ->join('people', 'person_id', 'people.id')
            ->select(
                'res_sales.id',
                'res_sales.sale_date',
                'res_sales.sale_hour',
                'res_sales.correlative',
                'res_sales.petty_cash_id',
                'res_sales.user_id',
                'res_sales.person_id',
                'res_sales.local_id',
                'res_sales.total',
                'res_sales.total_discount',
                'res_sales.payments',
                'res_sales.queue_status',
                'people.full_name'
            );

        if (request()->filled('search')) {
            $sales->where('people.full_name', 'like', '%' . request()->input('search') . '%');
        }

        if (request()->filled('date_start') && request()->filled('date_end')) {
            $sales->whereBetween('sale_date', [request()->input('date_start'), request()->input('date_end')]);
        }

        if (request()->filled('queue_status') && request()->input('queue_status') !== '00') {
            $sales->where('res_sales.queue_status', request()->input('queue_status'));
        }

        $sales = $sales->orderByDesc('res_sales.id')->paginate(10);

        return Inertia::render('Restaurant::Sales/List', [
            'sales' => $sales,
            'filters' => request()->all(),
        ]);
    }

    public function create()
    {
        $clientDefault = Person::find(1);
        $comandas = ResCategory::with('subcategories.comandas.presentation')
            ->with('comandas.presentation')
            ->whereNull('category_id')
            ->get();
        $paymentMethods = PaymentMethod::all();
        $documentTypes = DB::table('identity_document_type')->get();
        $saleDocumentTypes = DB::table('sale_document_types')
            ->whereIn('sunat_id', ['01', '03', '80'])
            ->get();
        $departments = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                'districts.name AS district_name',
                'provinces.name AS province_name',
                'departments.name AS department_name',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS city_name")
            )
            ->get();

        return Inertia::render('Restaurant::Sales/Create', [
            'clientDefault' => $clientDefault,
            'documentTypes' => $documentTypes,
            'saleDocumentTypes' => $saleDocumentTypes,
            'departments' => $departments,
            'comandas' => $comandas,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:people,id',
            'document_type_id' => 'required|string|in:80,01,03',
            'total' => 'required|numeric|min:0.01',
            'comandas' => 'required|array|min:1',
            'comandas.*.id' => 'required|integer|exists:res_comandas,id',
            'comandas.*.quantity' => 'required|numeric|min:1',
            'comandas.*.price' => 'required|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.type' => 'required|integer',
            'payments.*.amount' => 'required|numeric|min:0',
        ]);

        $paymentsTotal = collect($validated['payments'])->sum('amount');
        if (round((float) $validated['total'], 2) !== round((float) $paymentsTotal, 2)) {
            return response()->json([
                'success' => false,
                'message' => 'El total de venta no coincide con el total de los pagos',
            ], 422);
        }

        if ($validated['document_type_id'] === '01') {
            $person = Person::find($validated['client_id']);
            $hasRuc = $person
                && (string) $person->document_type_id === '6'
                && preg_match('/^\d{11}$/', (string) $person->number);

            if (! $hasRuc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Para factura electrónica el cliente debe estar registrado con RUC de 11 dígitos.',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $pettyCash = PettyCash::firstOrCreate([
                'user_id' => Auth::id(),
                'state' => 1,
                'local_sale_id' => Auth::user()->local_id,
            ], [
                'date_opening' => Carbon::now()->format('Y-m-d'),
                'time_opening' => date('H:i:s'),
                'income' => 0,
            ]);

            $sale = ResSale::create([
                'sale_date' => Carbon::now()->format('Y-m-d'),
                'sale_hour' => Carbon::now()->format('H:i:s'),
                'user_id' => Auth::id(),
                'petty_cash_id' => $pettyCash->id,
                'person_id' => $validated['client_id'],
                'local_id' => Auth::user()->local_id,
                'total' => $validated['total'],
                'payments' => $validated['payments'],
                'queue_status' => '01',
                'type_name_document' => $validated['document_type_id'],
            ]);

            foreach ($validated['comandas'] as $comanda) {
                ResSaleDetail::create([
                    'sale_id' => $sale->id,
                    'comanda_id' => $comanda['id'],
                    'quantity' => $comanda['quantity'],
                    'price' => $comanda['price'],
                    'preparation_status' => 'pendiente',
                ]);
            }

            $consumption = $this->consumptionService->consumeForSale($sale);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la venta: ' . $e->getMessage(),
            ], 500);
        }

        $response = [
            'success' => true,
            'message' => 'Cobro registrado con éxito',
            'sale_id' => $sale->id,
        ];

        if (! empty($consumption['warnings'])) {
            $response['stock_warnings'] = $consumption['warnings'];
            $response['message'] .= '. Atención: algunos insumos tenían stock insuficiente.';
        }

        return response()->json($response);
    }

    public function edit($id)
    {
        $clients = Person::where('is_client', true)->get();
        $comandas = ResCategory::with('subcategories.comandas.presentation')
            ->with('comandas.presentation')
            ->whereNull('category_id')
            ->get();
        $paymentMethods = PaymentMethod::all();

        $sale = ResSale::with('details.comanda.presentation')->findOrFail($id);

        return Inertia::render('Restaurant::Sales/Edit', [
            'clients' => $clients,
            'comandas' => $comandas,
            'paymentMethods' => $paymentMethods,
            'sale' => $sale,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'client_id' => 'required|integer|exists:people,id',
            'total' => 'required|numeric|min:0.01',
            'comandas' => 'required|array|min:1',
            'comandas.*.id' => 'required|integer|exists:res_comandas,id',
            'comandas.*.quantity' => 'required|numeric|min:1',
            'comandas.*.price' => 'required|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.type' => 'required|integer',
            'payments.*.amount' => 'required|numeric|min:0',
            'queue_status' => 'nullable|string|in:01,02,03,04,99',
        ]);

        $paymentsTotal = collect($validated['payments'])->sum('amount');
        if (round((float) $validated['total'], 2) !== round((float) $paymentsTotal, 2)) {
            return response()->json([
                'success' => false,
                'message' => 'El total de venta no coincide con el total de los pagos',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $sale = ResSale::findOrFail($id);

            $sale->update([
                'user_id' => Auth::id(),
                'person_id' => $validated['client_id'],
                'total' => $validated['total'],
                'payments' => $validated['payments'],
                'queue_status' => $validated['queue_status'] ?? $sale->queue_status,
            ]);

            ResSaleDetail::where('sale_id', $id)->delete();

            foreach ($validated['comandas'] as $comanda) {
                ResSaleDetail::create([
                    'sale_id' => $sale->id,
                    'comanda_id' => $comanda['id'],
                    'quantity' => $comanda['quantity'],
                    'price' => $comanda['price'],
                    'preparation_status' => $comanda['preparation_status'] ?? 'pendiente',
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la venta: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Venta actualizada con éxito',
        ]);
    }

    public function destroy($id)
    {
        $sale = ResSale::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada',
            ], 404);
        }

        if ($sale->queue_status === '99') {
            return response()->json([
                'success' => false,
                'message' => 'La venta ya está anulada',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $this->consumptionService->voidSaleConsumption((int) $id);
            $sale->update(['queue_status' => '99']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al anular la venta: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Venta anulada',
        ]);
    }

    public function cuisine()
    {
        $comandas = ResSale::with('details.comanda')
            ->whereIn('queue_status', ['01', '02'])
            ->orderBy('id')
            ->get();

        return Inertia::render('Restaurant::Sales/Cuisine', [
            'comandas' => $comandas,
        ]);
    }

    public function getSale($id)
    {
        $sale = ResSale::with('details.comanda')
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['sale' => $sale]);
    }

    public function updateKitchenStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'details' => 'required|array|min:1',
            'details.*.id' => 'required|integer|exists:res_sale_details,id',
            'details.*.preparation_status' => 'required|in:pendiente,listo',
        ]);

        DB::beginTransaction();
        try {
            $sale = ResSale::with('details')->findOrFail($id);

            if (!in_array($sale->queue_status, ['01', '02', '03'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La venta no puede actualizarse en cocina en su estado actual',
                ], 422);
            }

            foreach ($validated['details'] as $detailData) {
                $detail = $sale->details->firstWhere('id', $detailData['id']);
                if ($detail) {
                    $detail->update(['preparation_status' => $detailData['preparation_status']]);
                }
            }

            $sale->refresh()->load('details');

            $allReady = $sale->details->every(fn ($d) => $d->preparation_status === 'listo');
            $anyInProgress = $sale->details->contains(fn ($d) => $d->preparation_status === 'listo');

            if ($allReady) {
                $sale->update(['queue_status' => '03']);
            } elseif ($anyInProgress || $sale->queue_status === '01') {
                $sale->update(['queue_status' => '02']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el estado de cocina: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado de cocina guardado correctamente',
            'queue_status' => $sale->fresh()->queue_status,
        ]);
    }
}
