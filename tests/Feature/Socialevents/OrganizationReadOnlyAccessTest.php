<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Modules\Socialevents\Services\SportsOrganizationService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Modo solo lectura al vencer la prueba del organizador.
 *
 * El caso que fija esta prueba: vencida la prueba, el organizador conserva la
 * lectura y la vista de pagos, pero el servidor rechaza cualquier registro o
 * edición. Antes esto no se podía comprobar porque vencer la prueba no era
 * posible desde la pantalla de cuentas: "Acceso hasta" se ignora mientras la
 * prueba siga vigente y "Días de prueba" siempre recalculaba el fin desde hoy.
 */
class OrganizationReadOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $user;

    private EventEdition $edition;

    private EvenEvent $event;

    protected function setUp(): void
    {
        parent::setUp();

        // Modo deportivo activo: PW00001 = 2 (torneos).
        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        // Organización con la prueba vencida ayer, con lectura concedida.
        $this->organization = SocialeventOrganization::create([
            'name' => 'Organización vencida',
            'status' => 'trial',
            'trial_started_at' => now()->subDays(31),
            'trial_days' => 30,
            'trial_ends_at' => now()->subDay(),
            'read_permissions' => json_encode(['teams', 'fixtures', 'reports', 'minutes']),
            'match_rate' => 5,
            'prepaid_balance' => 100,
        ]);

        $role = Role::firstOrCreate(['name' => 'sports_organizer', 'guard_name' => 'web']);
        foreach (['even_dashboard', 'even_ediciones_listado', 'even_ediciones_fixtures', 'even_ediciones_fixtures_nuevo', 'even_ediciones_equipos'] as $nombre) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']));
        }

        $this->user = User::factory()->create([
            'socialevent_organization_id' => $this->organization->id,
            'socialevent_organization_role' => 'owner',
        ]);
        $this->user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->event = EvenEvent::create(['title' => 'Torneo vencido', 'organization_id' => $this->organization->id]);
        $this->edition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => 2026,
            'name' => 'Edición vencida',
            'start_date' => '2026-01-10',
        ]);
    }

    private function administrador(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'even_organizaciones_admin', 'guard_name' => 'web']));

        $admin = User::factory()->create();
        $admin->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    public function test_la_prueba_vencida_conserva_la_lectura_del_panel_y_de_los_listados(): void
    {
        $this->actingAs($this->user)->get('/socialevents/dashboard')->assertOk();
        $this->actingAs($this->user)->get('/socialevents/editions')->assertOk();
    }

    public function test_la_prueba_vencida_bloquea_los_registros_y_los_cambios(): void
    {
        // Registrar una fecha desde la pantalla de partidos.
        $this->actingAs($this->user)
            ->put("/socialevents/editions/{$this->edition->id}/fixtures/generate", [])
            ->assertForbidden();

        // Editar el evento de la organización.
        $this->actingAs($this->user)
            ->post('/socialevents/events/update', ['id' => $this->event->id, 'title' => 'Título cambiado'])
            ->assertForbidden();

        $this->assertDatabaseCount('event_edition_matches', 0);
        $this->assertDatabaseHas('even_events', ['id' => $this->event->id, 'title' => 'Torneo vencido']);
    }

    public function test_la_availability_explica_que_el_acceso_esta_vencido(): void
    {
        $this->actingAs($this->user)
            ->getJson("/socialevents/editions/{$this->edition->id}/fixtures/create/availability")
            ->assertOk()
            ->assertJsonPath('can_create', false)
            ->assertJsonPath('can_write', false)
            ->assertJsonPath('reason', fn ($reason) => str_contains((string) $reason, 'vencido'));
    }

    public function test_con_el_acceso_vencido_igual_puede_entrar_a_pagar(): void
    {
        $this->actingAs($this->user)
            ->get('/socialevents/organizations/topup')
            ->assertOk();
    }

    public function test_la_api_movil_respeta_el_modo_solo_lectura(): void
    {
        // Lectura: el listado de partidos sigue disponible aunque vaya por PUT.
        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/socialevents/v1/admin/matches/edition/{$this->edition->id}")
            ->assertOk();

        // Escritura: el middleware la corta antes de llegar al controlador.
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/socialevents/v1/admin/teams', ['name' => 'Equipo nuevo'])
            ->assertForbidden();
    }

    public function test_el_administrador_vencia_la_prueba_indicando_una_fecha(): void
    {
        // La organización empieza con la prueba vigente treinta días más.
        DB::table('socialevent_organizations')->where('id', $this->organization->id)
            ->update(['trial_ends_at' => now()->addDays(30)]);
        $this->assertTrue(app(SportsOrganizationService::class)->canWrite($this->user));

        $ayer = now()->subDay()->toDateString();

        $this->actingAs($this->administrador())
            ->put("/socialevents/organizations/{$this->organization->id}", [
                'match_rate' => 5,
                'status' => 'trial',
                'trial_days' => 30,
                'trial_ends_at' => $ayer,
                'reason' => 'Vencimiento de prueba para revisar el modo solo lectura.',
            ])
            ->assertRedirect();

        $this->assertSame($ayer, $this->organization->fresh()->trial_ends_at->toDateString());
        $this->assertFalse(app(SportsOrganizationService::class)->canWrite($this->user), 'Con el fin de prueba en el pasado la organización queda en solo lectura.');
    }
}
