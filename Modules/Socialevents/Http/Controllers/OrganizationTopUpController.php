<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Socialevents\Services\OrganizationTopUpService;
use Modules\Socialevents\Services\OrganizationTeamService;
use Modules\Socialevents\Support\OrganizationCapabilities;
use App\Models\SaleDocumentType;
use Inertia\Inertia;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\PaymentMethod\PaymentMethodClient;
use MercadoPago\MercadoPagoConfig;
use Modules\Socialevents\Services\SportsOrganizationService;

class OrganizationTopUpController extends Controller
{
    public function index(Request $request)
    {
        $sports = app(SportsOrganizationService::class);
        $team = app(OrganizationTeamService::class);
        // Las cuentas suspendidas no pueden entrar al módulo, pero conservan
        // acceso a esta vista para efectuar el pago que permitirá reactivarlas.
        $organizationId = $sports->assertCanAccessPayments($request->user());
        abort_unless($organizationId, 404);
        $team->assertCanManage($request->user());

        $organization = DB::table('socialevent_organizations')->where('id', $organizationId)->first();
        $settings = DB::table('socialevent_organization_settings')->first();
        $seatPrice = (float) ($settings->seat_price ?? 1);
        $claim = DB::table('socialevent_organization_users')
            ->where('organization_id', $organizationId)
            ->where('role', 'owner')
            ->join('users', 'users.id', '=', 'socialevent_organization_users.user_id')
            ->select('users.email', 'users.telefono', 'users.numero_identidad', 'users.tipo_identidad')
            ->first();

        $seatPurchases = DB::table('socialevent_organization_seat_purchases')
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->map(fn ($purchase) => [
                'id' => (int) $purchase->id,
                'quantity' => (int) $purchase->quantity,
                'unit_price' => (float) $purchase->unit_price,
                'total' => (float) $purchase->total,
                'status' => (string) $purchase->status,
                'payment_method' => (string) $purchase->payment_method,
                'reference' => $purchase->provider_payment_id ?: $purchase->external_reference,
                'created_at' => \Illuminate\Support\Carbon::parse($purchase->created_at)->format('d/m/Y H:i'),
                'credited_at' => $purchase->credited_at
                    ? \Illuminate\Support\Carbon::parse($purchase->credited_at)->format('d/m/Y H:i')
                    : null,
            ]);

        $documentRequests = DB::table('socialevent_organization_document_requests as requests')
            ->leftJoin('sale_documents as documents', 'documents.id', '=', 'requests.sale_document_id')
            ->leftJoin('sale_document_types as types', 'types.id', '=', 'requests.document_type_id')
            ->where('requests.organization_id', $organizationId)
            ->orderByDesc('requests.created_at')
            ->orderByDesc('requests.id')
            ->limit(30)
            ->get([
                'requests.id',
                'requests.purchase_type',
                'requests.purchase_id',
                'requests.status',
                'requests.billing_data',
                'requests.delivery_channels',
                'requests.delivery_log',
                'requests.rejection_reason',
                'requests.issued_at',
                'requests.created_at',
                'requests.sale_document_id',
                'types.description as document_type',
                'types.sunat_id',
                'documents.invoice_serie',
                'documents.invoice_correlative',
            ])
            ->map(fn ($document) => [
                'id' => (int) $document->id,
                'purchase_type' => (string) $document->purchase_type,
                'purchase_id' => (int) $document->purchase_id,
                'status' => (string) $document->status,
                'billing_data' => json_decode((string) $document->billing_data, true) ?: [],
                'delivery_channels' => json_decode((string) $document->delivery_channels, true) ?: [],
                'delivery_log' => json_decode((string) $document->delivery_log, true) ?: [],
                'rejection_reason' => $document->rejection_reason,
                'issued_at' => $document->issued_at,
                'created_at' => $document->created_at,
                'document_type' => $document->document_type,
                'sunat_id' => $document->sunat_id,
                'number' => $document->invoice_serie
                    ? $document->invoice_serie . '-' . str_pad((string) $document->invoice_correlative, 9, '0', STR_PAD_LEFT)
                    : null,
                'can_download' => (bool) $document->sale_document_id,
            ]);

        return Inertia::render('Socialevents::Organizations/TopUp', [
            'balance' => (float) $organization->prepaid_balance,
            'publicKey' => config('services.mercadopago.key'),
            'minimumAmount' => 10,
            'organization' => [
                'name' => (string) $organization->name,
                'match_rate' => (float) $organization->match_rate,
                'ruc' => $organization->ruc,
                'address' => $organization->address,
                'ubigeo' => $organization->ubigeo,
            ],
            'access' => $sports->accessSummary($request->user()),
            'payments' => $sports->paymentHistory($request->user()),
            'seats' => $team->seatSummary($organizationId),
            'seatPrice' => $seatPrice,
            'members' => $team->members($organizationId),
            'capabilities' => OrganizationCapabilities::describe(),
            'seatPurchases' => $seatPurchases,
            'documentRequests' => $documentRequests,
            'documentTypes' => SaleDocumentType::query()->whereIn('sunat_id', ['01', '03', '80'])->orderBy('id')->get(['id', 'description', 'sunat_id']),
            'billingDefaults' => [
                'business_name' => (string) $organization->name,
                'ruc' => (string) ($organization->ruc ?? ''),
                'address' => (string) ($organization->address ?? ''),
                'ubigeo' => (string) ($organization->ubigeo ?? ''),
                'document_type' => (string) ($claim?->tipo_identidad ?? '1'),
                'document_number' => (string) ($claim?->numero_identidad ?? ''),
                'email' => (string) ($claim?->email ?? $request->user()->email),
                'phone' => (string) ($claim?->telefono ?? $request->user()->telefono ?? ''),
            ],
        ]);
    }

