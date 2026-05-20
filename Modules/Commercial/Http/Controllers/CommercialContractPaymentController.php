<?php

namespace Modules\Commercial\Http\Controllers;

use App\Models\Company;
use App\Models\District;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Commercial\Entities\CommercialContract;
use Modules\Commercial\Entities\CommercialContractPayment;
use Modules\Sales\Http\Controllers\SaleDocumentController;

class CommercialContractPaymentController extends Controller
{
    public function index($id)
    {
        $contract = CommercialContract::with(['client', 'responsible', 'service', 'payments'])->findOrFail($id);

        return Inertia::render('Commercial::Contracts/Payments', [
            'contract' => $contract,
            'paymentTypes' => $this->paymentTypes(),
            'paymentStatuses' => $this->paymentStatuses(),
        ]);
    }

    public function store(Request $request, $id)
    {
        $contract = CommercialContract::findOrFail($id);

        $data = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.id' => ['nullable', 'integer', 'exists:commercial_contract_payments,id'],
            'payments.*.payment_number' => ['required', 'integer', 'min:1'],
            'payments.*.description' => ['nullable', 'string', 'max:255'],
            'payments.*.due_date' => ['required', 'date'],
            'payments.*.payment_type' => ['required', Rule::in(array_column($this->paymentTypes(), 'value'))],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.interest_amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.total_amount' => ['required', 'numeric', 'min:0'],
            'payments.*.status' => ['required', Rule::in(array_column($this->paymentStatuses(), 'value'))],
            'payments.*.paid_at' => ['nullable', 'date'],
            'payments.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($contract, $data) {
            $receivedIds = collect($data['payments'])->pluck('id')->filter()->all();
            $contract->payments()
                ->when($receivedIds, fn ($query) => $query->whereNotIn('id', $receivedIds))
                ->whereNull('document_payments')
                ->delete();

            foreach ($data['payments'] as $index => $payment) {
                $amount = round((float) $payment['amount'], 2);
                $interest = round((float) ($payment['interest_amount'] ?? 0), 2);
                $total = round($amount + $interest, 2);
                $existingPayment = ! empty($payment['id'])
                    ? $contract->payments()->where('id', $payment['id'])->first()
                    : null;
                $documentPayments = $existingPayment?->document_payments ?: null;
                $currentBalance = $existingPayment
                    ? round((float) ($existingPayment->balance_amount ?: $existingPayment->total_amount), 2)
                    : $total;

                $contract->payments()->updateOrCreate([
                    'id' => $payment['id'] ?? null,
                ], [
                    'payment_number' => $index + 1,
                    'description' => $payment['description'] ?? null,
                    'due_date' => $payment['due_date'],
                    'payment_type' => $payment['payment_type'],
                    'amount' => $amount,
                    'interest_amount' => $interest,
                    'total_amount' => $total,
                    'balance_amount' => $documentPayments ? min($currentBalance, $total) : $total,
                    'currency' => $contract->currency,
                    'status' => $documentPayments ? $existingPayment->status : $payment['status'],
                    'paid_at' => $documentPayments ? $existingPayment->paid_at : ($payment['paid_at'] ?? null),
                    'document_payments' => $documentPayments,
                    'notes' => $payment['notes'] ?? null,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Cronograma guardado correctamente');
    }

    public function createDocument($paymentId)
    {
        $payment = CommercialContractPayment::with(['contract.client', 'contract.service'])->findOrFail($paymentId);
        $contract = $payment->contract;

        if (in_array($payment->status, ['paid', 'cancelled'], true) || (float) ($payment->balance_amount ?: $payment->total_amount) <= 0) {
            return redirect()
                ->route('comm_contracts_payments', $contract->id)
                ->with('error', 'Esta cuota ya esta pagada o anulada. No se puede generar otro documento.');
        }

        $company = Company::with('district.province.department')->first();
        $client = $contract->client;

        $city = null;
        if ($company?->district?->province?->department) {
            $city = $company->district->province->department->name.'-'.$company->district->province->name.'-'.$company->district->name;
            $company->city = $city;
        }

        if ($client?->ubigeo && $client?->district?->province?->department) {
            $client->city = $client->district->province->department->name.'-'.$client->district->province->name.'-'.$client->district->name;
        } else {
            $client->city = $client->ubigeo_description ?: $city;
        }

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                'districts.name AS district_name',
                'provinces.name AS province_name',
                'departments.name AS department_name',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS city_name")
            )
            ->get();

        return Inertia::render('Commercial::Contracts/PaymentDocumentCreate', [
            'payments' => PaymentMethod::all(),
            'client' => $client,
            'documentTypes' => DB::table('identity_document_type')->get(),
            'saleDocumentTypes' => DB::table('sale_document_types')->whereIn('sunat_id', ['01', '03'])->get(),
            'company' => $company,
            'departments' => $ubigeo,
            'unitTypes' => DB::table('sunat_unit_types')->get(),
            'type_operation' => Parameter::where('parameter_code', 'P000002')->value('value_default'),
            'commercialPayment' => [
                'id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'description' => $payment->description,
                'due_date' => $payment->due_date,
                'amount' => $payment->amount,
                'interest_amount' => $payment->interest_amount,
                'total_amount' => $payment->total_amount,
                'balance_amount' => $payment->balance_amount ?: $payment->total_amount,
                'currency' => $payment->currency,
                'contract' => [
                    'id' => $contract->id,
                    'title' => $contract->title,
                    'client' => $client,
                    'service' => $contract->service,
                ],
            ],
            'taxes' => [
                'igv' => Parameter::where('parameter_code', 'P000001')->value('value_default'),
                'icbper' => Parameter::where('parameter_code', 'P000004')->value('value_default'),
            ],
        ]);
    }

    public function storeDocument(Request $request): JsonResponse
    {
        $data = $request->validate([
            'commercial_payment_id' => ['required', 'integer', 'exists:commercial_contract_payments,id'],
            'total' => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($request, $data) {
            $payment = CommercialContractPayment::query()
                ->lockForUpdate()
                ->findOrFail($data['commercial_payment_id']);

            if (in_array($payment->status, ['paid', 'cancelled'], true) || (float) $payment->balance_amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cuota ya esta pagada o anulada. No se puede generar otro documento.',
                ]);
            }

            $balance = round((float) ($payment->balance_amount ?: $payment->total_amount), 2);
            $documentAmount = round((float) $request->get('total'), 2);

            if ($documentAmount > $balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'El monto del documento no puede ser mayor al saldo pendiente de la cuota.',
                ]);
            }

            $response = app(SaleDocumentController::class)->store($request);
            $payload = $response->getData(true);

            if (($payload['success'] ?? true) === false) {
                return response()->json($payload, $response->getStatusCode());
            }

            $newBalance = round($balance - $documentAmount, 2);
            $documents = $payment->document_payments ?: [];
            $documents[] = [
                'document_id' => $payload['id'],
                'serie' => $payload['invoice_serie'],
                'correlativo' => (string) $payload['invoice_correlative'],
                'amount' => $documentAmount,
                'created_at' => Carbon::now()->toDateTimeString(),
            ];

            $payment->update([
                'balance_amount' => $newBalance,
                'document_payments' => $documents,
                'status' => $newBalance <= 0 ? 'paid' : 'amortized',
                'paid_at' => $newBalance <= 0 ? ($request->get('date_issue') ?: Carbon::now()->format('Y-m-d')) : null,
            ]);

            $payload['commercial_payment'] = [
                'id' => $payment->id,
                'status' => $payment->fresh()->status,
                'balance_amount' => $newBalance,
                'document_payments' => $documents,
            ];

            return response()->json($payload);
        });
    }

    private function paymentTypes(): array
    {
        return [
            ['value' => 'initial', 'label' => 'Adelanto'],
            ['value' => 'installment', 'label' => 'Cuota'],
            ['value' => 'delivery', 'label' => 'Contra entrega'],
            ['value' => 'monthly', 'label' => 'Mensualidad'],
            ['value' => 'annual', 'label' => 'Anualidad'],
            ['value' => 'custom', 'label' => 'Personalizado'],
        ];
    }

    private function paymentStatuses(): array
    {
        return [
            ['value' => 'pending', 'label' => 'Pendiente'],
            ['value' => 'paid', 'label' => 'Pagado'],
            ['value' => 'overdue', 'label' => 'Vencido'],
            ['value' => 'partial', 'label' => 'Parcial'],
            ['value' => 'amortized', 'label' => 'Amortizado'],
            ['value' => 'cancelled', 'label' => 'Anulado'],
        ];
    }
}
