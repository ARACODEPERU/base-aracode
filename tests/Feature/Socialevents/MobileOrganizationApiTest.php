<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Fase 0 de la app móvil multi-organización.
 *
 * Fija tres contratos: la sesión móvil trae organización y vigencia, los
 * endpoints "my/*" solo devuelven datos de la organización autenticada y una
 * edición sin publicar no se revela a terceros pero sí a su dueño.
 */
class MobileOrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $organizer;

    private EvenEvent $event;

    private EventEdition $publishedEdition;

    private EventEdition $draftEdition;

    private SocialeventOrganization $otherOrganization;

    private User $otherOrganizer;

    private EvenEvent $otherEvent;

    private EventEdition $otherEdition;

    protected function setUp(): void
    {
        parent::setUp();

        // Modo deportivo activo: PW00001 = 2 (torneos).
        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = $this->crearOrganizacion('Organización A');
        $this->organizer = $this->crearOrganizador($this->organization, 'a@example.com');

        $this->event = EvenEvent::create([
            'title' => 'Torneo de la organización A',
            'organization_id' => $this->organization->id,
        ]);

        $this->publishedEdition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => 2026,
            'name' => 'Edición publicada',
            'start_date' => '2026-01-10',
            'landing_published' => true,
            'mobile_enabled' => true,
        ]);

        $this->draftEdition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => 2026,
            'name' => 'Edición en borrador',
            'start_date' => '2026-06-10',
            'landing_published' => false,
            'mobile_enabled' => false,
        ]);

        $this->otherOrganization = $this->crearOrganizacion('Organización B');
        $this->otherOrganizer = $this->crearOrganizador($this->otherOrganization, 'b@example.com');

        $this->otherEvent = EvenEvent::create([
            'title' => 'Torneo de la organización B',
            'organization_id' => $this->otherOrganization->id,
        ]);

        $this->otherEdition = EventEdition::create([
            'event_id' => $this->otherEvent->id,
            'year' => 2026,
            'name' => 'Edición de B',
            'start_date' => '2026-02-10',
            'landing_published' => false,
            'mobile_enabled' => false,
        ]);
    }

    public function test_la_sesion_movil_devuelve_organizacion_y_vigencia(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('organization.id', $this->organization->id)
            ->assertJsonPath('organization.name', 'Organización A')
            ->assertJsonPath('access.status', 'trial')
            ->assertJsonPath('access.can_read', true)
            ->assertJsonPath('access.can_write', true)
            ->assertJsonPath('access.reason', null)
            ->assertJsonPath('features.sports_mode', true)
            ->assertJsonPath('roles.0', 'sports_organizer');
    }

    public function test_la_sesion_sin_organizacion_lo_indica_sin_inventar_una(): void
    {
        $sinOrganizacion = User::factory()->create();

        $this->actingAs($sinOrganizacion, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('organization', null)
            ->assertJsonPath('access.status', 'none')
            ->assertJsonPath('access.can_write', false)
            ->assertJsonPath('access.reason', 'no_organization');
    }

    public function test_mis_eventos_solo_incluye_la_organizacion_autenticada(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->getJson('/api/socialevents/v1/my/events')
            ->assertOk();

        $eventos = $response->json('data');

        $this->assertCount(1, $eventos, 'Solo debe aparecer el evento de la organización autenticada.');
        $this->assertSame($this->event->id, $eventos[0]['id']);

        $ediciones = array_column($eventos[0]['editions'], 'id');
        sort($ediciones);

        $esperadas = [$this->publishedEdition->id, $this->draftEdition->id];
        sort($esperadas);

        $this->assertSame($esperadas, $ediciones, 'El dueño también ve sus ediciones sin publicar.');
    }

    public function test_no_puede_ver_la_edicion_de_otra_organizacion(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/socialevents/v1/my/editions/{$this->otherEdition->id}")
            ->assertNotFound();

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/socialevents/v1/my/editions/{$this->draftEdition->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->draftEdition->id)
            ->assertJsonPath('data.event.id', $this->event->id);
    }

    public function test_la_edicion_propia_trae_su_configuracion_editable(): void
    {
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/socialevents/v1/my/editions/{$this->draftEdition->id}")
            ->assertOk()
            ->assertJsonPath('data.event_id', $this->event->id)
            ->assertJsonPath('data.name', 'Edición en borrador')
            ->assertJsonPath('data.score_points_win', 3)
            ->assertJsonPath('data.score_points_draw', 1)
            ->assertJsonPath('data.min_players_per_team', 5)
            ->assertJsonPath('data.max_players_per_team', 15)
            ->assertJsonStructure([
                'data' => [
                    'competition_format',
                    'score_points_loss',
                    'inscription_fee',
                    'regular_half_minutes',
                    'regular_halves',
                    'extra_half_minutes',
                    'extra_halves',
                    'extra_time_enabled',
                    'max_substitutions',
                    'extra_substitution_enabled',
                    'yellow_price',
                    'direct_red_price',
                    'double_yellow_price',
                    'contact_name',
                    'contact_phone',
                    'contact_whatsapp',
                    'public_slug',
                    'landing_published',
                    'mobile_enabled',
                ],
            ]);
    }

    public function test_una_edicion_sin_publicar_no_se_revela_a_terceros_pero_si_a_su_dueno(): void
    {
        $url = "/api/socialevents/v1/edition/{$this->draftEdition->id}/public";

        // Sin sesión: 404, sin confirmar que la edición existe.
        $this->getJson($url)->assertNotFound();

        // Otra organización: tampoco.
        $this->actingAs($this->otherOrganizer, 'sanctum')->getJson($url)->assertNotFound();

        // El dueño puede preparar su edición antes de publicarla.
        $this->actingAs($this->organizer, 'sanctum')->getJson($url)->assertOk();
    }

    public function test_una_edicion_publicada_sigue_siendo_publica(): void
    {
        $this->getJson("/api/socialevents/v1/edition/{$this->publishedEdition->id}/public")
            ->assertOk();
    }

    public function test_el_evento_publico_no_revela_la_edicion_en_borrador(): void
    {
        // El evento tiene una edición publicada y otra en borrador: el visitante
        // solo recibe la publicada, nunca la más reciente sin publicar.
        $this->getJson("/api/socialevents/v1/event/{$this->event->id}")
            ->assertOk()
            ->assertJsonPath('data.edition.id', $this->publishedEdition->id);

        // Un evento sin ninguna edición publicada responde 404.
        $this->getJson("/api/socialevents/v1/event/{$this->otherEvent->id}")
            ->assertNotFound();
    }

    public function test_el_dueno_ve_su_evento_con_la_edicion_sin_publicar(): void
    {
        $this->actingAs($this->otherOrganizer, 'sanctum')
            ->getJson("/api/socialevents/v1/event/{$this->otherEvent->id}")
            ->assertOk()
            ->assertJsonPath('data.edition.id', $this->otherEdition->id);

        // Otra organización con sesión tampoco lo ve.
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson("/api/socialevents/v1/event/{$this->otherEvent->id}")
            ->assertNotFound();
    }

    public function test_los_partidos_de_una_edicion_en_borrador_solo_los_ve_su_dueno(): void
    {
        $url = "/api/socialevents/v1/edition/{$this->draftEdition->id}/matches/upcoming";

        $this->getJson($url)->assertNotFound();

        $this->actingAs($this->organizer, 'sanctum')
            ->getJson($url)
            ->assertOk();
    }

    public function test_el_acceso_vencido_responde_org_read_only_y_conserva_la_lectura(): void
    {
        $this->organization->update([
            'trial_ends_at' => now()->subDay(),
            'status' => 'trial',
        ]);

        // La lectura de sus torneos sigue disponible.
        $this->actingAs($this->organizer, 'sanctum')
            ->getJson('/api/socialevents/v1/my/events')
            ->assertOk();

        // La escritura se corta con un código que la app puede interpretar.
        $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/socialevents/v1/admin/teams', ['name' => 'Equipo nuevo'])
            ->assertForbidden()
            ->assertJsonPath('code', 'ORG_READ_ONLY')
            ->assertJsonPath('access.can_write', false)
            ->assertJsonPath('access.reason', 'trial_expired');
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
}
