<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fase 3 de la app móvil: plan, vigencia, saldo, movimientos y recarga.
 *
 * La recarga no se cobra desde la app: el endpoint devuelve la URL del checkout
 * web y la app la abre en el navegador. Estos tests fijan que la organización
 * sale del token, que el vencimiento conserva el acceso a pagos y que cada
 * organización solo ve sus propios movimientos.
 */
class MobileOrganizationPaymentsApiTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $organizer;

    private SocialeventOrganization $otherOrganization;

    private User $otherOrganizer;

    protected function setUp(): void
    {
        parent::setUp();

        // Modo deportivo activo: PW00001 = 2 (torneos).
        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = $this->crearOrganizacion('Organización A', 'trial', now()->subDay());
        $this->organizer = $this->crearOrganizador($this->organization, 'a@example.com');

        $this->otherOrganization = $this->crearOrganizacion('Organización B', 'active', now()->addMonths(6));
        $this->otherOrganizer = $this->crearOrganizador($this->otherOrganization, 'b@example.com');
    }

    public function test_el_estado_de_cuenta_trae_plan_vigencia_saldo_y_checkout(): void
    {
        $this->organization->update(['prepaid_balance' => 45.50, 'match_rate' => 7.5]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->getJson('/api/socialevents/v1/organizations/current')
            ->assertOk()
            ->assertJsonPath('data.organization.id', $this->organization->id)
            ->assertJsonPath('data.organization.name', 'Organización A')
            ->assertJsonPath('data.organization.status_label', 'Prueba gratuita')
            ->assertJsonPath('data.access.can_read', true)
            ->assertJsonPath('data.access.can_write', false)
            ->assertJsonPath('data.access.reason', 'trial_expired')
            ->assertJsonPath('data.balance', 45.5)
            ->assertJsonPath('data.match_rate', 7.5)
            ->assertJsonPath('data.checkout.minimum_amount', 10);

        $this->assertIsBool(
            $response->json('data.checkout.available'),
            'La app necesita saber si el checkout web puede cobrar antes de ofrecerlo.'
        );
    }

    public function test_una_cuenta_vencida_conserva_el_acceso_a_pagos_para_reactivarse(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->getJson('/api/socialevents/v1/organizations/current')
            ->assertOk();

        $this->assertStringContainsString(
            'organizations/topup',
            $response->json('data.checkout.url'),
            'El botón de recarga debe apuntar al checkout web.'
        );
    }

    public function test_una_organizacion_suspendida_tambien_puede_ver_su_estado_y_pagar(): void
    {
        $this->organization->update(['status' => 'suspended']);

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson('/api/socialevents/v1/organizations/current')
            ->assertOk()
            ->assertJsonPath('data.access.status', 'suspended')
            ->assertJsonPath('data.access.can_read', false)
            ->assertJsonPath('data.access.can_write', false)
            ->assertJsonPath('data.access.reason', 'suspended');
    }

    public function test_una_cuenta_sin_organizacion_recibe_org_required(): void
    {
        $sinOrganizacion = User::factory()->create();

        $this->actingAs($sinOrganizacion, 'sanctum')
            ->getJson('/api/socialevents/v1/organizations/current')
            ->assertForbidden()
            ->assertJsonPath('code', 'ORG_REQUIRED');

        $this->actingAs($sinOrganizacion, 'sanctum')
            ->postJson('/api/socialevents/v1/organizations/current/topup-intent')
            ->assertForbidden()
            ->assertJsonPath('code', 'ORG_REQUIRED');
    }

    public function test_los_movimientos_solo_incluyen_la_organizacion_autenticada(): void
    {
        $this->registrarRecarga($this->organization, 50, 'credited', 'card');
        $this->registrarRecarga($this->organization, 20, 'rejected', 'yape');
        $this->registrarRecarga($this->otherOrganization, 99, 'credited', 'card');

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->getJson('/api/socialevents/v1/organizations/current/payments')
            ->assertOk();

        $movimientos = $response->json('data');

        $this->assertCount(2, $movimientos, 'No debe aparecer la recarga de otra organización.');
        $this->assertEqualsCanonicalizing([50.0, 20.0], array_column($movimientos, 'amount'));
        $this->assertEqualsCanonicalizing(
            ['Acreditado', 'Rechazado'],
            array_column($movimientos, 'status_label')
        );
        $this->assertSame(50.0, (float) $response->json('summary.credited_total'));
    }

    public function test_el_checkout_precarga_el_monto_y_marca_el_origen_app(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/socialevents/v1/organizations/current/topup-intent', ['amount' => 75])
            ->assertOk();

        $url = $response->json('data.url');

        $this->assertStringContainsString('organizations/topup', $url);
        $this->assertStringContainsString('amount=75', $url);
        $this->assertStringContainsString('origen=app', $url);
        $this->assertSame(10, $response->json('data.minimum_amount'));
    }

    public function test_el_checkout_rechaza_montos_menores_al_minimo(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/socialevents/v1/organizations/current/topup-intent', ['amount' => 5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    private function crearOrganizacion(string $nombre, string $estado, $finVigencia): SocialeventOrganization
    {
        return SocialeventOrganization::create([
            'name' => $nombre,
            'status' => $estado,
            'trial_started_at' => now()->subDays(30),
            'trial_days' => 30,
            'trial_ends_at' => $estado === 'trial' ? $finVigencia : now()->addDays(30),
            'access_until' => $estado === 'active' ? $finVigencia : null,
            'read_permissions' => json_encode(['teams', 'fixtures', 'reports', 'minutes']),
            'match_rate' => 5,
            'prepaid_balance' => 0,
        ]);
    }

    private function crearOrganizador(SocialeventOrganization $organization, string $email): User
    {
        $role = Role::firstOrCreate(['name' => 'sports_organizer', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'email' => $email,
            'socialevent_organization_id' => $organization->id,
            'socialevent_organization_role' => 'owner',
        ]);
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    private function registrarRecarga(
        SocialeventOrganization $organization,
        float $monto,
        string $estado,
        string $metodo
    ): void {
        DB::table('socialevent_organization_topups')->insert([
            'organization_id' => $organization->id,
            'user_id' => null,
            'external_reference' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'payment_method' => $metodo,
            'status' => $estado,
            'amount' => $monto,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
