<?php

namespace Tests\Feature\Socialevents;

use App\Models\LocalSale;
use App\Models\Serie;
use App\Models\SaleDocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Modules\Socialevents\Services\OrganizationDocumentService;
use Modules\Socialevents\Services\OrganizationTeamService;
use Modules\Socialevents\Services\OrganizationTopUpService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Plan de usuarios adicionales de una organización.
 *
 * Fija las tres reglas del plan: una compra aprobada suma espacios una sola vez,
 * un usuario operativo solo recibe las tareas elegidas y una compra acreditada
 * puede generar su comprobante sin volver a mover el saldo.
 */
class OrganizationSeatsAndDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = SocialeventOrganization::create([
            'name' => 'Academia de prueba',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'match_rate' => 5,
            'prepaid_balance' => 0,
            'seat_limit' => 1,
        ]);

        $this->owner = User::factory()->create([
            'socialevent_organization_id' => $this->organization->id,
            'socialevent_organization_role' => 'owner',
        ]);
        DB::table('socialevent_organization_users')->insert([
            'organization_id' => $this->organization->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'profile_key' => 'full',
            'capabilities' => json_encode(['full']),
            'status' => 'active',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['even_organizaciones_admin', 'even_dashboard', 'even_ediciones_listado', 'even_evento_nuevo'] as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function crearCompra(int $quantity = 2, float $total = 2.0, string $status = 'processing'): int
    {
        return DB::table('socialevent_organization_seat_purchases')->insertGetId([
            'organization_id' => $this->organization->id,
            'purchased_by' => $this->owner->id,
            'external_reference' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'payment_method' => 'card',
            'status' => $status,
            'quantity' => $quantity,
            'unit_price' => 1,
            'total' => $total,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_una_compra_aprobada_suma_espacios_una_sola_vez(): void
    {
        $purchaseId = $this->crearCompra();
        $reference = DB::table('socialevent_organization_seat_purchases')->where('id', $purchaseId)->value('external_reference');

        $payment = [
            'id' => '900001',
            'status' => 'approved',
            'currency_id' => 'PEN',
            'transaction_amount' => 2.0,
            'payment_method_id' => 'visa',
            'payment_type_id' => 'credit_card',
            'external_reference' => $reference,
        ];

        $service = app(OrganizationTopUpService::class);
        $this->assertTrue($service->creditApprovedPayment($payment));
        $this->assertSame(3, (int) DB::table('socialevent_organizations')->where('id', $this->organization->id)->value('seat_limit'));
        $this->assertSame('credited', DB::table('socialevent_organization_seat_purchases')->where('id', $purchaseId)->value('status'));

        $this->assertFalse($service->creditApprovedPayment($payment), 'El mismo pago no puede acreditarse dos veces.');
        $this->assertSame(3, (int) DB::table('socialevent_organizations')->where('id', $this->organization->id)->value('seat_limit'));
    }

    public function test_un_operador_solo_recibe_las_tareas_elegidas_y_respeta_el_limite(): void
    {
        DB::table('socialevent_organizations')->where('id', $this->organization->id)->update(['seat_limit' => 2]);
        $team = app(OrganizationTeamService::class);

        $operator = $team->createMember($this->owner, $this->organization->id, [
            'name' => 'Operador de resultados',
            'email' => 'resultados@example.com',
            'password' => 'clave123',
            'numero_identidad' => '70000001',
            'capabilities' => ['results'],
        ]);

        $this->assertTrue($operator->hasRole('sports_operator'));
        $this->assertTrue($operator->hasPermissionTo('even_ediciones_partido_resultado'));
        $this->assertFalse($operator->hasPermissionTo('even_evento_nuevo'), 'Las tareas no elegidas no deben concederse.');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $team->createMember($this->owner, $this->organization->id, [
            'name' => 'Operador sin espacio',
            'email' => 'sin-espacio@example.com',
            'password' => 'clave123',
            'numero_identidad' => '70000002',
            'capabilities' => ['reports'],
        ]);
    }

    public function test_una_compra_acreditada_genera_su_comprobante(): void
    {
        $purchaseId = $this->crearCompra(2, 2.0, 'credited');
        $type = SaleDocumentType::query()->where('sunat_id', '80')->firstOrFail();

        $document = app(OrganizationDocumentService::class)->request(
            $this->owner,
            $this->organization->id,
            'seat_purchase',
            $purchaseId,
            $type->id,
            ['business_name' => 'Academia de prueba', 'document_number' => '70000001'],
            ['email'],
        );

        $this->assertSame('requested', $document->status);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $local = LocalSale::create([
            'description' => 'Local de prueba',
            'address' => 'Av. Siempre Viva 123',
        ]);
        $admin->forceFill(['local_id' => $local->id])->save();
        Serie::create([
            'document_type_id' => $type->id,
            'description' => 'NV01',
            'number' => 1,
            'user_id' => $admin->id,
            'local_id' => $admin->local_id,
        ]);

        $issued = app(OrganizationDocumentService::class)->issue($admin, $document->id, ['channels' => []]);

        $this->assertSame('issued', $issued->status);
        $this->assertNotNull($issued->sale_document_id);
        $this->assertSame('80', $issued->saleDocument->invoice_type_doc);
        $this->assertSame(2.0, (float) $issued->saleDocument->overall_total);
    }
}
