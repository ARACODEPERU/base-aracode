<?php

namespace Tests\Feature\Academic;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\SubscriptionRenewed;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionTestLog;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Services\IntegrationCredentialResolver;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Modo prueba de cobros recurrentes: usa solo credenciales de PRUEBA, crea la
 * suscripción, simula la renovación con correo y limpia lo generado.
 */
class SubscriptionTestModeTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_TOKEN = 'TEST-TOKEN-SUSCRIPCIONES';

    private User $user;

    private AcaSubscriptionType $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'services.mercadopago.token' => 'APP_USR-TOKEN-PRODUCCION',
            'services.mercadopago.test_token' => null,
            'services.mercadopago.test_public_key' => null,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('aca_suscripciones_editar', 'web');

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->givePermissionTo('aca_suscripciones_editar');

        $integration = Integration::query()->where('name', 'MercadoPago')->firstOrFail();
        $integration->auths()->updateOrCreate(
            ['field_name' => 'test_access_token'],
            ['field_value' => self::TEST_TOKEN, 'field_type' => 'password', 'is_enabled' => true]
        );
        $integration->auths()->updateOrCreate(
            ['field_name' => 'test_public_key'],
            ['field_value' => 'TEST-PUBLIC-KEY', 'field_type' => 'text', 'is_enabled' => true]
        );
        $this->app->forgetInstance(IntegrationCredentialResolver::class);

        $this->plan = AcaSubscriptionType::create([
            'title' => 'Plan Mensual Prueba',
            'usine' => 'academic',
            'period' => 'Mensual',
            'status' => true,
            'recurring_enabled' => true,
            'test_mode' => true,
            'prices' => [['currency' => 'PEN', 'amount' => 49.90]],
        ]);

        Http::fake([
            'api.mercadopago.com/v1/card_tokens*' => Http::response(['id' => 'card-token-1'], 201),
            'api.mercadopago.com/preapproval' => Http::response([
                'id' => 'preapproval-test-1',
                'status' => 'authorized',
                'next_payment_date' => now()->addMonth()->toDateString(),
            ], 201),
            'api.mercadopago.com/preapproval/*' => Http::response(['status' => 'cancelled'], 200),
        ]);
    }

    public function test_crea_la_suscripcion_de_prueba_con_el_token_de_prueba(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_start', $this->plan->id), ['email' => 'prueba@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('preapproval_id', 'preapproval-test-1');

        $this->assertDatabaseHas('aca_subscription_preapprovals', [
            'subscription_id' => $this->plan->id,
            'preapproval_id' => 'preapproval-test-1',
            'is_test' => 1,
        ]);

        $this->assertDatabaseHas('aca_subscription_payments', [
            'subscription_id' => $this->plan->id,
            'is_test' => 1,
            'status' => 'pending',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v1/card_tokens')
                && str_contains($request->url(), 'public_key=TEST-PUBLIC-KEY');
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mercadopago.com/preapproval'
                && $request->hasHeader('Authorization', 'Bearer ' . self::TEST_TOKEN)
                && ($request['card_token_id'] ?? null) === 'card-token-1';
        });
    }

    public function test_la_prueba_trimestral_cobra_cada_tres_meses(): void
    {
        $this->plan->update(['period' => 'Trimestral']);

        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_start', $this->plan->id), ['email' => 'prueba@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mercadopago.com/preapproval'
                && ($request['auto_recurring']['frequency'] ?? null) === 3
                && ($request['auto_recurring']['frequency_type'] ?? null) === 'months';
        });

        $preapproval = AcaSubscriptionPreapproval::query()
            ->where('subscription_id', $this->plan->id)
            ->firstOrFail();

        $this->assertSame(3, $preapproval->frequency);

        $payment = AcaSubscriptionPayment::query()
            ->where('subscription_id', $this->plan->id)
            ->firstOrFail();

        $this->assertSame(
            now()->copy()->addMonths(3)->toDateString(),
            $payment->date_end->toDateString(),
            'El primer periodo de prueba debe cubrir 3 meses.'
        );
    }

    public function test_bloquea_si_el_token_de_prueba_es_el_de_produccion(): void
    {
        config(['services.mercadopago.test_token' => 'APP_USR-TOKEN-PRODUCCION']);
        Integration::query()->where('name', 'MercadoPago')->firstOrFail()
            ->auths()->where('field_name', 'test_access_token')->update(['field_value' => null]);
        $this->app->forgetInstance(IntegrationCredentialResolver::class);

        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_start', $this->plan->id), ['email' => 'prueba@example.com'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_simula_la_renovacion_con_correo_y_registros(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_start', $this->plan->id), ['email' => 'prueba@example.com'])
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_renew', $this->plan->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        // Primer cobro conciliado + mes siguiente cobrado.
        $this->assertSame(
            2,
            AcaSubscriptionPayment::query()
                ->where('subscription_id', $this->plan->id)
                ->where('is_test', true)
                ->where('status', 'approved')
                ->count()
        );

        Mail::assertQueued(SubscriptionRenewed::class);

        $this->assertDatabaseHas('aca_subscription_test_logs', [
            'subscription_id' => $this->plan->id,
            'action' => 'renewal',
        ]);
    }

    public function test_envia_el_aviso_de_pago_exitoso_aunque_el_cobro_siga_pendiente(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_start', $this->plan->id), ['email' => 'prueba@example.com'])
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_notice', $this->plan->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('email', 'prueba@example.com');

        Mail::assertQueued(SubscriptionRenewed::class, function (SubscriptionRenewed $mail) {
            return $mail->hasTo('prueba@example.com');
        });

        $this->assertDatabaseHas('aca_subscription_test_logs', [
            'subscription_id' => $this->plan->id,
            'action' => 'notice',
        ]);
    }

    public function test_no_borra_un_alumno_real_usado_en_la_prueba(): void
    {
        $persona = Person::create([
            'document_type_id' => '1',
            'names' => 'Alumno',
            'father_lastname' => 'Real',
            'mother_lastname' => 'Existente',
            'full_name' => 'Alumno Real Existente',
            'email' => 'prueba@example.com',
            'number' => 'REAL-0001',
            'gender' => 'M',
            'country_id' => 1,
            'is_client' => true,
            'status' => true,
        ]);

        $alumno = AcaStudent::create([
            'person_id' => $persona->id,
            'student_code' => 'REAL-0001',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_start', $this->plan->id), ['email' => 'prueba@example.com'])
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_purge', $this->plan->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        // La prueba se limpió, pero la persona y el alumno reales siguen ahí.
        $this->assertSame(0, AcaSubscriptionPreapproval::query()->where('subscription_id', $this->plan->id)->count());
        $this->assertDatabaseHas('people', ['id' => $persona->id]);
        $this->assertDatabaseHas('aca_students', ['id' => $alumno->id]);
    }

    public function test_borra_solo_los_datos_de_prueba(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_start', $this->plan->id), ['email' => 'prueba@example.com'])
            ->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('aca_subscriptions_test_purge', $this->plan->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, AcaSubscriptionPreapproval::query()->where('subscription_id', $this->plan->id)->count());
        $this->assertSame(0, AcaSubscriptionPayment::query()->where('subscription_id', $this->plan->id)->count());
        $this->assertSame(0, AcaSubscriptionTestLog::query()->where('subscription_id', $this->plan->id)->count());

        // El alumno y la persona creados por la prueba también se borran.
        $this->assertDatabaseCount('aca_students', 0);
        $this->assertDatabaseMissing('people', ['email' => 'prueba@example.com']);

        // El plan (dato real) no se borra.
        $this->assertDatabaseHas('aca_subscription_types', ['id' => $this->plan->id, 'test_mode' => 1]);
    }
}
