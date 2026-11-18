<?php

namespace Tests\Feature\Academic;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\SubscriptionChargeFailed;
use Modules\Academic\Emails\SubscriptionGraceExpired;
use Modules\Academic\Emails\SubscriptionRenewed;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Entities\MercadoPagoEvent;
use Modules\Academic\Services\MercadoPago\SubscriptionPaymentGateway;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationExecLog;
use Tests\TestCase;

/**
 * Webhook de suscripciones: firma, conciliación del primer cobro, renovación
 * mensual y suspensión al terminar la gracia.
 */
class MercadoPagoSubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_prueba';

    private FakeSubscriptionPaymentGateway $gateway;

    private AcaSubscriptionPreapproval $preapproval;

    private int $personId;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config([
            'services.mercadopago.webhook_secret' => '',
            'services.mercadopago.subscription_grace_days' => 3,
        ]);

        $this->registrarSecretoEnHub();
        $this->gateway = new FakeSubscriptionPaymentGateway();
        $this->app->instance(SubscriptionPaymentGateway::class, $this->gateway);

        $this->personId = (int) DB::table('people')->insertGetId([
            'document_type_id' => '1',
            'full_name' => 'Alumno Prueba',
            'number' => '44556677',
            'email' => 'alumno@example.com',
            'gender' => 'M',
            'country_id' => 1,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = AcaStudent::create([
            'person_id' => $this->personId,
            'student_code' => '44556677',
        ]);

        $plan = AcaSubscriptionType::create([
            'title' => 'Plan Mensual',
            'usine' => 'academic',
            'period' => 'Mensual',
            'status' => true,
            'recurring_enabled' => true,
            'prices' => [['currency' => 'PEN', 'amount' => 49.90]],
        ]);

        $this->preapproval = AcaSubscriptionPreapproval::create([
            'student_id' => $student->id,
            'subscription_id' => $plan->id,
            'preapproval_id' => 'preapproval-hook-1',
            'status' => 'authorized',
            'amount' => 49.90,
            'currency_id' => 'PEN',
            'next_payment_date' => now()->addMonth()->toDateString(),
            'payer_email' => 'alumno@example.com',
        ]);

        AcaStudentSubscription::create([
            'student_id' => $student->id,
            'subscription_id' => $plan->id,
            'date_start' => now()->toDateString(),
            'date_end' => now()->addMonth()->toDateString(),
            'status' => true,
        ]);
    }

    public function test_rechaza_una_firma_invalida(): void
    {
        $this->postJson($this->urlWebhook(111), ['type' => 'subscription_authorized_payment'], [
            'x-signature' => 'ts=123,v1=firma-falsa',
            'x-request-id' => 'req-1',
        ])->assertStatus(401);

        $this->assertSame(0, MercadoPagoEvent::count());
    }

    public function test_sin_secreto_configurado_responde_503(): void
    {
        Integration::query()->where('name', 'MercadoPago')->firstOrFail()
            ->auths()->where('field_name', 'webhook_secret')->update(['field_value' => null]);

        $firma = $this->firma(111, '');
        $this->postJson($this->urlWebhook(111), ['type' => 'subscription_authorized_payment'], $firma['headers'])
            ->assertStatus(503);
    }

    public function test_concilia_el_primer_cobro_sin_reenviar_el_correo_de_compra(): void
    {
        $pendiente = AcaSubscriptionPayment::create([
            'student_id' => $this->preapproval->student_id,
            'subscription_id' => $this->preapproval->subscription_id,
            'date_start' => now()->toDateString(),
            'date_end' => now()->addMonth()->toDateString(),
            'amount' => 49.90,
            'preapproval_id' => $this->preapproval->preapproval_id,
            'status' => 'authorized',
            'source' => 'recurring',
        ]);

        $this->gateway->invoices[111] = $this->invoice(111, 'approved');

        $this->postJson($this->urlWebhook(111), ['type' => 'subscription_authorized_payment'], $this->firma(111)['headers'])
            ->assertOk()
            ->assertJsonPath('result', 'reconciled');

        $pendiente->refresh();
        $this->assertSame('111', $pendiente->authorized_payment_id);
        $this->assertSame('approved', $pendiente->status);
        $this->assertNotNull($pendiente->paid_at);

        Mail::assertNothingQueued();
        $this->assertNotNull(MercadoPagoEvent::first()->processed_at);
    }

    public function test_registra_el_mes_siguiente_y_avisa_la_renovacion(): void
    {
        $this->gateway->invoices[222] = $this->invoice(222, 'approved');

        $this->postJson($this->urlWebhook(222), ['type' => 'subscription_authorized_payment'], $this->firma(222)['headers'])
            ->assertOk()
            ->assertJsonPath('result', 'charged');

        $cobro = AcaSubscriptionPayment::where('authorized_payment_id', '222')->firstOrFail();
        $this->assertSame('recurring', $cobro->source);
        $this->assertSame('approved', $cobro->status);

        Mail::assertQueued(SubscriptionRenewed::class, 1);

        // El mes nuevo arranca al terminar el acceso que ya tenía el alumno.
        $this->assertSame(now()->addMonth()->addDay()->toDateString(), $cobro->date_start->toDateString());

        $suscripcion = AcaStudentSubscription::where('student_id', $this->preapproval->student_id)->firstOrFail();
        $this->assertSame($cobro->date_end->toDateString(), (string) $suscripcion->date_end);
        $this->assertSame(1, DB::table('onli_sales')->count());
    }

    public function test_una_notificacion_repetida_no_duplica_el_cobro(): void
    {
        $this->gateway->invoices[333] = $this->invoice(333, 'approved');

        $this->postJson($this->urlWebhook(333), ['type' => 'subscription_authorized_payment'], $this->firma(333)['headers'])
            ->assertOk()
            ->assertJsonPath('result', 'charged');

        $this->postJson($this->urlWebhook(333), ['type' => 'subscription_authorized_payment'], $this->firma(333)['headers'])
            ->assertOk()
            ->assertJsonPath('result', 'already_processed');

        $this->assertSame(1, AcaSubscriptionPayment::where('authorized_payment_id', '333')->count());
        Mail::assertQueued(SubscriptionRenewed::class, 1);
    }

    public function test_un_rechazo_avisa_y_suspende_al_terminar_la_gracia(): void
    {
        $this->preapproval->forceFill([
            'next_payment_date' => now()->subDays(5)->toDateString(),
        ])->save();

        $this->gateway->invoices[444] = $this->invoice(444, 'rejected', 'rejected');

        $this->postJson($this->urlWebhook(444), ['type' => 'subscription_authorized_payment'], $this->firma(444)['headers'])
            ->assertOk()
            ->assertJsonPath('result', 'suspended');

        Mail::assertQueued(SubscriptionChargeFailed::class, 1);
        Mail::assertQueued(SubscriptionGraceExpired::class, 1);

        $this->assertSame(0, (int) AcaStudentSubscription::where('student_id', $this->preapproval->student_id)->value('status'));
        $this->assertSame('paused', $this->preapproval->fresh()->status);
    }

    public function test_ignora_eventos_que_no_son_cobros_de_suscripcion(): void
    {
        $firma = $this->firma(555);

        $this->postJson($this->urlWebhook(555), ['type' => 'payment'], $firma['headers'])
            ->assertOk()
            ->assertJsonPath('ignored', true);

        // El gateway falso lanzaría excepción si se hubiera consultado el cobro.
        $this->assertNotNull(MercadoPagoEvent::first()->processed_at);
    }

    public function test_ignora_un_cobro_de_una_suscripcion_desconocida(): void
    {
        $this->gateway->invoices[666] = array_merge($this->invoice(666, 'approved'), [
            'preapproval_id' => 'preapproval-inexistente',
        ]);

        $this->postJson($this->urlWebhook(666), ['type' => 'subscription_authorized_payment'], $this->firma(666)['headers'])
            ->assertOk()
            ->assertJsonPath('result', 'ignored');
    }

    public function test_rechaza_una_notificacion_sin_encabezados_de_firma(): void
    {
        $this->postJson($this->urlWebhook(777), ['type' => 'subscription_authorized_payment'])
            ->assertStatus(401);
    }

    public function test_la_url_responde_a_get_para_confirmar_que_esta_activa(): void
    {
        $this->getJson(route('aca_mercadopago_subscription_webhook'))
            ->assertOk()
            ->assertJsonPath('active', true);

        $this->assertSame('GET', data_get(IntegrationExecLog::firstOrFail()->request_payload, 'metodo'));
    }

    public function test_acepta_la_notificacion_de_prueba_del_panel_sin_procesarla(): void
    {
        // Payload fijo del botón "Simular notificación": sin firma y con data.id ficticio.
        $this->postJson(route('aca_mercadopago_subscription_webhook'), [
            'action' => 'updated',
            'type' => 'subscription_preapproval',
            'entity' => 'preapproval',
            'data' => ['id' => '123456'],
        ])->assertOk()->assertJsonPath('simulation', true);

        // No se consulta el gateway ni se registra un evento de cobro.
        $this->assertSame(0, MercadoPagoEvent::count());
        $this->assertSame(0, AcaSubscriptionPayment::count());

        // Pero la llegada sí queda visible en el Historial de IntegrationHub.
        $this->assertSame('success', IntegrationExecLog::firstOrFail()->status);
    }

    public function test_registra_cada_llegada_en_el_historial_de_la_integracion(): void
    {
        // Prueba del panel de MercadoPago: antes no dejaba rastro.
        $this->postJson(route('aca_mercadopago_subscription_webhook'), [
            'action' => 'updated',
            'type' => 'subscription_preapproval',
            'data' => ['id' => '123456'],
        ])->assertOk();

        // Cobro con firma inválida: también se guarda, ya como fallo.
        $this->postJson($this->urlWebhook(999), ['type' => 'subscription_authorized_payment'], [
            'x-signature' => 'ts=123,v1=firma-falsa',
            'x-request-id' => 'req-999',
        ])->assertStatus(401);

        $llegadas = IntegrationExecLog::orderBy('id')->get();
        $this->assertCount(2, $llegadas);

        $integracionId = (int) Integration::query()->where('name', 'MercadoPago')->value('id');

        $prueba = $llegadas[0];
        $this->assertSame($integracionId, $prueba->integration_id);
        $this->assertSame('success', $prueba->status);
        $this->assertSame(200, $prueba->response_status_code);
        $this->assertSame('webhook:subscription_preapproval', $prueba->batch_id);
        $this->assertNull($prueba->endpoint_id);
        $this->assertSame('123456', data_get($prueba->request_payload, 'payload.data.id'));
        $this->assertSame('ausente', data_get($prueba->request_payload, 'encabezados.x-signature'));

        $invalida = $llegadas[1];
        $this->assertSame('failed', $invalida->status);
        $this->assertSame(401, $invalida->response_status_code);
        $this->assertStringContainsString('firma_invalida', (string) $invalida->error_message);
    }

    public function test_devuelve_500_y_guarda_el_error_si_falla_la_consulta_del_cobro(): void
    {
        // Sin entrada en el gateway falso, authorizedPayment() lanza excepción.
        $this->postJson($this->urlWebhook(888), ['type' => 'subscription_authorized_payment'], $this->firma(888)['headers'])
            ->assertStatus(500);

        $evento = MercadoPagoEvent::firstOrFail();
        $this->assertNull($evento->processed_at);
        $this->assertNotNull($evento->error);
    }

    private function urlWebhook(int $id): string
    {
        return route('aca_mercadopago_subscription_webhook') . '?data.id=' . $id;
    }

    /**
     * @return array{headers: array<string, string>}
     */
    private function firma(int $id, string $secret = self::SECRET): array
    {
        $ts = (string) now()->timestamp;
        $requestId = 'req-' . $id;
        $manifest = "id:{$id};request-id:{$requestId};ts:{$ts};";

        return [
            'headers' => [
                'x-signature' => 'ts=' . $ts . ',v1=' . hash_hmac('sha256', $manifest, $secret),
                'x-request-id' => $requestId,
            ],
        ];
    }

    private function registrarSecretoEnHub(): void
    {
        $integration = Integration::query()->where('name', 'MercadoPago')->first();

        if (! $integration) {
            $integration = Integration::create([
                'name' => 'MercadoPago',
                'url_base' => 'https://api.mercadopago.com',
                'execution_type' => 'manual',
                'is_active' => true,
            ]);
        }

        $integration->auths()->updateOrCreate(
            ['field_name' => 'webhook_secret'],
            [
                'field_value' => self::SECRET,
                'field_type' => 'password',
                'auth_location' => 'header',
                'is_enabled' => true,
            ]
        );

        // El resolutor cachea por petición: se descarta la instancia anterior.
        $this->app->forgetInstance(\Modules\Integrationhub\Services\IntegrationCredentialResolver::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoice(int $id, string $status, string $paymentStatus = 'approved'): array
    {
        return [
            'id' => $id,
            'preapproval_id' => $this->preapproval->preapproval_id,
            'status' => $status,
            'transaction_amount' => 49.90,
            'currency_id' => 'PEN',
            'debit_date' => now()->toIso8601String(),
            'next_retry_date' => now()->addDays(3)->toDateString(),
            'payment' => ['id' => $id + 9000, 'status' => $paymentStatus],
            'raw' => ['id' => $id, 'status' => $status],
        ];
    }
}

/**
 * Gateway falso de cobros autorizados.
 */
class FakeSubscriptionPaymentGateway implements SubscriptionPaymentGateway
{
    /** @var array<int, array<string, mixed>> */
    public array $invoices = [];

    public function authorizedPayment(int $id): array
    {
        if (! isset($this->invoices[$id])) {
            throw new \RuntimeException('Cobro no configurado en la prueba: ' . $id);
        }

        return $this->invoices[$id];
    }
}
