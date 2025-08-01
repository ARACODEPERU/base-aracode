<?php

namespace Modules\Sales\Http\Controllers;

use App\Models\Expense;
use App\Models\LocalSale;
use App\Models\PaymentMethod;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PDF;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Modules\Purchases\Entities\PurcDocument;

class ReportController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Inertia::render('Sales::Reports/List');
    }

    public function sales_report()
    {
        return Inertia::render('Sales::Reports/SaleReport', [
            'filters' => request()->all('search'),
            'locals' => LocalSale::all(),
        ]);
    }

    public function sales_report_export($start, $end, $download)
    {
        $sales = Sale::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)->orderBy('id', 'desc')->orderBy('created_at', 'desc')
            ->get();

        $date = date_time_format();
        $start = Carbon::parse($start)->format('d-m-Y');
        $end = Carbon::parse($end)->format('d-m-Y');
        if ($start == $end) {
            $file = public_path('ticket/') . 'reporteVentas_' . $start . '.pdf';
        } else {
            $file = public_path('ticket/') . 'reporteVentas_' . $start . '_al_' . $end . '.pdf';
        }
        $start = date('d/m/Y', strtotime($start));
        $end = date('d/m/Y', strtotime($end));
        if ($download == "false") {
            return view('sales::reports.sales_report', ['sales' => $sales, 'start' => $start, 'end' => $end, 'date' => $date, 'print' => true]);
        } else {
            $pdf = PDF::loadView('sales::reports.sales_report', ['sales' => $sales, 'start' => $start, 'end' => $end, 'date' => $date, 'print' => false]);
            $pdf->setPaper('A4', 'landscape');
            $pdf->save($file);

            return response()->download($file);
        }
    }

    public function sales_report_dates($start = null, $end = null, $local_id = 0, $consulta = false)
    {

        $date_ = date('Y-m-d');

        $date = date_time_format();
        $start = $start == null ? $date_ : $start;
        $end = $end == null ? $date_ : $end;
        $payments = PaymentMethod::all();

        if ($local_id == 0 || $local_id == null) {
            $sales = $this->getSales($start, $end);

            if ($consulta) {
                return response()->json([
                    'payments' => $payments,
                    'sales' => $sales
                ]);
            } else {
                return Inertia::render('Sales::Reports/SaleReportByDates', [
                    'locals' => LocalSale::all(),
                    'sales' => $sales,
                    'date' => $date,
                    'start' => $start,
                    'end' => $end,
                    'payments' => $payments,
                ]);
            }
        } else {
            $sales = $this->getSales($start, $end, $local_id);


            return response()->json([
                'payments' => $payments,
                'sales' => $sales
            ]);
        }
    }
    public function getSales($start, $end, $local_id = 0)
    {
        $sales = [];
        if ($local_id == 0) {
            $sales = Sale::join('local_sales', 'sales.local_id', 'local_sales.id')
                ->join('sale_products', 'sale_products.sale_id', 'sales.id')
                ->join('products', 'products.id', 'sale_products.product_id')
                ->select(
                    'sales.id',
                    'sales.created_at',
                    'sales.local_id',
                    'sales.payments',
                    'local_sales.description AS local_description',
                    'products.interne',
                    'products.description as product_description',
                    'products.image',
                    'sale_products.product as product',
                    'sale_products.saleProduct as saleProduct',
                    'sale_products.total as product_total'
                )
                ->where(function ($query) use ($start, $end) {
                    $query->whereDate('sales.created_at', '>=', $start)
                        ->whereDate('sales.created_at', '<=', $end);
                })
                ->where('sales.status', '=', 1)
                ->orderBy('sales.id')
                ->get();
        } else {
            $sales = Sale::join('local_sales', 'sales.local_id', 'local_sales.id')
                ->join('sale_products', 'sale_products.sale_id', 'sales.id')
                ->join('products', 'products.id', 'sale_products.product_id')
                ->select(
                    'sales.id',
                    'sales.created_at',
                    'sales.local_id',
                    'sales.payments',
                    'local_sales.description AS local_description',
                    'products.interne',
                    'products.description as product_description',
                    'products.image',
                    'sale_products.product as product',
                    'sale_products.saleProduct as saleProduct',
                    'sale_products.total as product_total'
                )
                ->whereDate('sales.created_at', '>=', $start)
                ->whereDate('sales.created_at', '<=', $end)
                ->where('sales.status', '=', 1)
                ->where('sales.local_id', '=', $local_id)
                ->orderBy('sales.id')
                ->get();
        }
        $arraySale = [];
        foreach ($sales as $k => $sale) {
            $arraySale[$k] = [
                'id'                        => $sale->id,
                'created_at'                => $sale->created_at,
                'local_id'                  => $sale->local_id,
                'payments'                  => $sale->payments,
                'interne'                   => $sale->interne,
                'product_description'       => $sale->product_description,
                'image'                     => $sale->image,
                'product'                   => $sale->product,
                'sale_product'              => $sale->saleProduct,
                'product_total'             => $sale->product_total,
                'local_description'         => $sale->local_description
            ];
        }

        return $arraySale;
    }

    public function PettyCashReport($petty_cash_id)
    {
        $petty_cash = PettyCash::find($petty_cash_id);

        $tickets = Sale::with('establishment')
            ->with('document.serie.documentType')
            ->where('sales.petty_cash_id', '=', $petty_cash_id)
            ->where('sales.status', '=', 1)
            ->where('physical', 1)
            ->whereHas('document', function ($query) { // 'document' es el nombre de tu relación en el modelo Sale
                $query->whereIn('invoice_type_doc', ['80'])
                    ->where('status', 1);
            })
            ->orderBy('id', 'desc')
            ->get();

        $physicals = Sale::with('establishment')
            ->with('physicalDocument.saleDocumentType')
            ->where('sales.petty_cash_id', '=', $petty_cash_id)
            ->where('sales.status', '=', 1)
            ->where('physical', 3)
            ->whereHas('physicalDocument', function ($query) { // 'document' es el nombre de tu relación en el modelo Sale
                $query->whereIn('document_type', ['1','2'])
                    ->where('status', '<>', 'A');
            })
            ->orderBy('id', 'desc')
            ->get();

        $documents = Sale::with('establishment')
            ->with('document.serie.documentType')
            ->where('sales.petty_cash_id', '=', $petty_cash_id)
            ->where('sales.status', '=', 1)
            ->orderBy('id', 'desc')
            ->where('physical', 2)
            ->whereHas('document', function ($query) { // 'document' es el nombre de tu relación en el modelo Sale
                $query->whereIn('invoice_type_doc', ['03','01'])
                    ->where('status', 1)
                        ->whereNotIn('invoice_status', ['Rechazada']); // Estado de la factura
            })
            ->get();



        $total = 0;

        foreach ($tickets as $ticket) {
            $total = $total + $ticket->total;
        }
        foreach ($physicals as $physical) {
            $total = $total + $physical->total;
        }

        foreach ($documents as $document) {
            $total = $total + $document->total;
        }


        $expenses = Expense::where('petty_cash_id', $petty_cash_id)->get();

        return Inertia::render('Sales::Reports/PettyCashReport', [
            'locals' => LocalSale::all(),
            'tickets' => $tickets,
            'physicals' => $physicals,
            'documents' => $documents,
            'petty_cash' => $petty_cash,
            'date' => $petty_cash->date_opening . $petty_cash->time_opening,
            'start' => $petty_cash->date_closed,
            'end' => $petty_cash->date_opening,
            'expenses' => $expenses,
            'total' => number_format($total, 2, '.', '')
        ]);
    }

    public function inventoryReportProducts()
    {
        return Inertia::render('Sales::Reports/InventoryReportProducts', [
            'locals' => LocalSale::all()
        ]);
    }




    public function reportPaymentMethodTotals()
    {
        return Inertia::render('Sales::Reports/PaymentMethodTotals', [
            'locals' => LocalSale::all(),
        ]);
    }

    public function inventoryReportProductsData(Request $request)
    {
        $products = Product::leftJoin('sale_products', 'product_id', 'products.id')
            ->select('products.*', DB::raw('SUM(sale_products.quantity) as total_sold'))
            ->where('is_product', true)
            ->groupBy('products.id')
            ->get();
        return response()->json([
            'products' => $products
        ]);
    }

    public function dataPaymentMethodTotals(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $payments = Sale::select('payments')->where('local_id', $request->input('local_id'))
            ->where(function ($query) use ($start, $end) {
                $query->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end);
            })
            ->where('status', 1)
            ->get();

        //$array = json_decode($payments, true);
        $sumas = array();

        // Recorrer los pagos
        foreach ($payments as $pago) {
            // Decodificar el pago como un array asociativo
            $datos_pago = json_decode($pago['payments'], true);

            // Obtener el tipo y el monto del pago
            $tipo_pago = $datos_pago[0]['type'];
            $monto_pago = $datos_pago[0]['amount'];

            // Si el tipo de pago no está en el array de sumas, inicializarlo en 0
            if (!isset($sumas[$tipo_pago])) {
                $sumas[$tipo_pago] = 0;
            }

            // Sumar el monto del pago al tipo de pago correspondiente
            $sumas[$tipo_pago] += $monto_pago;
        }

        // Imprimir las sumas de cada tipo de pago
        $c = 0;

        $payments_sum = [];
        foreach ($sumas as $tipo_pago => $suma) {
            $payments_sum[$c] = [
                'type' => $tipo_pago,
                'amount' => $suma
            ];
            $c++;
        }

        $method_payments = PaymentMethod::all();

        $array_payments = [];

        $total = 0;

        foreach ($method_payments as $key => $method) {
            $encontrado = false;
            foreach ($payments_sum as $payment) {
                if ($method->id == $payment['type']) {
                    $array_payments[$key] = [
                        'id'            => $method->id,
                        'description'   => $method->description,
                        'amount'        => $payment['amount']
                    ];
                    $total = $total + $payment['amount'];
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                $array_payments[$key] = [
                    'id'            => $method->id,
                    'description'   => $method->description,
                    'amount'        => 0
                ];
            }
        }

        return response()->json([
            'payments'  => $array_payments,
            'total'     => $total
        ]);
    }

    public function reportProductSellersDates()
    {
        //$users = User::where('id', '<>', 1)->get();
        $users = User::get();

        return Inertia::render('Sales::Reports/ProductSellersDates', [
            'sellers' => $users
        ]);
    }

    public function reportProductSellersTable(Request $request)
    {

        $date_range = $request->get('date');

        // Encontrar la posición de la palabra "a"
        $pos = strpos($date_range, 'a');

        // Extraer las fechas de inicio y fin
        $startDate = trim(substr($date_range, 0, $pos));
        $endDate = trim(substr($date_range, $pos + 2));

        $userId = $request->input('user_id');

        $products = SaleDocument::with('items.product')->where('status', 1)
            ->where('user_id', $userId);

        if ($startDate) {
            $products->whereBetween('invoice_broadcast_date', [$startDate, $endDate]);
        } else {
            $products->whereDate('invoice_broadcast_date', $endDate);
        }

        $products = $products->get()
            ->flatMap(function ($saleDocument) {
                return $saleDocument->items->map(function ($item) use ($saleDocument) {
                    return [
                        'product_id' => $item->product_id,
                        'image' => $item->product->image,
                        'product_name' => $item->product->description,
                        'product_code' => $item->product->interne,
                        'product_price' => $item->price_sale,
                        'quantity' => $item->quantity,
                        'total_amount' => $item->mto_total,
                        'sale_date' => $saleDocument->invoice_broadcast_date
                    ];
                });
            })
            ->groupBy('product_id')
            ->map(function ($items) {
                return [
                    'product_id' => $items->first()['product_id'],
                    'image' => $items->first()['image'],
                    'product_name' => $items->first()['product_name'],
                    'product_code' => $items->first()['product_code'],
                    'product_price' => $items->first()['product_price'],
                    'total_quantity' => $items->sum('quantity'),
                    'total_amount' => number_format($items->sum('total_amount'), 2, '.', ' '),
                    'sale_date' => $items->first()['sale_date']
                ];
            })
            ->values();

        return response()->json([
            'products' => $products
        ]);
    }
    public function reportSalesExpenses()
    {
        return Inertia::render('Sales::Reports/SalesExpenses');
    }

    public function reportSalesExpensesData(Request $request)
    {
        $date_range = $request->get('date');

        // Encontrar la posición de la palabra "a"
        $pos = strpos($date_range, 'a');

        // Extraer las fechas de inicio y fin
        $startDate = trim(substr($date_range, 0, $pos));
        $endDate = trim(substr($date_range, $pos + 2));

        $establishmentId = $request->input('establishment_id');

        $sales = $this->getDataSalesTotal($startDate, $endDate, $establishmentId);
        $purchases = $this->getDataPurchPurchasesTotal($startDate, $endDate, $establishmentId);
        $expenses = $this->getDataExpenses($startDate, $endDate, $establishmentId);

        $ts = (float) str_replace(" ", "", $sales['total']);
        $tp = (float) str_replace(" ", "", $purchases['total']);
        $te = (float) str_replace(" ", "", $expenses['total']);

        $result = $ts - ($tp + $te);

        return response()->json([
            'sales' => $sales,
            'purchases' => $purchases,
            'expenses' => $expenses,
            'result' => number_format($result, 2, '.', ' ')
        ]);
    }

    public function getDataPurchPurchasesTotal($startDate, $endDate, $establishmentId)
    {
        $documentsPurchases = PurcDocument::where('status', '<>', 'A');

        if ($startDate) {
            $documentsPurchases = $documentsPurchases->whereBetween('date_of_issue', [$startDate, $endDate]);
        } else {
            $documentsPurchases = $documentsPurchases->whereDate('date_of_issue', $endDate);
        }

        $documentsPurchases = $documentsPurchases->get();

        $total = 0;
        foreach ($documentsPurchases as $purchase) {
            $total = $purchase->total + $total;
        }

        return [
            'documents' => $documentsPurchases,
            'total' => number_format($total, 2, '.', ' ')
        ];
    }

    public function getDataSalesTotal($startDate, $endDate, $establishmentId)
    {
        $documentsSales = SaleDocument::where('status', 1)->whereIn('invoice_type_doc', ['03', '01']);
        $documentsNotes = SaleDocument::where('status', 1)->where(function ($query) {
            $query->where('invoice_type_doc', '80')
                ->orWhereNull('invoice_type_doc');
        });

        if ($startDate) {
            $documentsSales = $documentsSales->whereBetween('invoice_broadcast_date', [$startDate, $endDate]);
        } else {
            $documentsSales = $documentsSales->whereDate('invoice_broadcast_date', $endDate);
        }

        if ($startDate) {
            $documentsNotes = $documentsNotes->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $documentsNotes = $documentsNotes->whereDate('created_at', $endDate);
        }


        // $documentsSales = $documentsSales->get();
        // $documentsNotes = $documentsNotes->get();

        $documents = $documentsSales->union($documentsNotes)->get();

        $total = 0;
        foreach ($documents as $document) {
            $total = $document->overall_total + $total;
        }



        return [
            'documents' => $documents,
            'total' => number_format($total, 2, '.', ' ')
        ];
    }

    public function getDataExpenses($startDate, $endDate, $establishmentId)
    {
        $expenses = Expense::with('cash');

        if ($startDate) {
            $expenses = $expenses->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $expenses = $expenses->whereDate('created_at', $endDate);
        }

        $expenses = $expenses->get();

        $total = 0;
        foreach ($expenses as $expense) {
            $total = $expense->amount + $total;
        }

        return [
            'details' => $expenses,
            'total' => number_format($total, 2, '.', ' ')
        ];
    }

    public function recordSalesIncomePeriod()
    {
        return Inertia::render('Sales::Reports/RecordSalesIncomePeriod');
    }
}
