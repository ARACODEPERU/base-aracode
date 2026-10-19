<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Agregar equipos a una edición desde una organización deportiva (PW00001 = 2).
 */
class OrganizationAddTeamTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $user;

    private EventEdition $edition;

    protected function setUp(): void
    {
        parent::setUp();

        // Modo deportivo activo: PW00001 = 2 (torneos).
        // Las migraciones ya insertan PW00001, así que se actualiza en lugar de crear.
        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = SocialeventOrganization::create([
            'name' => 'Organización de prueba',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
        ]);

        $this->user = User::factory()->create([
            'socialevent_organization_id' => $this->organization->id,
        ]);

        Permission::firstOrCreate(['name' => 'even_ediciones_equipos', 'guard_name' => 'web']);
        $this->user->givePermissionTo('even_ediciones_equipos');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $event = EvenEvent::create([
            'title' => 'Torneo de prueba',
            'organization_id' => $this->organization->id,
        ]);

        $this->edition = EventEdition::create([
            'event_id' => $event->id,
            'year' => 2026,
            'name' => 'Edición de prueba',
            'start_date' => '2026-01-10',
        ]);
    }

    public function test_organizacion_agrega_equipo_propio_que_no_esta_en_la_edicion(): void
    {
        $team = EventTeam::create([
            'name' => 'Equipo propio',
            'short_name' => 'EPR',
            'organization_id' => $this->organization->id,
        ]);

        $this->actingAs($this->user)
            ->post("/socialevents/editions/{$this->edition->id}/teams/add", [
                'edition_id' => $this->edition->id,
                'team_id' => $team->id,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('event_edition_teams', [
            'edition_id' => $this->edition->id,
            'team_id' => $team->id,
        ]);
    }

    public function test_no_permite_agregar_un_equipo_de_otra_organizacion(): void
    {
        $otra = SocialeventOrganization::create([
            'name' => 'Otra organización',
            'status' => 'trial',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
        ]);

        $ajeno = EventTeam::create([
            'name' => 'Equipo ajeno',
            'short_name' => 'EAJ',
            'organization_id' => $otra->id,
        ]);

        $this->actingAs($this->user)
            ->post("/socialevents/editions/{$this->edition->id}/teams/add", [
                'edition_id' => $this->edition->id,
                'team_id' => $ajeno->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('event_edition_teams', [
            'edition_id' => $this->edition->id,
            'team_id' => $ajeno->id,
        ]);
    }
}
