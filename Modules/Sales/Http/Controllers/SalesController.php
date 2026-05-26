<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\SaleProduct;
use App\Models\PaymentMethod;
use App\Models\PettyCash;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Sales\Entities\SalePhysicalDocument;

class SalesController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $paymentMethodNames = PaymentMethod::query()
            ->pluck('description', 'id')
            ->mapWithKeys(fn ($label, $id) => [(string) $id => $label])
            ->all();

        return Inertia::render('Sales::Dashboard', [
            'metrics' => $this->buildDashboardMetrics($today, $monthStart, $monthEnd),
            'charts' => [
                'salesTrend' => $this->buildSalesTrendChart($today->copy()->subDays(6), $today),
                'paymentMethods' => $this->buildPaymentMethodChart($monthStart, $monthEnd, $paymentMethodNames),
            ],
            'tables' => [
                'recentSales' => $this->buildRecentSalesTable($paymentMethodNames),
                'topProducts' => $this->buildTopProductsTable($monthStart, $monthEnd),
                'alerts' => $this->buildAlertsTable(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function parameters()
    {
        return view('sales::create');
    }

    public function getSummaryTotals(Request $request)
    {
        // Validar el parámetro de búsqueda
        $request->validate([
            'period' => 'required|in:day,week,month',
        ]);

        $period = $request->input('period');
        $now = Carbon::now();

        // Inicializar la consulta
        $query = SaleDocument::select('invoice_type_doc', DB::raw('SUM(overall_total) as total'))
            ->where(function ($query) {
                $query->whereIn('invoice_type_doc', ['03', '01'])
                    ->orWhereNull('invoice_type_doc');
            })
            ->where('status', 1)
            ->groupBy('invoice_type_doc');

        $queryNote = SaleDocument::select('invoice_type_doc', DB::raw('SUM(overall_total) as total'))
            ->where(function ($query) {
                $query->where('invoice_type_doc', '80')
                    ->orWhereNull('invoice_type_doc');
            })
            ->where('status', 1)
            ->groupBy('invoice_type_doc');

        $queryPhysical = SalePhysicalDocument::select('document_type', DB::raw('SUM(total) as total'))
            ->where('status', '<>', 'A')
            ->groupBy('document_type');


        // Filtrar según el período
        switch ($period) {
            case 'day':
                $query->whereDate('invoice_broadcast_date', $now->format('Y-m-d'));
                $queryNote->whereDate('invoice_broadcast_date', $now->format('Y-m-d'));
                $queryPhysical->whereDate('document_date', $now->format('Y-m-d'));
                break;

            case 'week':
                $startOfWeek = $now->startOfWeek()->format('Y-m-d');
                $endOfWeek = $now->endOfWeek()->format('Y-m-d');
                $query->whereBetween('invoice_broadcast_date', [$startOfWeek, $endOfWeek]);
                $queryNote->whereBetween('invoice_broadcast_date', [$startOfWeek, $endOfWeek]);
                $queryPhysical->whereBetween('document_date', [$startOfWeek, $endOfWeek]);
                break;

            case 'month':
                $startOfMonth = $now->startOfMonth()->format('Y-m-d');
                $endOfMonth = $now->endOfMonth()->format('Y-m-d');
                $query->whereBetween('invoice_broadcast_date', [$startOfMonth, $endOfMonth]);
                $queryNote->whereBetween('invoice_broadcast_date', [$startOfMonth, $endOfMonth]);
                $queryPhysical->whereBetween('document_date', [$startOfMonth, $endOfMonth]);
                break;
        }

        // Obtener los totales
        $documents = $query->get();
        $salesNote = $queryNote->get();
        $physicals = $queryPhysical->get();

        $total = 0;

        foreach ($documents as $doc) {
            $total = $total + $doc->total;
        }
        foreach ($salesNote as $not) {
            $total = $total + $not->total;
        }

        $newPhysicals = 0;
        foreach ($physicals as $physical) {
            $total = $total + $physical->total;
            $newPhysicals = $newPhysicals + $physical->total;
        }


        // Devolver la respuesta en formato JSON
        return response()->json([
            'documents' => $documents,
            'notes_sale' => $salesNote,
            'physical' => $newPhysicals,
            'total' => $total
        ]);
    }

    public function totalBalanceTables(Request $request)
    {
        // Validar el parámetro de búsqueda
        $request->validate([
            'period' => 'required|in:day,week,month',
        ]);

        $period = $request->input('period');
        $now = Carbon::now();

        // Inicializar la consulta base
        $query = SaleDocument::selectRaw('SUM(overall_total) as total_sales')->where('status', 1);

        $queryPhysical = SalePhysicalDocument::selectRaw('SUM(total) as total_sales')
            ->where('status', '<>', 'A')
            ->groupBy('document_type');

        // Determinar el agrupamiento según el período
        switch ($period) {
            case 'day':
                $query->whereDate('invoice_broadcast_date', $now->format('Y-m-d'));
                $queryPhysical->whereDate('created_at', $now->format('Y-m-d'));

                $previousDay = $now->subDay()->format('Y-m-d');
                $previousTotal = SaleDocument::whereDate('invoice_broadcast_date', $previousDay)
                    ->where('status', 1)
                    ->sum('overall_total');
                $previousTotalPhysical = SalePhysicalDocument::whereDate('document_date', $previousDay)
                    ->where('status', '<>', 'A')
                    ->sum('total');
                break;

            case 'week':
                $startOfWeek = $now->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                $endOfWeek = $now->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
                $query->whereBetween('invoice_broadcast_date', [$startOfWeek, $endOfWeek]);
                $queryPhysical->whereBetween('document_date', [$startOfWeek, $endOfWeek]);

                $previousWeekStart = $now->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                $previousWeekEnd = $previousWeekStart . '+6 days';

                $previousTotal = SaleDocument::whereBetween('invoice_broadcast_date', [$previousWeekStart, $previousWeekEnd])
                    ->where('status', 1)
                    ->sum('overall_total');

                $previousTotalPhysical = SalePhysicalDocument::whereBetween('document_date', [$previousWeekStart, $previousWeekEnd])
                    ->where('status', '<>', 'A')
                    ->sum('total');

                break;

            case 'month':
                $startOfMonth = $now->startOfMonth()->format('Y-m-d');
                $endOfMonth = $now->endOfMonth()->format('Y-m-d');
                //dd($endOfMonth);
                $query->whereBetween('invoice_broadcast_date', [$startOfMonth, $endOfMonth]);
                $queryPhysical->whereBetween('document_date', [$startOfMonth, $endOfMonth]);

                $previousMonthStart = $now->subMonth()->startOfMonth()->format('Y-m-d');
                $previousMonthEnd = $now->subMonth()->endOfMonth()->format('Y-m-d');
                $previousTotal = SaleDocument::whereBetween('invoice_broadcast_date', [$previousMonthStart, $previousMonthEnd])
                    ->where('status', 1)
                    ->sum('overall_total');

                $previousTotalPhysical = SalePhysicalDocument::whereBetween('document_date', [$previousMonthStart, $previousMonthEnd])
                    ->where('status', '<>', 'A')
                    ->sum('total');
                break;
        }

        // Obtener el total de ventas
        $totalSales = $query->first()->total_sales ?? 0;
        $totalPhysical = $queryPhysical->first()->total_sales ?? 0;
        //dd($previousTotal);
        // Calcular la diferencia con el período anterior
        $difference = ($totalSales + $totalPhysical) - ($previousTotal + ($previousTotalPhysical ?? 0));
        //dd($totalSales);
        // Devolver la respuesta en formato JSON
        return response()->json([
            'total_sales' => ($totalSales + $totalPhysical),
            'difference' => $difference,
            'period' => $period,
        ]);
    }

    public function minimumStock()
    {
        $products = Product::where('stock', '<=', DB::raw('stock_min'))
            ->where('is_product', true)
            ->limit(100)
            ->get();

        return response()->json([
            'products' => $products
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function clientSearchDocument(Request $request)
    {
        $document = SaleDocument::select(
            'id',
            'client_number',
            'client_rzn_social',
            'invoice_serie',
            'invoice_correlative',
            'number',
            'invoice_mto_imp_sale',
            'invoice_type_doc'
        )
            ->where('invoice_type_doc', $request->get('type'))
            ->where('invoice_serie', $request->get('serie'))
            ->where('invoice_correlative', $request->get('number'))
            ->where('client_number', $request->get('client'))
            ->where('invoice_mto_imp_sale', $request->get('amount'))
            ->where('invoice_broadcast_date', $request->get('date'))
            ->get();

        return response()->json([
            'document' => $document
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function findInvoice()
    {
        $saleDocumentTypes = DB::table('sale_document_types')->whereIn('sunat_id', ['01', '03'])->get();
        return Inertia::render('Sales::Finder/Invoices', [
            'saleDocumentTypes' => $saleDocumentTypes
        ]);
    }

    private function salesDashboardBaseQuery()
    {
        $query = Sale::query()
            ->where('physical', 1)
            ->where('status', 1);

        if (!Auth::user()->hasRole('admin')) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    private function buildDashboardMetrics(Carbon $today, Carbon $monthStart, Carbon $monthEnd): array
    {
        $todayStats = (clone $this->salesDashboardBaseQuery())
            ->whereDate('sale_date', $today->toDateString())
            ->selectRaw('COUNT(*) as sale_count, COALESCE(SUM(total), 0) as total_amount')
            ->first();

        $monthStats = (clone $this->salesDashboardBaseQuery())
            ->whereBetween('sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('COUNT(*) as sale_count, COALESCE(SUM(total), 0) as total_amount')
            ->first();

        $monthCount = (int) ($monthStats->sale_count ?? 0);
        $monthTotal = (float) ($monthStats->total_amount ?? 0);

        return [
            'todayTotal' => round((float) ($todayStats->total_amount ?? 0), 2),
            'todayCount' => (int) ($todayStats->sale_count ?? 0),
            'monthTotal' => round($monthTotal, 2),
            'monthCount' => $monthCount,
            'averageTicket' => $monthCount > 0 ? round($monthTotal / $monthCount, 2) : 0,
        ];
    }

    private function buildSalesTrendChart(Carbon $startDate, Carbon $endDate): array
    {
        $salesByDay = (clone $this->salesDashboardBaseQuery())
            ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('DATE(sale_date) as sale_day, COUNT(*) as sale_count, COALESCE(SUM(total), 0) as total_amount')
            ->groupBy('sale_day')
            ->orderBy('sale_day')
            ->get()
            ->keyBy('sale_day');

        $labels = [];
        $totals = [];
        $counts = [];
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $key = $cursor->toDateString();
            $dayData = $salesByDay->get($key);

            $labels[] = $cursor->format('d/m');
            $totals[] = round((float) ($dayData->total_amount ?? 0), 2);
            $counts[] = (int) ($dayData->sale_count ?? 0);

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'totals' => $totals,
            'counts' => $counts,
        ];
    }

    private function buildPaymentMethodChart(Carbon $monthStart, Carbon $monthEnd, array $paymentMethodNames): array
    {
        $sales = (clone $this->salesDashboardBaseQuery())
            ->whereBetween('sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['total', 'payments']);

        $breakdown = [];

        foreach ($sales as $sale) {
            $payments = collect($this->normalizePayments($sale->payments ?? null))
                ->filter(fn ($payment) => is_array($payment) && isset($payment['amount']));

            if ($payments->isEmpty()) {
                $breakdown['Sin detalle'] = ($breakdown['Sin detalle'] ?? 0) + (float) $sale->total;
                continue;
            }

            foreach ($payments as $payment) {
                $type = (string) ($payment['type'] ?? $payment['payment_method_id'] ?? '');
                $label = $paymentMethodNames[$type] ?? ($type !== '' ? "Metodo {$type}" : 'Sin detalle');
                $breakdown[$label] = ($breakdown[$label] ?? 0) + (float) ($payment['amount'] ?? 0);
            }
        }

        arsort($breakdown);

        return [
            'items' => collect($breakdown)
                ->map(fn ($value, $label) => [
                    'label' => $label,
                    'value' => round((float) $value, 2),
                ])
                ->values()
                ->take(6)
                ->all(),
            'monthTotal' => round((float) $sales->sum('total'), 2),
        ];
    }

    private function buildRecentSalesTable(array $paymentMethodNames): array
    {
        return (clone $this->salesDashboardBaseQuery())
            ->with([
                'client:id,full_name',
                'document:id,sale_id,invoice_serie,invoice_correlative,number',
                'establishment:id,description',
            ])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'client_id', 'local_id', 'sale_date', 'total', 'payments'])
            ->map(function ($sale) use ($paymentMethodNames) {
                return [
                    'id' => $sale->id,
                    'document' => $this->formatSaleDocumentLabel($sale->document, $sale->id),
                    'client' => $sale->client->full_name ?? 'Cliente no disponible',
                    'local' => $sale->establishment->description ?? 'Sin local',
                    'date' => $sale->sale_date,
                    'total' => round((float) $sale->total, 2),
                    'paymentSummary' => $this->formatPaymentSummary($sale->payments, $paymentMethodNames),
                ];
            })
            ->values()
            ->all();
    }

    private function buildTopProductsTable(Carbon $monthStart, Carbon $monthEnd): array
    {
        $query = SaleProduct::query()
            ->join('sales', 'sale_products.sale_id', '=', 'sales.id')
            ->where('sales.physical', 1)
            ->where('sales.status', 1)
            ->whereBetween('sales.sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        if (!Auth::user()->hasRole('admin')) {
            $query->where('sales.user_id', Auth::id());
        }

        return $query
            ->selectRaw('sale_products.product_id, MIN(sale_products.product) as product_snapshot, SUM(sale_products.quantity) as units, SUM(sale_products.total) as revenue')
            ->groupBy('sale_products.product_id')
            ->orderByDesc('units')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $snapshot = json_decode($row->product_snapshot ?? '[]', true) ?: [];

                return [
                    'id' => (int) $row->product_id,
                    'name' => $snapshot['description'] ?? $snapshot['name'] ?? "Producto #{$row->product_id}",
                    'sku' => $snapshot['interne'] ?? null,
                    'units' => round((float) $row->units, 2),
                    'revenue' => round((float) $row->revenue, 2),
                ];
            })
            ->values()
            ->all();
    }

    private function buildAlertsTable(): array
    {
        $lowStockQuery = Product::query()
            ->where('is_product', true)
            ->whereColumn('stock', '<=', 'stock_min');

        $pettyCashQuery = PettyCash::query()->where('state', 1);

        if (!Auth::user()->hasRole('admin')) {
            $pettyCashQuery->where('user_id', Auth::id());
        }

        return [
            'lowStockCount' => (clone $lowStockQuery)->count(),
            'openPettyCashCount' => $pettyCashQuery->count(),
            'lowStockItems' => $lowStockQuery
                ->orderBy('stock')
                ->limit(4)
                ->get(['id', 'description', 'stock', 'stock_min'])
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'description' => $product->description,
                    'stock' => round((float) $product->stock, 2),
                    'stockMin' => round((float) $product->stock_min, 2),
                ])
                ->values()
                ->all(),
        ];
    }

    private function formatSaleDocumentLabel(?SaleDocument $document, int $saleId): string
    {
        if (!$document) {
            return 'NV-' . $saleId;
        }

        $serie = $document->invoice_serie ?: 'NV';
        $correlative = $document->invoice_correlative ?: $document->number ?: $saleId;

        return $serie . '-' . str_pad((string) $correlative, 8, '0', STR_PAD_LEFT);
    }

    private function normalizePayments($payments): array
    {
        if (is_array($payments)) {
            return $payments;
        }

        if (is_string($payments)) {
            $decoded = json_decode($payments, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function formatPaymentSummary($payments, array $paymentMethodNames): string
    {
        $labels = collect($this->normalizePayments($payments))
            ->map(function ($payment) use ($paymentMethodNames) {
                if (!is_array($payment)) {
                    return null;
                }

                $type = (string) ($payment['type'] ?? $payment['payment_method_id'] ?? '');

                return $paymentMethodNames[$type] ?? ($type !== '' ? "Metodo {$type}" : null);
            })
            ->filter()
            ->unique()
            ->values();

        if ($labels->isEmpty()) {
            return 'Sin detalle';
        }

        $visibleLabels = $labels->take(2)->implode(' · ');

        if ($labels->count() <= 2) {
            return $visibleLabels;
        }

        return $visibleLabels . ' +' . ($labels->count() - 2);
    }
}
