<?php

namespace Tests\Feature\Integrationhub;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Services\MercadoPagoWebhookHistory;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationExecLog;
use Tests\TestCase;

/**
 * Historial de las notificaciones que MercadoPago envía al webhook de
 * suscripciones. Se guardan en `integration_exec_logs` con endpoint nulo para
 * que la pestaña Historial de IntegrationHub muestre que la petición llegó.
 */
class MercadoPagoWebhookHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_una_llegada_exitosa_con_su_payload(): void
    {
        $request = Request::create(
            '/academic/mercadopago/subscriptions/webhook?type=subscription_authorized_payment&data.id=123',
            'POST',
            ['type' => 'subscription_authorized_payment', 'data' => ['id' => '123']],
            [],
            [],
            ['REMOTE_ADDR' => '203.0.113.7', 'HTTP_X_REQUEST_ID' => 'req-abc', 'HTTP_X_SIGNATURE' => 'ts=1,v1=firma']
        );

        app(MercadoPagoWebhookHistory::class)->record(
            $request,
            new JsonResponse(['message' => 'ok'], 200),
            microtime(true) - 0.05
        );

        $integration = Integration::query()->whereRaw('LOWER(name) = ?', ['mercadopago'])->firstOrFail();
        $log = IntegrationExecLog::query()->sole();

        $this->assertSame((int) $integration->id, (int) $log->integration_id);
        $this->assertNull($log->endpoint_id);
        $this->assertSame('success', $log->status);
        $this->assertSame(200, (int) $log->response_status_code);
        $this->assertStringContainsString('subscription_authorized_payment', (string) $log->batch_id);
        $this->assertSame('subscription_authorized_payment', $log->request_payload['payload']['type']);
        $this->assertSame('POST', $log->request_payload['metodo']);
        $this->assertSame('203.0.113.7', $log->request_payload['ip']);
        $this->assertSame('req-abc', $log->request_payload['encabezados']['x-request-id']);
        // La firma no se guarda: solo se anota si llegó.
        $this->assertSame('recibida', $log->request_payload['encabezados']['x-signature']);
        $this->assertStringNotContainsString('firma', (string) json_encode($log->request_payload));
    }

    public function test_un_rechazo_queda_como_fallido_con_su_motivo(): void
    {
        $request = Request::create('/academic/mercadopago/subscriptions/webhook', 'POST', ['type' => 'subscription_preapproval']);

        app(MercadoPagoWebhookHistory::class)->record(
            $request,
            new JsonResponse(['message' => 'Firma inválida', 'reason' => 'invalid_signature'], 401),
            microtime(true)
        );

        $log = IntegrationExecLog::query()->sole();

        $this->assertSame('failed', $log->status);
        $this->assertSame(401, (int) $log->response_status_code);
        $this->assertStringContainsString('Firma inválida', (string) $log->error_message);
        $this->assertStringContainsString('invalid_signature', (string) $log->error_message);
        // Sin cabecera de firma también se registra la llegada.
        $this->assertSame('ausente', $log->request_payload['encabezados']['x-signature']);
    }

    public function test_sin_la_integracion_no_escribe_nada(): void
    {
        Integration::query()->whereRaw('LOWER(name) = ?', ['mercadopago'])->delete();

        app(MercadoPagoWebhookHistory::class)->record(
            Request::create('/academic/mercadopago/subscriptions/webhook', 'POST'),
            new JsonResponse(['message' => 'ok'], 200),
            microtime(true)
        );

        $this->assertSame(0, IntegrationExecLog::query()->count());
    }
}
