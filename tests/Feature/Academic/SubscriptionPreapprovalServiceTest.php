<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Services\MercadoPago\SubscriptionPreapprovalGateway;
use Modules\Academic\Services\SubscriptionPreapprovalService;
use Tests\TestCase;

/**
 * Alta de la suscripción recurrente: payload enviado a MercadoPago y
 * persistencia local con el snapshot fiscal.
 */
class SubscriptionPreapprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_el_preapproval_mensual_y_guarda_el_snapshot_fiscal(): void
    {
        $gateway = $this->fakeGateway();
        [$student, $plan] = $this->escenario();

        $preapproval = app(SubscriptionPreapprovalService::class)->create($student, $plan, [
            'token' => 'card-token-123',
            'payer' => ['email' => 'alumno@example.com'],
            'personInvoice' => [
                'document_type' => 'boleta',
                'razonSocial' => 'Ana Pérez',
                'ruc' => '12345678',
            ],
        ]);

        $this->assertCount(1, $gateway->payloads);
        $payload = $gateway->payloads[0];

        $this->assertSame('authorized', $payload['status']);
        $this->assertSame('card-token-123', $payload['card_token_id']);
        $this->assertSame(1, $payload['auto_recurring']['frequency']);
        $this->assertSame('months', $payload['auto_recurring']['frequency_type']);
        $this->assertEqualsWithDelta(49.90, $payload['auto_recurring']['transaction_amount'], 0.001);
        $this->assertSame('PEN', $payload['auto_recurring']['currency_id']);
        $this->assertStringContainsString('aca:student:' . $student->id . ':plan:' . $plan->id, $payload['external_reference']);

        $this->assertSame('preapproval-1', $preapproval->preapproval_id);
        $this->assertSame('authorized', $preapproval->status);
        $this->assertSame('PEN', $preapproval->currency_id);
        $this->assertEqualsWithDelta(49.90, (float) $preapproval->amount, 0.001);
        $this->assertSame('boleta', $preapproval->invoice_data['document_type']);
        $this->assertSame('Ana Pérez', $preapproval->invoice_data['razon_social']);
        $this->assertLessThanOrEqual(28, $preapproval->billing_day);
        $this->assertNotNull($preapproval->next_payment_date);
    }

    public function test_no_cobra_de_nuevo_si_ya_hay_una_suscripcion_activa(): void
    {
        $gateway = $this->fakeGateway();
        [$student, $plan] = $this->escenario();

        $existente = AcaSubscriptionPreapproval::create([
            'student_id' => $student->id,
            'subscription_id' => $plan->id,
            'preapproval_id' => 'preapproval-existente',
            'status' => 'authorized',
            'amount' => 49.90,
            'currency_id' => 'PEN',
        ]);

        $resultado = app(SubscriptionPreapprovalService::class)->create($student, $plan, [
            'token' => 'otro-token',
            'payer' => ['email' => 'alumno@example.com'],
        ]);

        $this->assertSame($existente->id, $resultado->id);
        $this->assertCount(0, $gateway->payloads);
    }

    public function test_rechaza_planes_que_no_son_mensuales(): void
    {
        $this->fakeGateway();
        [$student, $plan] = $this->escenario(['period' => 'Anual']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('solo se admiten suscripciones mensuales');

        app(SubscriptionPreapprovalService::class)->create($student, $plan, ['token' => 'token']);
    }

    public function test_rechaza_un_token_vacio(): void
    {
        $this->fakeGateway();
        [$student, $plan] = $this->escenario();

        $this->expectException(InvalidArgumentException::class);

        app(SubscriptionPreapprovalService::class)->create($student, $plan, ['token' => '']);
    }

    public function test_toma_el_correo_del_pagador_del_checkout(): void
    {
        $this->fakeGateway();
        [$student, $plan] = $this->escenario();

        $preapproval = app(SubscriptionPreapprovalService::class)->create($student, $plan, [
            'token' => 'token',
            'payer' => ['email' => 'pagador@example.com'],
        ]);

        $this->assertSame('pagador@example.com', $preapproval->payer_email);
    }

    private function fakeGateway(): FakePreapprovalGateway
    {
        $gateway = new FakePreapprovalGateway();
        $this->app->instance(SubscriptionPreapprovalGateway::class, $gateway);

        return $gateway;
    }

    /**
     * @return array{0: AcaStudent, 1: AcaSubscriptionType}
     */
    private function escenario(array $overrides = []): array
    {
        $student = AcaStudent::create([
            'person_id' => 9001,
            'student_code' => 'T9001',
        ]);

        $plan = AcaSubscriptionType::create(array_merge([
            'title' => 'Plan mensual de prueba',
            'usine' => 'academic',
            'period' => 'Mensual',
            'status' => true,
            'recurring_enabled' => true,
            'prices' => [['currency' => 'PEN', 'amount' => '49.90', 'detail' => null]],
        ], $overrides));

        return [$student, $plan];
    }
}

/**
 * Gateway falso: registra el payload y responde como MercadoPago autorizado.
 */
class FakePreapprovalGateway implements SubscriptionPreapprovalGateway
{
    /** @var array<int, array<string, mixed>> */
    public array $payloads = [];

    public function create(array $payload): array
    {
        $this->payloads[] = $payload;
        $id = 'preapproval-' . count($this->payloads);

        return [
            'id' => $id,
            'status' => 'authorized',
            'next_payment_date' => now()->addMonth()->toIso8601String(),
            'init_point' => null,
            'raw' => ['id' => $id, 'status' => 'authorized'],
        ];
    }
}
