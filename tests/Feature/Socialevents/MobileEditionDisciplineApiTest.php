<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fase 5B móvil: tarjetas pendientes, suspensiones, exclusiones y premios.
 *
 * Las reglas son las mismas de la web: jugadores inscritos, partidos con
 * resultado y pertenencia a la organización del token.
 */
class MobileEditionDisciplineApiTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $organizer;

    private EvenEvent $event;

    private EventEdition $edition;

    private EventTeam $team;

    private Person $player;

    private Person $otherPlayer;

    private EventEditionMatch $match;

    private EventEdition $otherEdition;

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = $this->crearOrganizacion('Organización A');
        $this->organizer = $this->crearOrganizador($this->organization, 'disciplina-a@example.com');

        $this->event = EvenEvent::create([
            'title' => 'Torneo A',
            'organization_id' => $this->organization->id,
            'status' => 'PE',
        ]);

        $this->edition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => '2026',
            'name' => 'Edición 2026',
            'start_date' => '2026-01-10',
            'competition_format' => 'round_robin',
            'yellow_price' => 10,
            'direct_red_price' => 25,
            'double_yellow_price' => 15,
            'status' => 'pending',
        ]);

        $this->team = EventTeam::create([
            'organization_id' => $this->organization->id,
            'name' => 'Equipo A',
            'short_name' => 'EA',
            'status' => true,
        ]);

        \Modules\Socialevents\Entities\EventEditionTeam::create([
            'edition_id' => $this->edition->id,
            'team_id' => $this->team->id,
        ]);

        $this->player = Person::create([
            'full_name' => 'Carlos Torres',
            'document_type_id' => 1,
            'number' => '70000001',
            'gender' => 'M',
            'status' => '1',
        ]);

        $this->otherPlayer = Person::create([
            'full_name' => 'Diego Ramos',
            'document_type_id' => 1,
            'number' => '70000002',
            'gender' => 'M',
            'status' => '1',
        ]);

        foreach ([$this->player, $this->otherPlayer] as $person) {
            EventEditionTeamPlayer::create([
                'edition_id' => $this->edition->id,
                'team_id' => $this->team->id,
                'person_id' => $person->id,
                'jersey_number' => '7',
                'position' => 'Delantero',
                'role_in_team' => 'Ninguno',
                'registration_date' => '2026-01-11',
            ]);
        }

        $this->match = EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'team_h_id' => $this->team->id,
            'team_a_id' => $this->team->id,
            'phase' => 'league',
            'round_number' => 1,
            'match_date' => '2026-01-15 16:00:00',
            'score_h' => 2,
            'score_a' => 1,
            'status' => 'finished',
        ]);

        // Edición de otra organización para comprobar el aislamiento.
        $otherOrganization = $this->crearOrganizacion('Organización B');
        $otherEvent = EvenEvent::create([
            'title' => 'Torneo B',
            'organization_id' => $otherOrganization->id,
            'status' => 'PE',
        ]);

        $this->otherEdition = EventEdition::create([
            'event_id' => $otherEvent->id,
            'year' => '2026',
            'name' => 'Edición B',
            'start_date' => '2026-02-10',
            'competition_format' => 'round_robin',
            'status' => 'pending',
        ]);
    }

    public function test_lista_las_tarjetas_pendientes_con_su_deuda(): void
    {
        $this->registrarTarjeta('yellow', 10);
        $this->registrarTarjeta('red', 25);

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('sanctions'))
            ->assertOk()
            ->assertJsonPath('data.total_pending', 35)
            ->assertJsonCount(1, 'data.players')
            ->assertJsonPath('data.players.0.player_id', $this->player->id)
            ->assertJsonPath('data.players.0.team_name', 'Equipo A')
            ->assertJsonCount(2, 'data.players.0.sanctions')
            ->assertJsonPath('data.players.0.sanctions.0.type_label', 'Amarilla')
            ->assertJsonCount(1, 'data.played_matches')
            ->assertJsonCount(2, 'data.all_players');
    }

    public function test_la_doble_amarilla_absorbe_las_amarillas_y_usa_el_precio_de_la_edicion(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('sanctions'), [
                'match_id' => $this->match->id,
                'player_id' => $this->player->id,
                'type' => 'double_yellow',
                'minute' => '78',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount_fine', 15);

        // Expulsión + dos amarillas del historial, sin importe.
        $this->assertSame(3, EventEditionMatchSanction::where('player_id', $this->player->id)->count());
        $this->assertSame(
            2,
            EventEditionMatchSanction::where('player_id', $this->player->id)
                ->where('type', 'yellow')
                ->where('amount_fine', 0)
                ->count()
        );

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('sanctions'))
            ->assertOk()
            ->assertJsonPath('data.total_pending', 15)
            ->assertJsonCount(1, 'data.players.0.sanctions')
            ->assertJsonPath('data.players.0.sanctions.0.type', 'double_yellow');

        // Una tarjeta suelta después suma su propio importe.
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('sanctions'), [
                'match_id' => $this->match->id,
                'player_id' => $this->otherPlayer->id,
                'type' => 'yellow',
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount_fine', 10);
    }

    public function test_rechaza_tarjetas_de_un_partido_ajeno_o_de_un_jugador_no_inscrito(): void
    {
        $otherMatch = EventEditionMatch::create([
            'edition_id' => $this->otherEdition->id,
            'round_number' => 1,
            'score_h' => 1,
            'score_a' => 0,
            'status' => 'finished',
        ]);

        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('sanctions'), [
                'match_id' => $otherMatch->id,
                'player_id' => $this->player->id,
                'type' => 'yellow',
            ])
            ->assertStatus(422);

        $sinEquipo = Person::create([
            'full_name' => 'Jugador Libre',
            'document_type_id' => 1,
            'number' => '70000003',
            'status' => '1',
        ]);

        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('sanctions'), [
                'match_id' => $this->match->id,
                'player_id' => $sinEquipo->id,
                'type' => 'yellow',
            ])
            ->assertStatus(422);

        $this->assertSame(0, EventEditionMatchSanction::count());
    }

    public function test_registra_y_levanta_una_suspension(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('suspensions'), [
                'player_id' => $this->player->id,
                'type' => 'matches',
                'matches_count' => 2,
                'reason' => 'Acumulación de tarjetas',
                'suspended_by' => 'Comisión de disciplina',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type_label', 'Por 2 partido(s)');

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('suspensions'))
            ->assertOk()
            ->assertJsonCount(1, 'data.suspensions')
            ->assertJsonPath('data.suspensions.0.player_name', 'Carlos Torres')
            ->assertJsonPath('data.suspensions.0.is_active', true)
            // El jugador suspendido no vuelve a ofrecerse en el selector.
            ->assertJsonCount(1, 'data.players');

        $suspensionId = $response->json('data.suspensions.0.id');

        $this->actingAs($this->organizer, 'sanctum')
            ->deleteJson($this->url("suspensions/{$suspensionId}"))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull(
            \Modules\Socialevents\Entities\EventEditionPlayerSuspension::find($suspensionId)->lifted_at
        );
    }

    public function test_registra_y_elimina_una_exclusion(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('exclusions'), [
                'player_id' => $this->player->id,
                'reason' => 'Agresión al árbitro',
                'excluded_by' => 'Comisión de disciplina',
            ])
            ->assertCreated();

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('exclusions'))
            ->assertOk()
            ->assertJsonCount(1, 'data.exclusions')
            ->assertJsonPath('data.exclusions.0.player_name', 'Carlos Torres')
            ->assertJsonCount(1, 'data.players');

        $exclusionId = $response->json('data.exclusions.0.id');

        $this->actingAs($this->organizer, 'sanctum')
            ->deleteJson($this->url("exclusions/{$exclusionId}"))
            ->assertOk();

        $this->assertDatabaseCount('event_edition_player_exclusions', 0);
    }

    public function test_guarda_los_premios_con_las_claves_de_la_landing(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->putJson($this->url('prizes'), [
                'prizes' => [
                    ['position' => 1, 'money' => 1000, 'gift' => 'Copa'],
                    ['position' => 3, 'money' => 250, 'gift' => null],
                    ['position' => 2, 'money' => null, 'gift' => ''],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.prize_details.first.money', 1000)
            ->assertJsonPath('data.prize_details.first.gift', 'Copa')
            ->assertJsonPath('data.prize_details.third.money', 250)
            ->assertJsonMissingPath('data.prize_details.second');

        $stored = EventEdition::find($this->edition->id)->prize_details;
        $this->assertSame(1000, $stored['first']['money']);
        $this->assertSame('Copa', $stored['first']['gift']);
        $this->assertArrayNotHasKey('fourth', $stored);
    }

    public function test_no_puede_tocar_la_disciplina_de_otra_edicion(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/socialevents/v1/admin/editions/{$this->otherEdition->id}/sanctions")
            ->assertNotFound();

        $this->actingAs($this->organizer, 'sanctum')
            ->postJson("/api/socialevents/v1/admin/editions/{$this->otherEdition->id}/suspensions", [
                'player_id' => $this->player->id,
                'type' => 'definitive',
                'reason' => 'No debería',
            ])
            ->assertNotFound();

        $this->actingAs($this->organizer, 'sanctum')
            ->putJson("/api/socialevents/v1/admin/editions/{$this->otherEdition->id}/prizes", [
                'prizes' => [['position' => 1, 'money' => 500]],
            ])
            ->assertNotFound();
    }

    public function test_un_acceso_vencido_no_registra_tarjetas(): void
    {
        $this->organization->update([
            'status' => 'trial',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('sanctions'), [
                'match_id' => $this->match->id,
                'player_id' => $this->player->id,
                'type' => 'yellow',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'ORG_READ_ONLY');

        $this->assertSame(0, EventEditionMatchSanction::count());
    }

    private function registrarTarjeta(string $type, float $amount): void
    {
        EventEditionMatchSanction::create([
            'match_id' => $this->match->id,
            'player_id' => $this->player->id,
            'type' => $type,
            'minute' => '30',
            'amount_fine' => $amount,
        ]);
    }

    private function url(string $path): string
    {
        return "/api/socialevents/v1/admin/editions/{$this->edition->id}/{$path}";
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
