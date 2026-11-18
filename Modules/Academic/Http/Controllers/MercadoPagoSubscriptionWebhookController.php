<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\MercadoPagoEvent;
use Modules\Academic\Services\MercadoPago\SubscriptionPaymentGateway;
use Modules\Academic\Services\MercadoPagoWebhookHistory;
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
 *
 * El botón "Simular notificación" del panel de MercadoPago envía un payload
 * fijo (data.id = 123456, type = subscription_preapproval) SIN firma; esa
 * petición se acepta con 200 para que la prueba de la URL pase, pero no se
 * procesa nada: no hay cobro ni consulta al gateway.
 */
class MercadoPagoSubscriptionWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $inicio = microtime(true);

        $respuesta = $this->procesar($request);

        // Cada llegada queda en el Historial de la integración MercadoPago,
        // incluso la prueba del panel o una firma inválida: así se puede ver
        // qué envió MercadoPago y qué le respondimos.
        app(MercadoPagoWebhookHistory::class)->record($request, $respuesta, $inicio);

        return $respuesta;
    }

    /**
     * Valida, guarda y procesa la notificación recibida.
     */
    private function procesar(Request $request): JsonResponse
    {
        // Abrir la URL en el navegador envía GET: se responde un JSON corto en
        // lugar de la página de error 405 de Laravel.
        if ($request->isMethod('get')) {
            return response()->json([
                'webhook' => 'MercadoPago · suscripciones',
                'active' => true,
                'message' => 'Punto de recepción activo. MercadoPago envía las notificaciones por POST firmado.',
            ]);
        }

        $secret = app(IntegrationCredentialResolver::class)
            ->value('MercadoPago', 'webhook_secret', (string) config('services.mercadopago.webhook_secret'));

        if ($secret === null || trim($secret) === '') {
            Log::warning('[Suscripciones] Webhook sin secreto configurado en IntegrationHub.');

            return response()->json(['message' => 'Webhook sin configurar.'], 503);
        }

        // Prueba del panel de MercadoPago: llega sin x-signature y con un
        // identificador ficticio, así que no se puede validar ni conciliar.
        if ($this->esSimulacionDeMercadoPago($request)) {
            Log::info('[Suscripciones] Notificación de prueba de MercadoPago recibida (sin firma, no se procesa).');

            return response()->json([
                'received' => true,
                'simulation' => true,
                'message' => 'Prueba de MercadoPago recibida: el webhook está activo.',
            ]);
        }

        $firma = app(MercadoPagoWebhookSignature::class)->validate($request, $secret);

        if (! $firma['valid']) {
            Log::warning('[Suscripciones] Notificación con firma inválida.', ['motivo' => $firma['reason']]);

            return response()->json(['message' => 'Firma inválida.', 'reason' => $firma['reason']], 401);
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

    /**
     * Detecta el payload fijo del botón "Simular notificación" del panel de
     * MercadoPago. Se compara el tipo y el identificador ficticio, no la firma:
     * esa petición no la trae. El id real de un cobro o preapproval nunca es
     * 123456, así que no abre la puerta a notificaciones externas.
     */
    private function esSimulacionDeMercadoPago(Request $request): bool
    {
        $tipo = (string) ($request->input('type') ?? $request->query('type') ?? '');
        $dataId = (string) (data_get($request->all(), 'data.id') ?? $request->query('data.id') ?? '');

        return $dataId === '123456'
            && in_array($tipo, ['subscription_preapproval', 'preapproval'], true);
    }
}
