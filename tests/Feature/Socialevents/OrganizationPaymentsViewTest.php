<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Modules\Socialevents\Services\OrganizationTopUpService;
use Tests\TestCase;

/**
 * Vista "Mis pagos" del organizador (even_organizations.topup.index).
 *
 * El caso que fija esta prueba: el organizador debe poder entrar a pagar por
 * adelantado, antes de que el acceso venza y sin depender del aviso que solo
 * aparece al crear un partido. Por eso la vista muestra su vigencia, su saldo y
 * el historial de recargas, y sigue abierta con el acceso vencido.
 */
class OrganizationPaymentsViewTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Modo deportivo activo: PW00001 = 2 (torneos).
        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = SocialeventOrganization::create([
            'name' => 'Organización de prueba',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(12),
            // Permisos de lectura concedidos al crear la organización: con el
            // acceso vencido el organizador conserva la lectura y la vista de pagos.
            'read_permissions' => json_encode(['teams', 'fixtures', 'reports', 'minutes']),
            'match_rate' => 5,
            'prepaid_balance' => 25,
        ]);

        $this->user = User::factory()->create([
            'socialevent_organization_id' => $this->organization->id,
            'socialevent_organization_role' => 'owner',
        ]);
    }

    private function crearRecarga(float $amount, string $status = 'processing', string $method = 'card'): int
    {
        return DB::table('socialevent_organization_topups')->insertGetId([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'external_reference' => (string) \Illuminate\Support\Str::uuid(),
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'payment_method' => $method,
            'status' => $status,
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_el_organizador_ve_su_vigencia_saldo_e_historial(): void
    {
        $this->crearRecarga(30);
        $this->crearRecarga(20, 'credited');

        $this->actingAs($this->user)
            ->get('/socialevents/organizations/topup')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                // El componente vive en el namespace del módulo: el buscador de
                // vistas de Inertia no resuelve ese prefijo, así que solo se
                // comprueba el nombre.
                ->component('Socialevents::Organizations/TopUp', false)
                // Los props llegan serializados a JSON (25.0 viaja como 25), así
                // que los montos se comparan por valor numérico.
                ->where('balance', fn ($value) => (float) $value === 25.0)
                ->where('organization.name', 'Organización de prueba')
                ->where('access.status', 'trial')
                ->where('access.label', 'Prueba gratuita')
                ->where('access.expired', false)
                ->where('access.days_left', 12)
                ->where('access.sponsored', false)
                ->has('payments', 2)
            );
    }

    public function test_el_organizador_con_el_acceso_vencido_igual_entra_a_pagar(): void
    {
        DB::table('socialevent_organizations')->where('id', $this->organization->id)
            ->update(['trial_ends_at' => now()->subDays(3)]);

        $this->actingAs($this->user)
            ->get('/socialevents/organizations/topup')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('access.expired', true)
                ->where('access.days_left', -3)
                ->where('access.until', now()->subDays(3)->toDateString())
            );
    }

    public function test_la_cuenta_bloqueada_puede_abrir_pagos_y_ver_su_estado(): void
    {
        DB::table('socialevent_organizations')->where('id', $this->organization->id)
            ->update(['status' => 'suspended']);

        $this->actingAs($this->user)
            ->get('/socialevents/organizations/topup')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('access.status', 'suspended')
                ->where('access.label', 'Bloqueado')
                ->where('access.expired', true)
            );
    }

    public function test_la_cuenta_bloqueada_puede_iniciar_el_flujo_de_pago(): void
    {
        DB::table('socialevent_organizations')->where('id', $this->organization->id)
            ->update(['status' => 'suspended']);
        config(['services.mercadopago.token' => null, 'services.mercadopago.key' => null]);

        $this->actingAs($this->user)
            ->postJson('/socialevents/organizations/topup/yape', [
                'amount' => 10,
                'token' => 'token-de-prueba',
                'phone_number' => '999999999',
                'payer' => ['email' => $this->user->email],
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', 'El pago en línea aún no está configurado para esta organización.');
    }

    public function test_la_recarga_acreditada_aparece_en_el_historial(): void
    {
        $topUpId = $this->crearRecarga(40, 'processing', 'yape');
        $referencia = DB::table('socialevent_organization_topups')->where('id', $topUpId)->value('external_reference');

        $acreditado = app(OrganizationTopUpService::class)->creditApprovedPayment([
            'id' => '987654321',
            'status' => 'approved',
            'currency_id' => 'PEN',
            'transaction_amount' => 40.0,
            'payment_method_id' => 'yape',
            'external_reference' => $referencia,
        ]);

        $this->assertTrue($acreditado, 'El pago aprobado debe acreditar el saldo de la organización.');

        $this->actingAs($this->user)
            ->get('/socialevents/organizations/topup')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                // 25 de saldo inicial más los 40 acreditados.
                ->where('balance', fn ($value) => (float) $value === 65.0)
                ->where('payments.0.status', 'credited')
                ->where('payments.0.status_label', 'Acreditado')
                ->where('payments.0.method', 'Yape')
                ->where('payments.0.amount', fn ($value) => (float) $value === 40.0)
            );
    }

    public function test_un_usuario_sin_organizacion_no_entra_a_la_vista_de_pagos(): void
    {
        $sinOrganizacion = User::factory()->create();

        $this->actingAs($sinOrganizacion)
            ->get('/socialevents/organizations/topup')
            ->assertStatus(403);
    }
}
