<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fase 5A móvil: el organizador crea y edita sus torneos y ediciones, y genera
 * el fixture con sus propios equipos.
 *
 * Las rutas viven en el grupo administrativo: la pertenencia sale del token y un
 * acceso vencido responde ORG_READ_ONLY.
 */
class MobileTournamentAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $organizer;

    private EvenEvent $event;

    private EventEdition $edition;

    private array $teams = [];

    private SocialeventOrganization $otherOrganization;

    private User $otherOrganizer;

    private EvenEvent $otherEvent;

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = $this->crearOrganizacion('Organización A');
        $this->organizer = $this->crearOrganizador($this->organization, 'admin-a@example.com');

        $this->event = EvenEvent::create([
            'title' => 'Torneo A',
            'description' => 'Torneo de la organización A',
            'organization_id' => $this->organization->id,
            'date_start' => '2026-01-01',
            'date_end' => '2026-01-31',
            'status' => 'PE',
        ]);

        $this->edition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => '2026',
            'name' => 'Edición 2026',
            'start_date' => '2026-01-10',
            'end_date' => '2026-02-10',
            'competition_format' => 'round_robin',
            'score_points_win' => 3,
            'score_points_draw' => 1,
            'score_points_loss' => 0,
            'min_players_per_team' => 5,
            'max_players_per_team' => 15,
            'status' => 'pending',
        ]);

        foreach (['Equipo A', 'Equipo B', 'Equipo C'] as $index => $name) {
            $team = EventTeam::create([
                'organization_id' => $this->organization->id,
                'name' => $name,
                'short_name' => 'A'.($index + 1),
                'status' => true,
            ]);
            $this->teams[] = $team;

            DB::table('event_edition_teams')->insert([
                'edition_id' => $this->edition->id,
                'team_id' => $team->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->otherOrganization = $this->crearOrganizacion('Organización B');
        $this->otherOrganizer = $this->crearOrganizador($this->otherOrganization, 'admin-b@example.com');

        $this->otherEvent = EvenEvent::create([
            'title' => 'Torneo B',
            'description' => 'Torneo ajeno',
            'organization_id' => $this->otherOrganization->id,
            'status' => 'PE',
        ]);
    }

    public function test_crea_un_torneo_en_su_organizacion(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/socialevents/v1/admin/events', [
                'title' => 'Copa de Verano',
                'description' => 'Torneo de prueba',
                'date_start' => '2026-03-01',
                'date_end' => '2026-03-10',
                'tickets_quantity' => 200,
                'broadcast' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Copa de Verano');

        $eventId = $response->json('data.id');

        $this->assertDatabaseHas('even_events', [
            'id' => $eventId,
            'organization_id' => $this->organization->id,
            'number_days' => 10,
            'status' => 'PE',
        ]);
    }

    public function test_rechaza_un_torneo_con_fechas_incoherentes(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/socialevents/v1/admin/events', [
                'title' => 'Fechas mal',
                'description' => 'Prueba',
                'date_start' => '2026-03-10',
                'date_end' => '2026-03-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_end');
    }

    public function test_no_puede_editar_el_torneo_de_otra_organizacion(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->putJson("/api/socialevents/v1/admin/events/{$this->otherEvent->id}", [
                'title' => 'Intento ajeno',
                'description' => 'No debería',
                'date_start' => '2026-03-01',
                'date_end' => '2026-03-02',
            ])
            ->assertNotFound();

        $this->assertSame('Torneo B', $this->otherEvent->fresh()->title);
    }

    public function test_crea_una_edicion_en_su_torneo(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/socialevents/v1/admin/editions', [
                'event_id' => $this->event->id,
                'year' => '2027',
                'name' => 'Apertura 2027',
                'start_date' => '2027-01-05',
                'end_date' => '2027-03-05',
                'competition_format' => 'round_robin',
                'score_points_win' => 3,
                'score_points_draw' => 1,
                'score_points_loss' => 0,
                'inscription_fee' => 150,
                'min_players_per_team' => 6,
                'max_players_per_team' => 18,
                'landing_published' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.landing_published', true)
            ->assertJsonPath('data.mobile_enabled', true);

        $this->assertDatabaseHas('event_editions', [
            'id' => $response->json('data.id'),
            'event_id' => $this->event->id,
            'name' => 'Apertura 2027',
            'public_slug' => 'apertura-2027-2027',
        ]);
    }

    public function test_no_puede_crear_una_edicion_en_un_torneo_ajeno(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/socialevents/v1/admin/editions', [
                'event_id' => $this->otherEvent->id,
                'year' => '2027',
                'name' => 'Intento ajeno',
                'start_date' => '2027-01-05',
                'competition_format' => 'round_robin',
                'score_points_win' => 3,
                'score_points_draw' => 1,
                'score_points_loss' => 0,
                'min_players_per_team' => 5,
                'max_players_per_team' => 15,
            ])
            ->assertNotFound();
    }

    public function test_actualiza_su_edicion_sin_moverla_de_torneo(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->putJson("/api/socialevents/v1/admin/editions/{$this->edition->id}", [
                'event_id' => $this->event->id,
                'year' => '2026',
                'name' => 'Edición renombrada',
                'start_date' => '2026-01-12',
                'competition_format' => 'single_elimination',
                'score_points_win' => 3,
                'score_points_draw' => 1,
                'score_points_loss' => 0,
                'min_players_per_team' => 5,
                'max_players_per_team' => 15,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Edición renombrada')
            ->assertJsonPath('data.competition_format', 'single_elimination');

        $this->assertDatabaseHas('event_editions', [
            'id' => $this->edition->id,
            'name' => 'Edición renombrada',
        ]);
    }

    public function test_genera_el_fixture_solo_con_equipos_inscritos(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson("/api/socialevents/v1/admin/editions/{$this->edition->id}/fixtures/generate", [
                'teams' => array_map(fn (EventTeam $team) => $team->id, $this->teams),
            ])
            ->assertOk()
            ->assertJsonPath('data.created', 3)
            ->assertJsonPath('data.total', 3);

        $this->assertSame(
            3,
            EventEditionMatch::where('edition_id', $this->edition->id)->count()
        );
    }

    public function test_no_genera_el_fixture_con_un_equipo_ajeno(): void
    {
        $foreignTeam = EventTeam::create([
            'organization_id' => $this->otherOrganization->id,
            'name' => 'Equipo de B',
            'status' => true,
        ]);

        $this->actingAs($this->organizer, 'sanctum')
            ->postJson("/api/socialevents/v1/admin/editions/{$this->edition->id}/fixtures/generate", [
                'teams' => [$this->teams[0]->id, $foreignTeam->id],
            ])
            ->assertNotFound();

        $this->assertSame(
            0,
            EventEditionMatch::where('edition_id', $this->edition->id)->count()
        );
    }

    public function test_un_acceso_vencido_no_crea_torneos(): void
    {
        $this->organization->update([
            'status' => 'trial',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/socialevents/v1/admin/events', [
                'title' => 'No permitido',
                'description' => 'Solo lectura',
                'date_start' => '2026-03-01',
                'date_end' => '2026-03-02',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'ORG_READ_ONLY');

        $this->assertDatabaseMissing('even_events', ['title' => 'No permitido']);
    }

    private function crearOrganizacion(string $nombre): SocialeventOrganization
    {
        return SocialeventOrganization::create([
            'name' => $nombre,
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'read_permissions' => json_encode(['teams', 'fixtures', 'reports', 'minutes']),
            'match_rate' => 5,
            'prepaid_balance' => 100,
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
}
