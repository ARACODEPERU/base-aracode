<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Parameter;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\SaleDocument;
use App\Models\SaleDocumentType;
use App\Models\Serie;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Commercial\Entities\CommercialContract;
use Modules\Commercial\Entities\CommercialContractPayment;
use Modules\Sales\Http\Controllers\SaleDocumentController;
use Modules\Treasury\Services\PaymentAccountResolver;

/**
 * Emision de facturas y boletas desde Comercial agrupando las cuotas pendientes
 * de uno o varios contratos del cliente.
 *
 * El comprobante se registra con el mismo proceso de facturacion electronica
 * (SaleDocumentController::store) y luego cada cuota queda vinculada al
 * documento, con su saldo y estado actualizados.
 */
class CommercialSaleDocumentController extends Controller
{
    public function create()
    {
        $company = Company::with('district.province.department')->first();

        if ($company?->district?->province?->department) {
            $company->city = $company->district->province->department->name
                .'-'.$company->district->province->name
                .'-'.$company->district->name;
        }

        $resolver = app(PaymentAccountResolver::class);

        return Inertia::render('Commercial::Documents/Create', [
            'payments' => PaymentMethod::all()->map(fn (PaymentMethod $method) => [
                'id' => $method->id,
                'description' => $method->description,
                'has_treasury_account' => (bool) $resolver->resolveForMethodModel($method),
            ]),
            'saleDocumentTypes' => DB::table('sale_document_types')->whereIn('sunat_id', ['01', '03'])->get(),
            'company' => $company,
            'type_operation' => Parameter::where('parameter_code', 'P000002')->value('value_default'),
            'taxes' => [
                'igv' => Parameter::where('parameter_code', 'P000001')->value('value_default'),
                'icbper' => Parameter::where('parameter_code', 'P000004')->value('value_default'),
            ],
        ]);
    }

