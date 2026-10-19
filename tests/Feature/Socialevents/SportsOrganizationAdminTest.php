<?php

namespace Tests\Feature\Socialevents;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tarifas y cuentas de organizaciones: solo para admin con el permiso even_organizaciones_admin.
 */
class SportsOrganizationAdminTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = SocialeventOrganization::create([
            'name' => 'Organización de prueba',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
        ]);
    }

    private function adminConPermiso(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permiso = Permission::firstOrCreate(['name' => 'even_organizaciones_admin', 'guard_name' => 'web']);
        $role->givePermissionTo($permiso);

        $user = User::factory()->create();
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    public function test_admin_sin_el_permiso_no_puede_entrar(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user)->get('/socialevents/organizations')->assertForbidden();
        $this->actingAs($user)->get('/socialevents/organizations/accounts')->assertForbidden();
    }

    public function test_admin_con_permiso_ve_tarifas_y_cuentas_y_crea_una_tarifa(): void
    {
        $admin = $this->adminConPermiso();

        $this->actingAs($admin)->get('/socialevents/organizations')->assertOk();
        $this->actingAs($admin)->get('/socialevents/organizations/accounts')->assertOk();

        $this->actingAs($admin)->post('/socialevents/organizations/rates', [
            'name' => 'Equipo y partido estándar',
            'amount' => 7.5,
            'description' => 'Tarifa base',
        ])->assertRedirect();

        $this->assertDatabaseHas('socialevent_match_rates', [
            'name' => 'Equipo y partido estándar',
            'amount' => 7.5,
            'created_by' => $admin->id,
        ]);
    }

    public function test_crea_organizacion_sin_ruc_ni_direccion_con_clave_de_ocho_caracteres(): void
    {
        $admin = $this->adminConPermiso();

        // El RUC y los datos de facturación se piden recién al cobrar, no al
        // crear la cuenta. La contraseña mínima es de 8 caracteres.
        $this->actingAs($admin)->post('/socialevents/organizations/accounts', [
            'name' => 'Academia sin RUC',
            'tipo_organizacion' => 'empresa',
            'match_rate' => 5,
            'owner' => [
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@example.com',
                'password' => 'clave123',
                'tipo_identidad' => '1',
                'numero_identidad' => '77474744',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $organization = DB::table('socialevent_organizations')->where('name', 'Academia sin RUC')->first();
        $this->assertNotNull($organization, 'La organización debe crearse sin RUC ni dirección.');
        $this->assertNull($organization->ruc);
        $this->assertNull($organization->address);

        $owner = User::query()->where('email', 'juan.perez@example.com')->first();
        $this->assertNotNull($owner);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('clave123', $owner->password));
        $this->assertSame((int) $organization->id, (int) $owner->socialevent_organization_id);
    }

    public function test_crea_organizacion_actualizando_persona_y_usuario_existentes(): void
    {
        $admin = $this->adminConPermiso();
        $person = Person::create([
            'document_type_id' => '1',
            'number' => '12345678',
            'full_name' => 'Nombre anterior',
            'short_name' => 'Nombre anterior',
            'email' => 'anterior@example.com',
            'telephone' => '900000000',
        ]);
        $user = User::factory()->create([
            'person_id' => $person->id,
            'name' => 'Nombre anterior',
            'email' => 'anterior@example.com',
            'tipo_identidad' => '1',
            'numero_identidad' => '12345678',
            'telefono' => '900000000',
        ]);
        $originalPassword = $user->password;

        $this->actingAs($admin)->get('/socialevents/organizations/accounts/owner-search?search=12345678')
            ->assertOk()
            ->assertJsonPath('people.0.id', $person->id)
            ->assertJsonPath('people.0.user_id', $user->id)
            ->assertJsonPath('people.0.available', true);

        $this->actingAs($admin)->post('/socialevents/organizations/accounts', [
            'name' => 'Organización persona existente',
            'tipo_organizacion' => 'empresa',
            'match_rate' => 5,
            'owner' => [
                'person_id' => $person->id,
                'name' => 'Nombre actualizado',
                'email' => 'actualizado@example.com',
                'password' => '',
                'tipo_identidad' => '1',
                'numero_identidad' => '12345678',
                'telefono' => '911111111',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, Person::where('number', '12345678')->count());
        $this->assertDatabaseHas('people', [
            'id' => $person->id,
            'full_name' => 'Nombre actualizado',
            'telephone' => '911111111',
        ]);
        $this->assertSame('actualizado@example.com', $person->fresh()->email);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'person_id' => $person->id,
            'name' => 'Nombre actualizado',
            'email' => 'actualizado@example.com',
            'numero_identidad' => '12345678',
        ]);
        $this->assertSame($originalPassword, $user->fresh()->password, 'La contraseña debe conservarse si no se proporciona una nueva.');
        $this->assertDatabaseHas('socialevent_organization_users', [
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->actingAs($admin)->get('/socialevents/organizations/accounts/owner-search?search=actualizado%40example.com')
            ->assertOk()
            ->assertJsonPath('people.0.id', $person->id)
            ->assertJsonPath('people.0.available', false);
    }

    public function test_el_perfil_se_actualiza_sin_exigir_ruc_ni_direccion(): void
    {
        $admin = $this->adminConPermiso();

        $this->actingAs($admin)->put("/socialevents/organizations/accounts/{$this->organization->id}/profile", [
            'name' => 'Organización renombrada',
            'tipo_organizacion' => 'empresa',
            'reason' => 'Corrección de nombre solicitada por el cliente.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('socialevent_organizations', [
            'id' => $this->organization->id,
            'name' => 'Organización renombrada',
        ]);
    }

    public function test_la_lista_incluye_los_ultimos_cobros_de_cada_organizacion(): void
    {
        $admin = $this->adminConPermiso();

        DB::table('socialevent_organization_ledger')->insert([
            'organization_id' => $this->organization->id,
            'user_id' => $admin->id,
            'entry_type' => 'balance_topup',
            'amount' => 150,
            'balance_after' => 150,
            'payment_method' => '1',
            'description' => 'Pago de acceso; comprobante NV01-000000001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // El modal de cobros muestra el historial sin salir del listado.
        $this->actingAs($admin)->get('/socialevents/organizations/accounts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('organizations.0.payments.0.amount', fn ($value) => (float) $value === 150.0)
                ->where('organizations.0.payments.0.description', 'Pago de acceso; comprobante NV01-000000001')
            );
    }

    public function test_bloquear_y_desbloquear_quedan_auditados(): void
    {
        $admin = $this->adminConPermiso();

        $this->actingAs($admin)->post("/socialevents/organizations/{$this->organization->id}/block", [
            'reason' => 'Falta de pago de mayo',
        ])->assertRedirect();

        $this->assertDatabaseHas('socialevent_organizations', ['id' => $this->organization->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('socialevent_organization_audits', [
            'organization_id' => $this->organization->id,
            'action' => 'organization_blocked',
            'reason' => 'Falta de pago de mayo',
        ]);

        // Sin fecha de acceso y sin prueba vigente, no se puede desbloquear.
        DB::table('socialevent_organizations')->where('id', $this->organization->id)->update(['trial_ends_at' => now()->subDay()]);
        $this->actingAs($admin)->post("/socialevents/organizations/{$this->organization->id}/unblock", [
            'reason' => 'Pago recibido',
        ])->assertStatus(422);

        $this->actingAs($admin)->post("/socialevents/organizations/{$this->organization->id}/unblock", [
            'reason' => 'Pago recibido',
            'access_until' => now()->addMonth()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('socialevent_organizations', ['id' => $this->organization->id, 'status' => 'active']);
        $this->assertDatabaseHas('socialevent_organization_audits', [
            'organization_id' => $this->organization->id,
            'action' => 'organization_unblocked',
            'reason' => 'Pago recibido',
        ]);
    }
}
