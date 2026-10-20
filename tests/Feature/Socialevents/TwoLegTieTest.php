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
use Modules\Socialevents\Services\TournamentTieService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Un cruce de ida y vuelta crea dos partidos enlazados (tie_id + leg), con los
 * equipos invertidos, un cargo por partido y un marcador global.
 */
class TwoLegTieTest extends TestCase
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

    public function test_ida_y_vuelta_crea_dos_partidos_enlazados_con_equipos_invertidos(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'quarterfinals',
            'round_number' => 1,
            'match_date' => '2026-01-15 20:00',
            'tie_format' => 'home_and_away',
            'match_date_return' => '2026-01-22 20:00',
        ])->assertStatus(200)->assertSessionHasNoErrors();

        $partidos = EventEditionMatch::orderBy('id')->get();

        $this->assertCount(2, $partidos);

        $ida = $partidos->first();
        $vuelta = $partidos->last();

        $this->assertSame(1, (int) $ida->leg);
        $this->assertSame(2, (int) $vuelta->leg);
        $this->assertSame($ida->id, (int) $vuelta->tie_id);
        $this->assertSame('home_and_away', $ida->tie_format);
        $this->assertSame('home_and_away', $vuelta->tie_format);

        // Los equipos se invierten en la vuelta.
        $this->assertSame($this->equipoH->id, (int) $ida->team_h_id);
        $this->assertSame($this->equipoA->id, (int) $ida->team_a_id);
        $this->assertSame($this->equipoA->id, (int) $vuelta->team_h_id);
        $this->assertSame($this->equipoH->id, (int) $vuelta->team_a_id);

        // Ambas piernas quedan en la misma fase.
        $this->assertSame('quarterfinals', $vuelta->phase);
    }

    public function test_la_ida_y_la_vuelta_quedan_en_jornadas_distintas(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'quarterfinals',
            'round_number' => 1,
            'tie_format' => 'home_and_away',
        ])->assertStatus(200)->assertSessionHasNoErrors();

        $partidos = EventEditionMatch::orderBy('id')->get();

        // Por defecto la ida es la jornada 1 y la vuelta la 2, no la misma fecha.
        $this->assertSame(1, (int) $partidos->first()->round_number);
        $this->assertSame(2, (int) $partidos->last()->round_number);
    }

    public function test_el_fixture_agrupa_la_ida_y_la_vuelta_en_dos_jornadas(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'quarterfinals',
            'round_number' => 1,
            'tie_format' => 'home_and_away',
        ])->assertStatus(200);

        $this->actingAs($this->user)
            ->get(route('even_ediciones_fixtures', $this->edition->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                // Cuartos se pinta en dos bloques de jornada: ida y vuelta.
                ->where('fixture', fn ($fixture) => collect($fixture['quarterfinals'])->keys()->map(fn ($jornada) => (int) $jornada)->all() === [1, 2])
            );
    }

    public function test_la_jornada_de_la_vuelta_se_puede_elegir(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'semifinals',
            'round_number' => 3,
            'round_number_return' => 5,
            'tie_format' => 'home_and_away',
        ])->assertStatus(200)->assertSessionHasNoErrors();

        $partidos = EventEditionMatch::orderBy('id')->get();

        $this->assertSame(3, (int) $partidos->first()->round_number);
        $this->assertSame(5, (int) $partidos->last()->round_number);
    }

    public function test_la_jornada_de_la_vuelta_no_puede_ser_igual_o_anterior_a_la_ida(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'semifinals',
            'round_number' => 2,
            'round_number_return' => 2,
            'tie_format' => 'home_and_away',
        ])->assertSessionHasErrors('round_number_return');

        // El cruce no se crea a medias: ni la ida ni la vuelta quedan registradas.
        $this->assertSame(0, EventEditionMatch::count());
    }

    public function test_ida_y_vuelta_genera_dos_cargos(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'semifinals',
            'round_number' => 1,
            'tie_format' => 'home_and_away',
        ])->assertStatus(200);

        $this->assertSame(2, EventEditionMatch::count());

        // Un cargo por partido: el cobro sigue siendo por partido, no por cruce.
        $this->assertSame(
            2,
            DB::table('socialevent_organization_ledger')
                ->where('edition_id', $this->edition->id)
                ->where('entry_type', 'match_charge')
                ->count()
        );
    }

    public function test_ida_y_vuelta_no_se_permite_en_fase_de_liga(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'league',
            'round_number' => 1,
            'tie_format' => 'home_and_away',
        ])->assertSessionHasErrors('tie_format');

        $this->assertSame(0, EventEditionMatch::count());
    }

    public function test_muerte_subita_se_permite_solo_en_eliminacion(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'final',
            'round_number' => 1,
            'tie_format' => 'single_sudden_death',
        ])->assertSessionHasNoErrors();

        $match = EventEditionMatch::first();

        $this->assertSame('single', $match->tie_format);
        $this->assertSame('sudden_death', $match->decider);
        $this->assertNull($match->leg);
    }

    public function test_las_fases_nuevas_se_guardan_con_su_clave_canonica(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'round_16',
            'round_number' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('event_edition_matches', [
            'edition_id' => $this->edition->id,
            'phase' => 'round_16',
        ]);
    }

    public function test_el_global_y_el_ganador_salen_de_las_dos_piernas(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'final',
            'round_number' => 1,
            'tie_format' => 'home_and_away',
        ])->assertStatus(200);

        $partidos = EventEditionMatch::orderBy('id')->get();
        $ida = $partidos->first();
        $vuelta = $partidos->last();

        // Ida 2-0 para el local; vuelta (equipos invertidos) 1-1.
        $ida->update(['score_h' => 2, 'score_a' => 0, 'status' => 'closed']);
        $vuelta->update(['score_h' => 1, 'score_a' => 1, 'status' => 'closed']);

        $summary = app(TournamentTieService::class)->summary($ida->fresh());

        // Desde la perspectiva de los equipos de la ida: 3 - 1.
        $this->assertSame(3, $summary['aggregate_h']);
        $this->assertSame(1, $summary['aggregate_a']);
        $this->assertSame('aggregate', $summary['decided_by']);
        $this->assertSame((int) $this->equipoH->id, $summary['winner_team_id']);

        // Desde la vuelta (equipos invertidos) el global se lee al revés.
        $summaryVuelta = app(TournamentTieService::class)->summary($vuelta->fresh());

        $this->assertSame(1, $summaryVuelta['aggregate_h']);
        $this->assertSame(3, $summaryVuelta['aggregate_a']);
        $this->assertSame((int) $this->equipoH->id, $summaryVuelta['winner_team_id']);
    }

    public function test_global_empatado_se_define_por_penales_de_la_vuelta(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'semifinals',
            'round_number' => 1,
            'tie_format' => 'home_and_away',
        ])->assertStatus(200);

        $partidos = EventEditionMatch::orderBy('id')->get();
        $ida = $partidos->first();
        $vuelta = $partidos->last();

        $ida->update(['score_h' => 1, 'score_a' => 0, 'status' => 'closed']);
        // La vuelta la gana el equipo que era visitante en la ida, 1-0: global 1-1.
        $vuelta->update([
            'score_h' => 1,
            'score_a' => 0,
            'status' => 'closed',
            'penalties' => [
                ['team' => 'local', 'result' => 'goal'],
                ['team' => 'visitor', 'result' => 'miss'],
            ],
        ]);

        $summary = app(TournamentTieService::class)->summary($vuelta->fresh());

        $this->assertSame(1, $summary['aggregate_h']);
        $this->assertSame(1, $summary['aggregate_a']);
        $this->assertSame('penalties', $summary['decided_by']);
        // Gana en penales el local de la vuelta, que es el equipo A.
        $this->assertSame((int) $this->equipoA->id, $summary['winner_team_id']);
    }

    public function test_editar_un_partido_cambia_la_fase_del_cruce_completo(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'quarterfinals',
            'round_number' => 1,
            'tie_format' => 'home_and_away',
        ])->assertStatus(200);

        $ida = EventEditionMatch::orderBy('id')->first();

        $this->actingAs($this->user)->put(route('even_ediciones_fixtures_update', $ida->id), [
            'round_number' => 1,
            'phase' => 'semifinals',
            'tie_format' => 'home_and_away',
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            ['semifinals', 'semifinals'],
            EventEditionMatch::orderBy('id')->pluck('phase')->all()
        );
    }

    public function test_el_formulario_de_crear_recibe_las_fases_y_los_formatos(): void
    {
        $this->actingAs($this->user)
            ->get(route('even_ediciones_fixtures_create', $this->edition->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('phaseOptions', 7)
                ->has('tieFormats', 3)
                // Octavos de final es la tercera opción del catálogo.
                ->where('phaseOptions.2.value', 'round_16')
                ->where('phaseOptions.2.label', 'Octavos de final')
                ->where('phaseOptions.5.label', 'Tercer y cuarto puesto')
                ->where('tieFormats.2.value', 'home_and_away')
            );
    }

    public function test_el_fixture_expone_las_fases_de_eliminacion_en_orden(): void
    {
        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'final',
            'round_number' => 1,
        ])->assertStatus(200);

        $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), [
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'third_place',
            'round_number' => 1,
        ])->assertStatus(200);

        $this->actingAs($this->user)
            ->get(route('even_ediciones_fixtures', $this->edition->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('fixture', 2)
                ->where('fixture', fn ($fixture) => collect($fixture)->keys()->all() === ['third_place', 'final'])
                ->has('phaseOptions', 7)
                ->where('phaseOptions.5.value', 'third_place')
                ->where('phaseOptions.6.value', 'final')
            );
    }
}
