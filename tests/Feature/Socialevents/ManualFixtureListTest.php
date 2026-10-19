<?php

namespace Tests\Feature\Socialevents;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\SocialeventOrganization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Un partido creado a mano por la organización debe guardarse y aparecer
 * en la lista de partidos de la edición (no en blanco).
 */
class ManualFixtureListTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $user;

    private EventEdition $edition;

    private EventTeam $equipoH;

    private EventTeam $equipoA;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite de pruebas no trae NOW() (sí existe en MySQL): se registra como función local.
        DB::connection()->getPdo()->sqliteCreateFunction('NOW', fn () => now()->toDateTimeString());
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$partes) => implode('', $partes));

        Parameter::updateOrCreate(
            ['parameter_code' => 'PW00001'],
            ['description' => 'Pagina web principal', 'control_type' => 'sa', 'value_default' => '2'],
        );

        $this->organization = SocialeventOrganization::create([
            'name' => 'Organización de prueba',
            'status' => 'active',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'access_until' => now()->addMonth(),
            'match_rate' => 5,
            'prepaid_balance' => 1000,
        ]);

        $this->user = User::factory()->create(['socialevent_organization_id' => $this->organization->id]);
        foreach (['even_ediciones_fixtures', 'even_ediciones_fixtures_nuevo'] as $permiso) {
            $this->user->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $evento = EvenEvent::create([
            'title' => 'Torneo de prueba',
            'organization_id' => $this->organization->id,
        ]);

        $this->edition = EventEdition::create([
            'event_id' => $evento->id,
            'year' => 2026,
            'name' => 'Edición de prueba',
            'start_date' => '2026-01-10',
        ]);

        $this->equipoH = EventTeam::create(['name' => 'Equipo Uno', 'short_name' => 'E1', 'organization_id' => $this->organization->id]);
        $this->equipoA = EventTeam::create(['name' => 'Equipo Dos', 'short_name' => 'E2', 'organization_id' => $this->organization->id]);
        EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $this->equipoH->id]);
        EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $this->equipoA->id]);
    }

    public function test_partido_creado_a_mano_se_guarda_y_aparece_en_la_lista(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'league',
            'round_number' => 1,
        ])->assertStatus(200);

        $this->assertDatabaseHas('event_edition_matches', [
            'edition_id' => $this->edition->id,
            'team_h_id' => $this->equipoH->id,
            'team_a_id' => $this->equipoA->id,
            'phase' => 'league',
        ]);

        $this->actingAs($this->user)
            ->get(route('even_ediciones_fixtures', $this->edition->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('fixture.league.1', 1)
                ->where('fixture.league.1.0.id', EventEditionMatch::first()->id)
            );
    }

    public function test_sin_saldo_el_error_de_pago_se_devuelve_al_formulario(): void
    {
        $this->organization->update(['prepaid_balance' => 0]);

        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'league',
            'round_number' => 1,
        ])->assertSessionHasErrors('prepaid_balance');

        $this->assertDatabaseCount('event_edition_matches', 0);
    }
}
