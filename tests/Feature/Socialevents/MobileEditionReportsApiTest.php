<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fase 5C móvil: usuarios de la organización, reportes/PDF y publicación de la
 * landing. La organización y la edición siempre salen del token y se comprueba
 * el aislamiento entre dos organizaciones.
 */
class MobileEditionReportsApiTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $organizer;

    private EvenEvent $event;

    private EventEdition $edition;

    private EventTeam $team;

    private EventEditionMatch $match;

    private EventEditionMatchReport $report;

    private EventEdition $otherEdition;

    private Person $player;

    protected function setUp(): void
    {
        parent::setUp();

        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = $this->crearOrganizacion('Organización A');
        $this->organizer = $this->crearOrganizador($this->organization, 'reportes-a@example.com');

        $this->event = EvenEvent::create([
            'title' => 'Copa Apertura',
            'organization_id' => $this->organization->id,
            'status' => 'PE',
        ]);

        $this->edition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => '2026',
            'name' => 'Copa Apertura 2026',
            'start_date' => '2026-01-10',
            'competition_format' => 'round_robin',
            'contact_phone' => '999888777',
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
            'number' => '71000001',
            'gender' => 'M',
            'status' => '1',
        ]);

        EventEditionTeamPlayer::create([
            'edition_id' => $this->edition->id,
            'team_id' => $this->team->id,
            'person_id' => $this->player->id,
            'jersey_number' => '7',
            'position' => 'Delantero',
            'role_in_team' => 'Ninguno',
            'registration_date' => '2026-01-11',
        ]);

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

        $this->report = EventEditionMatchReport::create([
            'match_id' => $this->match->id,
            'local_score' => 2,
            'visitor_score' => 1,
            'referees' => [['full_name' => 'Árbitro Uno']],
            'observations' => 'Partido sin incidentes.',
            'closed_at' => '2026-01-15 18:00:00',
            'closed_by' => $this->organizer->id,
            'protest_status' => 'none',
        ]);

        EventEditionMatchSanction::create([
            'match_id' => $this->match->id,
            'player_id' => $this->player->id,
            'type' => 'yellow',
            'minute' => '30',
            'amount_fine' => 10,
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

    public function test_lista_los_usuarios_de_la_organizacion_sin_mezclar_otras_cuentas(): void
    {
        $member = User::factory()->create([
            'name' => 'María Miembro',
            'email' => 'miembro-a@example.com',
            'socialevent_organization_id' => $this->organization->id,
            'socialevent_organization_role' => 'member',
        ]);

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson('/api/socialevents/v1/organizations/current/users')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 2)
            ->assertJsonPath('data.summary.owners', 1)
            ->assertJsonPath('data.summary.members', 1)
            ->assertJsonPath('data.seats.used', 2)
            ->assertJsonPath('data.seats.can_invite', false)
            ->assertJsonPath('data.users.0.id', $this->organizer->id)
            ->assertJsonPath('data.users.0.role_label', 'Propietario')
            ->assertJsonPath('data.users.0.is_you', true)
            ->assertJsonPath('data.users.1.id', $member->id)
            ->assertJsonPath('data.users.1.role_label', 'Miembro');

        // La otra organización no aparece en la lista.
        $this->assertDatabaseMissing('users', [
            'id' => $member->id,
            'socialevent_organization_id' => $this->organization->id + 1,
        ]);
    }

    public function test_una_cuenta_sin_organizacion_no_puede_listar_usuarios(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'sanctum')
            ->getJson('/api/socialevents/v1/organizations/current/users')
            ->assertForbidden()
            ->assertJsonPath('code', 'ORG_REQUIRED');
    }

    public function test_lista_actas_y_partidos_de_la_edicion_propia(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('reports'))
            ->assertOk()
            ->assertJsonPath('data.summary.matches_total', 1)
            ->assertJsonPath('data.summary.reports_total', 1)
            ->assertJsonPath('data.summary.matches_without_report', 0)
            ->assertJsonPath('data.reports.0.id', $this->report->id)
            ->assertJsonPath('data.reports.0.match_label', 'Equipo A vs Equipo A')
            ->assertJsonPath('data.reports.0.status_label', 'Cerrada')
            ->assertJsonPath('data.reports.0.has_protest', false)
            ->assertJsonPath('data.matches.0.id', $this->match->id)
            ->assertJsonPath('data.matches.0.has_report', true);
    }

    public function test_no_expone_reportes_de_una_edicion_ajena(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/socialevents/v1/admin/editions/{$this->otherEdition->id}/reports")
            ->assertNotFound();
    }

    public function test_genera_una_url_firmada_para_el_acta_y_la_entrega_en_pdf(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('reports/pdf'), [
                'type' => 'acta',
                'report_id' => $this->report->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.type', 'acta')
            ->assertJsonPath('data.expires_in', 1800);

        $url = $response->json('data.url');
        $this->assertStringContainsString('signature=', $url);

        $pdf = $this->get($url);
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));
    }

    public function test_la_url_del_pdf_sin_firma_es_rechazada(): void
    {
        $this->get("/api/socialevents/v1/reports/pdf/{$this->edition->id}/acta?report={$this->report->id}")
            ->assertForbidden();
    }

    public function test_genera_planilla_y_lista_de_sanciones(): void
    {
        $planilla = $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('reports/pdf'), [
                'type' => 'planilla',
                'match_id' => $this->match->id,
            ])
            ->assertOk();

        $this->get($planilla->json('data.url'))->assertOk();

        $sanciones = $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('reports/pdf'), ['type' => 'sanciones'])
            ->assertOk();

        $this->get($sanciones->json('data.url'))->assertOk();

        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('reports/pdf'), ['type' => 'otro'])
            ->assertUnprocessable();
    }

    public function test_no_permite_pedir_el_pdf_de_un_acta_de_otra_edicion(): void
    {
        $otherMatch = EventEditionMatch::create([
            'edition_id' => $this->otherEdition->id,
            'round_number' => 1,
            'score_h' => 1,
            'score_a' => 0,
            'status' => 'finished',
        ]);

        $otherReport = EventEditionMatchReport::create([
            'match_id' => $otherMatch->id,
            'local_score' => 1,
            'visitor_score' => 0,
            'closed_at' => '2026-02-15 18:00:00',
            'closed_by' => $this->organizer->id,
        ]);

        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('reports/pdf'), [
                'type' => 'acta',
                'report_id' => $otherReport->id,
            ])
            ->assertNotFound();
    }

    public function test_consulta_el_estado_de_publicacion_con_comprobaciones(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('publication'))
            ->assertOk()
            ->assertJsonPath('data.landing_published', false)
            ->assertJsonPath('data.mobile_enabled', true)
            ->assertJsonPath('data.stats.teams', 1)
            ->assertJsonPath('data.stats.matches', 1)
            ->assertJsonPath('data.stats.results', 1)
            ->assertJsonPath('data.stats.pending_reports', 0)
            ->assertJsonPath('data.checklist.0.ok', false)
            ->assertJsonPath('data.checklist.1.ok', true)
            ->assertJsonPath('data.checklist.2.ok', true);
    }

    public function test_publica_la_landing_y_genera_el_enlace_publico(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->putJson($this->url('publication'), [
                'landing_published' => true,
                'mobile_enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.landing_published', true)
            ->assertJsonPath('data.mobile_enabled', false)
            ->assertJsonPath('data.public_slug', 'copa-apertura-2026');

        $slug = EventEdition::find($this->edition->id)->public_slug;
        $this->assertSame('copa-apertura-2026', $slug);

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('publication'))
            ->assertOk()
            ->assertJsonPath('data.checklist.3.ok', true)
            ->assertJsonPath('data.checklist.3.detail', '/torneos/'.$slug);
    }

    public function test_la_publicacion_de_otra_edicion_es_404(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/socialevents/v1/admin/editions/{$this->otherEdition->id}/publication")
            ->assertNotFound();

        $this->actingAs($this->organizer, 'sanctum')
            ->putJson("/api/socialevents/v1/admin/editions/{$this->otherEdition->id}/publication", [
                'landing_published' => true,
                'mobile_enabled' => true,
            ])
            ->assertNotFound();
    }

    public function test_con_el_acceso_vencido_se_consulta_pero_no_se_publica(): void
    {
        $this->organization->update([
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('reports'))
            ->assertOk();

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson($this->url('publication'))
            ->assertOk();

        // Preparar un PDF es una lectura y sigue disponible.
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson($this->url('reports/pdf'), ['type' => 'sanciones'])
            ->assertOk();

        $this->actingAs($this->organizer, 'sanctum')
            ->putJson($this->url('publication'), [
                'landing_published' => true,
                'mobile_enabled' => true,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'ORG_READ_ONLY');
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
