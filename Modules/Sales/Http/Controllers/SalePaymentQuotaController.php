<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Sales\Http\Requests\StorePaymentRequest;

class SalePaymentQuotaController extends Controller
{
    use ValidatesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('sales::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('sales::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request)
    {

        try {
            DB::beginTransaction();


            $quota = SaleDocumentQuota::findOrFail($request->get('sale_document_quota_id'));
            $paymentData = null;

            $amountToApply = $request->get('amount_applied');

            $newPayment = SalePaymentQuota::create([
                'payment_method_id' => $request->get('payment_method_id'),
                'sale_document_quota_id' => $quota->id,
                'reference' => $request->get('reference'),
                'payment_date' => $request->get('payment_date'),
                'amount_applied' => $amountToApply,
                'description' => $request->get('description'),
                'estado' => true
            ]);

            $quota->balance -= $amountToApply;

            $saleDocument = SaleDocument::findOrFail($quota->sale_document_id);

            if ($quota->balance <= 0) {
                $quota->status = 'Pagado';
                $saleDocument->status_pay = true;
                $saleDocument->save();
                $quota->balance = 0;
                Log::info("Cuota ID {$quota->id} marcada como 'Pagada'.");
            } elseif ($quota->balance < $quota->amount) {
                $quota->status = 'Amortizado';
                Log::info("Cuota ID {$quota->id} marcada como 'Parcialmente Pagada'.");
            }

            $quota->save();

            $sale = Sale::findOrFail($saleDocument->sale_id);
            $sale->advancement += $amountToApply;
            $sale->save();

            $paymentData = $newPayment;



            DB::commit();

            return response()->json([
                'success' => true,
                'payment' => $paymentData,
                'quota'   => $quota,
                'sale'    => $sale
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al registrar pago: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function storePayFull(Request $request)
    {
        $this->validate($request,[
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount_applied' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            DB::beginTransaction();

            $totalPaid = 0;

            $pendingQuotas = SaleDocumentQuota::where('sale_document_id', $request->get('document_id'))
                        ->whereIn('status', ['Pendiente', 'Parcialmente Pagada', 'Amortizado', 'Vencido'])
                        ->where('balance', '>', 0) // Solo cuotas con saldo pendiente
                        ->orderBy('due_date') // O por quota_number
                        ->get();

            $amountToApply = $request->get('amount_applied');

            $payments = [
                array(
                    "type" => $request->get('payment_method_id'),
                    "reference" => $request->get('reference'),
                    "amount" => $amountToApply,
                    'description' => $request->get('description'),
                    'payment_date' => $request->get('payment_date') ?? now()->toDateString()
                )
            ];

            foreach ($pendingQuotas as $quota) {
                // Registrar un pago por el balance restante de cada cuota
                // Esto crea un registro de pago por cada cuota, lo que es detallado para el historial
                SalePaymentQuota::create([
                    'payment_method_id' => $request->get('payment_method_id'),
                    'sale_document_quota_id' => $quota->id,
                    'reference' => $request->get('reference') . ' (Saldado Total)', // Añadir un sufijo para identificar
                    'payment_date' => $request->get('payment_date') ?? now()->toDateString(),
                    'amount_applied' => $quota->balance, // El pago aplica el balance restante de esta cuota
                    'description' => 'Pago total de deuda: ' . $quota->quota_number,
                    'estado' => false
                ]);

                $totalPaid += $quota->balance; // Suma lo que se "pagó" por esta cuota

                // Actualizar la cuota a pagada
                $quota->balance = 0;
                $quota->status = 'Pagada';
                $quota->save();
            }

            $saleDocument = SaleDocument::findOrFail($request->get('document_id'));
            $sale = Sale::findOrFail($saleDocument->sale_id);
            $saleDocument->single_payment = true;
            $saleDocument->status_pay = true;
            $saleDocument->save();
            $sale->advancement += $amountToApply;
            $sale->payments = $payments;
            $sale->save();

            DB::commit();

            return to_route('acco_document_list')->with('success', 'Pago registrado y cuota actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al registrar pago: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return to_route('acco_document_list')->with('success', $e->getMessage());;
        }
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
        try {
            DB::beginTransaction();

            $payment = SalePaymentQuota::findOrFail($id);

            $quota = SaleDocumentQuota::findOrFail($payment['sale_document_quota_id']);

            if($payment){
                    $amountToApply = $payment->amount_applied;
                    $quota->balance += $amountToApply;

                    if ($quota->balance <= 0) {
                        $quota->status = 'Pagado';
                        $quota->balance = 0;
                        Log::info("Cuota ID {$quota->id} marcada como 'Pagada'.");
                    } elseif ($quota->balance < $quota->amount && $quota->balance > 0) {
                        $quota->status = 'Amortizado';
                        Log::info("Cuota ID {$quota->id} marcada como 'Parcialmente Pagada'.");
                    } else {
                        $quota->status = 'Pendiente';
                        Log::info("Cuota ID {$quota->id} marcada como 'Pendiente'.");
                    }
                    $quota->save();

                    $saleDocument = SaleDocument::findOrFail($quota->sale_document_id);
                    $saleDocument->status_pay = false;
                    $saleDocument->save();
                    $sale = Sale::findOrFail($saleDocument->sale_id);
                    $sale->advancement -= $amountToApply;
                    $sale->save();

            }

            $payment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'quota'   => $quota,
                'sale'    => $sale
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al registrar pago: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
