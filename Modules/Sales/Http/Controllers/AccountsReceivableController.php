<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use DataTables;

class AccountsReceivableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = PaymentMethod::all();
        return Inertia::render('Sales::AccountsReceivable/List',[
             'payments' => $payments,
        ]);
    }

    public function tableDocument()
    {
        $sales = (new Sale())->newQuery();

        $sales = $sales->with('client')
            ->where('physical', 2)
            ->whereHas('document', function ($query) {
                $query->whereIn('invoice_type_doc', ['03','01'])
                    ->where('status', 1)
                    ->whereNotIn('invoice_status', ['Rechazada'])
                    ->where('forma_pago','Credito'); // Estado de la factura
            })
            ->with('document.serie.documentType')
            ->with(['document.items','document.note','document.quotas.payments'])
            ->orderBy('sales.id', 'DESC');

        return DataTables::of($sales)
            ->addColumn('payment_status_text', function (Sale $sale) {
                // Accede al documento de venta asociado a la venta
                $document = $sale->document; // Asume que la relación 'document' está cargada

                // Realiza la misma lógica que tenías en el frontend
                if ($document) { // Asegúrate de que el documento exista
                    if ($document->overdue_fee && !$document->status_pay) {
                        return 'Vencido';
                    } elseif (!$document->overdue_fee && $sale->total == $sale->advancement) {
                        return 'Pagado';
                    } else {
                        return 'Atiempo';
                    }
                }
                return 'N/A'; // En caso de que el documento no exista, o necesites un valor por defecto
            })
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('sales::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sales::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
