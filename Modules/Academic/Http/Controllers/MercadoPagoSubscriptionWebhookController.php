<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\MercadoPagoEvent;
use Modules\Academic\Services\MercadoPago\SubscriptionPaymentGateway;
use Modules\Academic\Services\MercadoPagoWebhookSignature;
use Modules\Academic\Services\SubscriptionChargeService;
use Modules\Integrationhub\Services\IntegrationCredentialResolver;

/**
 * Webhook de suscripciones de MercadoPago.
 *
 * Valida la firma con el secreto administrado en IntegrationHub, guarda la
 * notificación en `mercadopago_events` y procesa el cobro autorizado. Responde
 * 2xx cuando la notificación ya no necesita reintentos y 500 si el proceso
 * falló (MercadoPago reintenta).
 */
class MercadoPagoSubscriptionWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = app(IntegrationCredentialResolver::class)
            ->value('MercadoPago', 'webhook_secret', (string) config('services.mercadopago.webhook_secret'));

        if ($secret === null || trim($secret) === '') {
            Log::warning('[Suscripciones] Webhook sin secreto configurado en IntegrationHub.');

            return response()->json(['message' => 'Webhook sin configurar.'], 503);
        }

        $firma = app(MercadoPagoWebhookSignature::class)->validate($request, $secret);

        if (! $firma['valid']) {
            Log::warning('[Suscripciones] Notificación con firma inválida.', ['motivo' => $firma['reason']]);

            return response()->json(['message' => 'Firma inválida.'], 401);
        }

        $topic = (string) ($request->input('type') ?? $request->query('topic') ?? $request->query('type') ?? '');
        $action = $request->input('action');
        $dataId = $firma['data_id'];

        $evento = MercadoPagoEvent::create([
            'topic' => mb_substr($topic !== '' ? $topic : 'desconocido', 0, 40),
            'action' => $action ? mb_substr((string) $action, 0, 40) : null,
            'data_id' => mb_substr($dataId, 0, 64),
            'payload' => $request->all() + ['query' => $request->query->all()],
        ]);

        // Solo los cobros de una suscripción necesitan procesarse aquí.
        if (! in_array($topic, ['subscription_authorized_payment', 'authorized_payment'], true)) {
            $evento->forceFill(['processed_at' => now()])->save();

            return response()->json(['received' => true, 'ignored' => true]);
        }

        if (! ctype_digit($dataId)) {
            $evento->forceFill(['processed_at' => now(), 'error' => 'data.id no numérico'])->save();

            return response()->json(['received' => true, 'ignored' => true]);
        }

        try {
            $invoice = app(SubscriptionPaymentGateway::class)->authorizedPayment((int) $dataId);
            $resultado = app(SubscriptionChargeService::class)->handleAuthorizedPayment($invoice);

            $evento->forceFill(['processed_at' => now()])->save();

            return response()->json(['received' => true, 'result' => $resultado]);
        } catch (\Throwable $e) {
            report($e);

            $evento->forceFill(['error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            return response()->json(['message' => 'No se pudo procesar la notificación.'], 500);
        }
    }
}
