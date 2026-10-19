<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Socialevents\Services\OrganizationTopUpService;
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
        // Las cuentas suspendidas no pueden entrar al módulo, pero conservan
        // acceso a esta vista para efectuar el pago que permitirá reactivarlas.
        $organizationId = $sports->assertCanAccessPayments($request->user());
        abort_unless($organizationId, 404);

        $organization = DB::table('socialevent_organizations')->where('id', $organizationId)->first();

        return Inertia::render('Socialevents::Organizations/TopUp', [
            'balance' => (float) $organization->prepaid_balance,
            'publicKey' => config('services.mercadopago.key'),
            'minimumAmount' => 10,
            'organization' => [
                'name' => (string) $organization->name,
                'match_rate' => (float) $organization->match_rate,
            ],
            // Vigencia e historial: el organizador puede entrar aquí antes de que
            // venza el acceso, sin esperar el aviso de pago.
            'access' => $sports->accessSummary($request->user()),
            'payments' => $sports->paymentHistory($request->user()),
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