    /**
     * Busca clientes del modulo comercial por nombre o numero de documento.
     * Solo se consulta con un criterio para no traer toda la cartera.
     */
    public function searchClients(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));

        if ($search === '') {
            return response()->json(['clients' => []]);
        }

        $clients = Person::query()
            ->where('is_client', true)
            ->where(function ($query) use ($search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%");
            })
            ->orderBy('full_name')
            ->limit(10)
            ->get()
            ->map(fn (Person $person) => [
                'id' => $person->id,
                'number' => $person->number,
                'full_name' => $person->full_name,
                'document_type_id' => $person->document_type_id,
                'email' => $person->email,
                'telephone' => $person->telephone,
                'address' => $person->address,
                'ubigeo' => $person->ubigeo,
                'ubigeo_description' => $person->ubigeo_description,
                'contracts_count' => CommercialContract::query()
                    ->where('client_id', $person->id)
                    ->where('status', 1)
                    ->count(),
            ]);

        return response()->json(['clients' => $clients]);
    }

    /**
     * Contratos vigentes del cliente con sus cuotas por cobrar (pendientes o
     * amortizadas). Una cuota se considera cobrable mientras tenga saldo.
     */
    public function clientContracts($clientId): JsonResponse
    {
        $client = Person::findOrFail($clientId);

        $contracts = CommercialContract::with('service')
            ->where('client_id', $client->id)
            ->where('status', 1)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->orderBy('start_date')
            ->get()
            ->map(function (CommercialContract $contract) {
                $payments = $contract->payments()
                    ->where('status', '!=', 'cancelled')
                    ->where('balance_amount', '>', 0)
                    ->orderBy('due_date')
                    ->get()
                    ->map(fn (CommercialContractPayment $payment) => [
                        'id' => $payment->id,
                        'payment_number' => $payment->payment_number,
                        'description' => $payment->description,
                        'due_date' => $payment->due_date,
                        'total_amount' => (float) $payment->total_amount,
                        'balance_amount' => (float) ($payment->balance_amount ?: $payment->total_amount),
                        'status' => $payment->status,
                        'currency' => $payment->currency ?: $contract->currency,
                    ]);

                return [
                    'id' => $contract->id,
                    'title' => $contract->title,
                    'currency' => $contract->currency,
                    'service' => [
                        'description' => $contract->service?->description,
                        'has_service' => (bool) $contract->service,
                    ],
                    'payments' => $payments->values(),
                ];
            })
            ->values();

        return response()->json([
            'client' => [
                'id' => $client->id,
                'number' => $client->number,
                'full_name' => $client->full_name,
                'document_type_id' => $client->document_type_id,
                'email' => $client->email,
                'telephone' => $client->telephone,
                'address' => $client->address,
                'ubigeo' => $client->ubigeo,
                'ubigeo_description' => $client->ubigeo_description,
            ],
            'contracts' => $contracts,
        ]);
    }

    /**
     * Registra el comprobante con el proceso de facturacion electronica y
     * vincula cada cuota cobrada al documento emitido.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'integer', 'exists:people,id'],
            'serie' => ['required', 'integer', 'exists:series,id'],
            'sale_documenttype_id' => ['required', 'integer', 'exists:sale_document_types,id'],
            'date_issue' => ['required', 'date'],
            'date_end' => ['required', 'date'],
            'additional_description' => ['nullable', 'string', 'max:255'],
            'forma_pago' => ['required', Rule::in(['Contado', 'Credito'])],
            'payments' => ['array'],
            'payments.*.type' => ['required_if:forma_pago,Contado', 'integer', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required_if:forma_pago,Contado', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'quotas' => ['array'],
            'quotas.amounts' => ['required_if:forma_pago,Credito', 'array', 'min:1'],
            'quotas.amounts.*.amount' => ['required', 'numeric', 'min:0.01'],
            'quotas.amounts.*.dueDate' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.quota_id' => ['required', 'integer', 'exists:commercial_contract_payments,id'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Agrega al menos una cuota a la bandeja.',
            'items.min' => 'Agrega al menos una cuota a la bandeja.',
            'serie.required' => 'Selecciona la serie del comprobante.',
            'payments.*.type.required_if' => 'Selecciona el medio de pago del cobro.',
            'quotas.amounts.required_if' => 'Programa al menos una cuota para la venta al credito.',
            'quotas.amounts.min' => 'Programa al menos una cuota para la venta al credito.',
        ]);

        $quotaIds = collect($data['items'])->pluck('quota_id');

        if ($quotaIds->duplicates()->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Una cuota no puede cobrarse dos veces en el mismo comprobante.',
            ], 422);
        }

        $client = Person::findOrFail($data['client_id']);

        // La serie debe existir y, cuando el usuario tiene local asignado,
        // pertenecer a ese local. Un usuario sin local (por ejemplo un
        // administrador) puede emitir con cualquier serie del tipo elegido.
        $userLocalId = Auth::user()?->local_id;

        $serie = Serie::query()
            ->where('id', $data['serie'])
            ->when($userLocalId, fn ($query) => $query->where('local_id', $userLocalId))
            ->first();

        if (! $serie) {
            return response()->json([
                'success' => false,
                'message' => $userLocalId
                    ? 'La serie seleccionada no pertenece al local del usuario.'
                    : 'La serie seleccionada no existe.',
            ], 422);
        }

        // Local de la venta: el del usuario o, si no tiene, el de la serie.
        $localId = $userLocalId ?: $serie->local_id;

        $documentType = SaleDocumentType::find($data['sale_documenttype_id']);

        if (! $documentType || (int) $serie->document_type_id !== (int) $documentType->id) {
            return response()->json([
                'success' => false,
                'message' => 'La serie no corresponde al tipo de comprobante elegido.',
            ], 422);
        }

        if ($documentType->sunat_id === '01' && (string) $client->document_type_id !== '6') {
            return response()->json([
                'success' => false,
                'message' => 'Para emitir una factura el cliente debe tener RUC.',
            ], 422);
        }

        $totalComprobante = round(collect($data['items'])->sum(fn ($row) => round((float) $row['amount'], 2)), 2);

        if ($data['forma_pago'] === 'Contado') {
            $cobrado = round(collect($data['payments'] ?? [])->sum(fn ($payment) => round((float) ($payment['amount'] ?? 0), 2)), 2);

            if (abs($cobrado - $totalComprobante) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cobro al contado debe ser igual al total del comprobante.',
                ], 422);
            }
        } else {
            $programado = round(collect($data['quotas']['amounts'] ?? [])->sum(fn ($quota) => round((float) ($quota['amount'] ?? 0), 2)), 2);

            if (abs($programado - $totalComprobante) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las cuotas programadas deben sumar el total del comprobante.',
                ], 422);
            }
        }

        $company = Company::with('district.province.department')->first();
        $companyCity = null;

        if ($company?->district?->province?->department) {
            $companyCity = $company->district->province->department->name
                .'-'.$company->district->province->name
                .'-'.$company->district->name;
        }

        try {
            return DB::transaction(function () use ($data, $client, $company, $companyCity, $localId) {
                $items = [];
                $quotas = [];

                foreach ($data['items'] as $row) {
                    $quota = CommercialContractPayment::with('contract.service')
                        ->lockForUpdate()
                        ->findOrFail($row['quota_id']);

                    if ($quota->contract?->client_id !== $client->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'La cuota no pertenece a un contrato del cliente seleccionado.',
                        ], 422);
                    }

                    if ($quota->status === 'cancelled') {
                        return response()->json([
                            'success' => false,
                            'message' => "La cuota {$quota->payment_number} esta anulada.",
                        ], 422);
                    }

                    $balance = round((float) ($quota->balance_amount ?: $quota->total_amount), 2);
                    $amount = round((float) $row['amount'], 2);

                    if ($balance <= 0) {
                        return response()->json([
                            'success' => false,
                            'message' => "La cuota {$quota->payment_number} ya no tiene saldo pendiente.",
                        ], 422);
                    }

                    if ($amount > $balance + 0.01) {
                        return response()->json([
                            'success' => false,
                            'message' => "El monto de la cuota {$quota->payment_number} no puede ser mayor a su saldo pendiente.",
                        ], 422);
                    }

                    $service = $quota->contract?->service;

                    if (! $service) {
                        return response()->json([
                            'success' => false,
                            'message' => "El contrato de la cuota {$quota->payment_number} no tiene un servicio asignado.",
                        ], 422);
                    }

                    $description = trim((string) ($row['description'] ?? ''));

                    if ($description === '') {
                        $description = trim($service->description.' - Cuota '.$quota->payment_number
                            .($quota->due_date ? ' ('.$quota->due_date.')' : ''));
                    }

                    $items[] = [
                        'id' => $service->id,
                        'interne' => $service->interne,
                        'description' => $description,
                        'is_product' => false,
                        'unit_type' => $service->type_unit_measure_id ?: 'ZZ',
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'discount' => 0,
                        'total' => $amount,
                        'v_sale' => 0,
                        'afe_igv' => $service->type_sale_affectation_id ?: '10',
                        'size' => null,
                        'icbper' => false,
                        'm_igv' => 0,
                    ];

                    $quotas[] = ['quota' => $quota, 'amount' => $amount];
                }

                $total = round(collect($items)->sum('unit_price'), 2);

                if ($total <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El total del comprobante debe ser mayor a cero.',
                    ], 422);
                }

                $payload = [
                    'client_id' => $client->id,
                    'client_name' => $client->number.'-'.$client->full_name,
                    'client_rzn_social' => $client->full_name,
                    'client_dti' => $client->document_type_id,
                    'client_number' => $client->number,
                    'client_ubigeo' => $client->ubigeo,
                    'client_ubigeo_description' => $client->ubigeo_description ?: $companyCity,
                    'client_direction' => $client->address ?: $company?->fiscal_address,
                    'client_phone' => $client->telephone,
                    'client_email' => $client->email,
                    'sale_documenttype_id' => $data['sale_documenttype_id'],
                    'type_operation' => Parameter::where('parameter_code', 'P000002')->value('value_default'),
                    'currency' => 'PEN',
                    'local_id' => $localId,
                    'serie' => $data['serie'],
                    'date_issue' => $data['date_issue'],
                    'date_end' => $data['date_end'],
                    'forma_pago' => $data['forma_pago'],
                    'total' => $total,
                    'total_discount' => 0,
                    'items' => $items,
                    'payments' => $data['forma_pago'] === 'Contado'
                        ? collect($data['payments'])->map(fn ($payment) => [
                            'type' => (int) $payment['type'],
                            'reference' => $payment['reference'] ?? null,
                            'amount' => round((float) $payment['amount'], 2),
                        ])->values()->all()
                        : [],
                    'quotas' => $data['forma_pago'] === 'Credito'
                        ? ['amounts' => collect($data['quotas']['amounts'])->map(fn ($quota) => [
                            'amount' => round((float) $quota['amount'], 2),
                            'dueDate' => $quota['dueDate'],
                        ])->values()->all()]
                        : [],
                    'additional_description' => $data['additional_description'] ?? null,
                ];

                $documentRequest = Request::create('/sales/saledocuments/store', 'POST', $payload);
                $documentRequest->setUserResolver(fn () => Auth::user());

                $response = app(SaleDocumentController::class)->store($documentRequest);
                $documentPayload = $response->getData(true);

                if (($documentPayload['success'] ?? true) === false) {
                    return response()->json([
                        'success' => false,
                        'message' => $documentPayload['message'] ?? 'No se pudo registrar el comprobante.',
                    ], 422);
                }

                // El registro del comprobante ya deja la venta en tesoreria
                // (SaleDocumentController crea el movimiento al registrar la
                // venta), por eso aqui no se vuelve a disparar.
                $document = SaleDocument::findOrFail($documentPayload['id']);
                $paidAt = $data['date_issue'] ?? Carbon::now()->format('Y-m-d');

                foreach ($quotas as $entry) {
                    /** @var CommercialContractPayment $quota */
                    $quota = $entry['quota'];
                    $newBalance = max(0, round((float) ($quota->balance_amount ?: $quota->total_amount) - $entry['amount'], 2));
                    $documents = collect($quota->document_payments ?: []);

                    $documents->push([
                        'document_id' => $document->id,
                        'serie' => $document->invoice_serie,
                        'correlativo' => (string) $document->invoice_correlative,
                        'amount' => $entry['amount'],
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ]);

                    // Al credito la cuota del contrato queda saldada por el
                    // comprobante pero NO pagada: el cobro vive en las cuotas
                    // del comprobante. Solo el contado la marca como pagada.
                    $isCredit = $data['forma_pago'] === 'Credito';

                    $quota->update([
                        'document_payments' => $documents->all(),
                        'balance_amount' => $newBalance,
                        'status' => $newBalance <= 0 && ! $isCredit ? 'paid' : 'amortized',
                        'paid_at' => $newBalance <= 0 && ! $isCredit ? $paidAt : null,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => "Comprobante {$document->invoice_serie}-{$document->invoice_correlative} registrado correctamente.",
                    'document' => [
                        'id' => $document->id,
                        'invoice_serie' => $document->invoice_serie,
                        'invoice_correlative' => (string) $document->invoice_correlative,
                        'number' => $document->number,
                        'invoice_type_doc' => $document->invoice_type_doc,
                        'total' => (float) ($document->invoice_mto_imp_sale ?? $document->overall_total ?? 0),
                        'client_rzn_social' => $document->client_rzn_social,
                    ],
                    'quotas' => collect($quotas)->map(fn ($entry) => [
                        'id' => $entry['quota']->id,
                        'payment_number' => $entry['quota']->payment_number,
                        'balance_amount' => (float) $entry['quota']->fresh()->balance_amount,
                        'status' => $entry['quota']->fresh()->status,
                    ])->values(),
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