    public function createCardPayment(Request $request): JsonResponse
    {
        app(SportsOrganizationService::class)->assertCanAccessPayments($request->user());
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:999999.99',
            'token' => 'required|string|max:500',
            'payment_method_id' => 'required|string|max:60',
            'installments' => 'required|integer|min:1|max:12',
            'issuer_id' => 'nullable',
            'payer.email' => 'required|email|max:255',
            'payer.identification.type' => 'nullable|string|max:20',
            'payer.identification.number' => 'nullable|string|max:40',
        ]);

        if (! $this->isAllowedCardMethod($validated['payment_method_id'])) {
            return response()->json(['message' => 'Solo se aceptan pagos con tarjeta y Yape.'], 422);
        }

        return $this->processPayment($request, $validated, 'card');
    }

    public function createYapePayment(Request $request): JsonResponse
    {
        app(SportsOrganizationService::class)->assertCanAccessPayments($request->user());
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:999999.99',
            'token' => 'required|string|max:500',
            'phone_number' => ['required', 'regex:/^9[0-9]{8}$/'],
            'payer.email' => 'required|email|max:255',
            'payer.identification.type' => 'nullable|string|max:20',
            'payer.identification.number' => 'nullable|string|max:40',
        ]);
        $validated['payment_method_id'] = 'yape';
        $validated['installments'] = 1;

        return $this->processPayment($request, $validated, 'yape');
    }

    public function createSeatCardPayment(Request $request): JsonResponse
    {
        app(OrganizationTeamService::class)->assertCanManage($request->user());
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:50',
            'token' => 'required|string|max:500',
            'payment_method_id' => 'required|string|max:60',
            'installments' => 'required|integer|min:1|max:12',
            'issuer_id' => 'nullable',
            'payer.email' => 'required|email|max:255',
            'payer.identification.type' => 'nullable|string|max:20',
            'payer.identification.number' => 'nullable|string|max:40',
        ]);

        if (! $this->isAllowedCardMethod($validated['payment_method_id'])) {
            return response()->json(['message' => 'Solo se aceptan pagos con tarjeta y Yape.'], 422);
        }

        return $this->processSeatPayment($request, $validated, 'card');
    }

    public function createSeatYapePayment(Request $request): JsonResponse
    {
        app(OrganizationTeamService::class)->assertCanManage($request->user());
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:50',
            'token' => 'required|string|max:500',
            'phone_number' => ['required', 'regex:/^9[0-9]{8}$/'],
            'payer.email' => 'required|email|max:255',
            'payer.identification.type' => 'nullable|string|max:20',
            'payer.identification.number' => 'nullable|string|max:40',
        ]);
        $validated['payment_method_id'] = 'yape';
        $validated['installments'] = 1;

        return $this->processSeatPayment($request, $validated, 'yape');
    }

    private function processSeatPayment(Request $request, array $validated, string $paymentMethod): JsonResponse
    {
        $organizationId = app(SportsOrganizationService::class)->assertCanAccessPayments($request->user());
        $token = config('services.mercadopago.token');
        if (! $token || ! config('services.mercadopago.key')) {
            return response()->json(['message' => 'El pago en línea aún no está configurado para esta organización.'], 503);
        }

        $settings = DB::table('socialevent_organization_settings')->first();
        $unitPrice = (float) ($settings->seat_price ?? 1);
        if ($unitPrice <= 0) {
            return response()->json(['message' => 'El precio de los espacios no está configurado. Contacta al administrador.'], 422);
        }

        $quantity = (int) $validated['quantity'];
        $amount = round($unitPrice * $quantity, 2);
        $externalReference = (string) Str::uuid();
        $idempotencyKey = (string) Str::uuid();
        $purchaseId = DB::table('socialevent_organization_seat_purchases')->insertGetId([
            'organization_id' => $organizationId,
            'purchased_by' => $request->user()->id,
            'external_reference' => $externalReference,
            'idempotency_key' => $idempotencyKey,
            'payment_method' => $paymentMethod,
            'status' => 'processing',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            MercadoPagoConfig::setAccessToken($token);
            $payment = (new PaymentClient())->create([
                'token' => $validated['token'],
                'issuer_id' => $validated['issuer_id'] ?? null,
                'payment_method_id' => $paymentMethod === 'yape' ? 'yape' : $validated['payment_method_id'],
                'transaction_amount' => $amount,
                'installments' => $validated['installments'],
                'description' => 'Compra de ' . $quantity . ' espacio(s) de usuario',
                'external_reference' => $externalReference,
                'payer' => $paymentMethod === 'yape'
                    ? [
                        ...$validated['payer'],
                        'phone' => ['area_code' => '51', 'number' => $validated['phone_number']],
                    ]
                    : $validated['payer'],
            ], new RequestOptions(null, null, ['X-Idempotency-Key: ' . $idempotencyKey]));
        } catch (\Throwable $error) {
            DB::table('socialevent_organization_seat_purchases')->where('id', $purchaseId)->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);
            report($error);

            return response()->json(['message' => 'Mercado Pago no pudo procesar el cobro. Verifica los datos e intenta nuevamente.'], 422);
        }

        DB::table('socialevent_organization_seat_purchases')->where('id', $purchaseId)->update([
            'provider_payment_id' => (string) $payment->id,
            'status' => (string) $payment->status,
            'updated_at' => now(),
        ]);

        if ($payment->status === 'approved') {
            $this->creditApprovedTopUp((string) $payment->id);
        }

        return response()->json([
            'status' => $payment->status,
            'status_detail' => $payment->status_detail,
        ]);
    }

    private function isAllowedCardMethod(string $methodId): bool
    {
        if ($methodId === 'yape' || ! config('services.mercadopago.token')) return false;

        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.token'));
            $result = (new PaymentMethodClient())->list();
            foreach ((array) $result->data as $method) {
                if (($method->id ?? null) === $methodId) {
                    return in_array($method->payment_type_id ?? null, ['credit_card', 'debit_card'], true);
                }
            }
        } catch (\Throwable $error) {
            report($error);
        }

        return false;
    }

    private function processPayment(Request $request, array $validated, string $paymentMethod): JsonResponse
    {
        $organizationId = app(SportsOrganizationService::class)->assertCanAccessPayments($request->user());
        $token = config('services.mercadopago.token');
        if (! $token || ! config('services.mercadopago.key')) {
            return response()->json(['message' => 'El pago en línea aún no está configurado para esta organización.'], 503);
        }

        $amount = round((float) $validated['amount'], 2);
        $externalReference = (string) Str::uuid();
        $idempotencyKey = (string) Str::uuid();
        $topUpId = DB::table('socialevent_organization_topups')->insertGetId([
            'organization_id' => $organizationId,
            'user_id' => $request->user()->id,
            'external_reference' => $externalReference,
            'idempotency_key' => $idempotencyKey,
            'payment_method' => $paymentMethod,
            'status' => 'processing',
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            MercadoPagoConfig::setAccessToken($token);
            $payment = (new PaymentClient())->create([
                'token' => $validated['token'],
                'issuer_id' => $validated['issuer_id'] ?? null,
                'payment_method_id' => $paymentMethod === 'yape' ? 'yape' : $validated['payment_method_id'],
                'transaction_amount' => $amount,
                'installments' => $validated['installments'],
                'description' => 'Recarga de saldo deportivo',
                'external_reference' => $externalReference,
                'payer' => $paymentMethod === 'yape'
                    ? [
                        ...$validated['payer'],
                        'phone' => ['area_code' => '51', 'number' => $validated['phone_number']],
                    ]
                    : $validated['payer'],
            ], new RequestOptions(null, null, ['X-Idempotency-Key: ' . $idempotencyKey]));
        } catch (\Throwable $error) {
            DB::table('socialevent_organization_topups')->where('id', $topUpId)->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);

            report($error);

            return response()->json(['message' => 'Mercado Pago no pudo procesar el cobro. Verifica los datos e intenta nuevamente.'], 422);
        }

        DB::table('socialevent_organization_topups')->where('id', $topUpId)->update([
            'provider_payment_id' => (string) $payment->id,
            'status' => (string) $payment->status,
            'updated_at' => now(),
        ]);

        if ($payment->status === 'approved') {
            $this->creditApprovedTopUp((string) $payment->id);
        }

        return response()->json([
            'status' => $payment->status,
            'status_detail' => $payment->status_detail,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = (string) $request->header('x-signature');
        $requestId = (string) $request->header('x-request-id');
        $query = $request->query->all();
        $dataId = (string) ($query['data.id'] ?? data_get($query, 'data.id') ?? data_get($request->all(), 'data.id', ''));
        $secret = (string) config('services.mercadopago.webhook_secret');
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($value !== null) $parts[$key] = $value;
        }

        if (! $secret || empty($parts['ts']) || empty($parts['v1']) || ! $requestId || ! $dataId) {
            return response()->json(['message' => 'Notificación incompleta.'], 401);
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$parts['ts']};";
        $expectedSignature = hash_hmac('sha256', $manifest, $secret);
        if (! hash_equals($expectedSignature, (string) $parts['v1'])) {
            return response()->json(['message' => 'Firma inválida.'], 401);
        }

        $notificationType = (string) ($request->query('type') ?? $request->input('type', ''));
        if ($notificationType === 'payment' && ctype_digit($dataId)) {
            $this->creditApprovedTopUp($dataId);
        }

        return response()->json(['received' => true]);
    }

    private function creditApprovedTopUp(string $paymentId): void
    {
        $accessToken = config('services.mercadopago.token');
        if (! $accessToken) return;

        $response = Http::withToken($accessToken)->acceptJson()->get("https://api.mercadopago.com/v1/payments/{$paymentId}");
        if ($response->successful()) {
            app(OrganizationTopUpService::class)->creditApprovedPayment($response->json());
        }
    }
}
