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
 * En eliminación cada fecha se sortea de nuevo, así que dos equipos pueden
 * volver a enfrentarse en una jornada posterior de la misma fase. El registro
 * solo debe rechazar el duplicado dentro de la misma jornada; en liga o grupos
 * el cruce repetido sigue siendo un error.
 */
class KnockoutRepeatFixtureTest extends TestCase
{
    use RefreshDatabase;

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

        $organization = SocialeventOrganization::create([
            'name' => 'Organización de prueba',
            'status' => 'active',
            'trial_started_at' => now(),
            'trial_days' => 30,
            'trial_ends_at' => now()->addDays(30),
            'access_until' => now()->addMonth(),
            'match_rate' => 5,
            'prepaid_balance' => 1000,
        ]);

        $this->user = User::factory()->create(['socialevent_organization_id' => $organization->id]);
        foreach (['even_ediciones_fixtures', 'even_ediciones_fixtures_nuevo'] as $permiso) {
            $this->user->givePermissionTo(Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $evento = EvenEvent::create([
            'title' => 'Torneo de prueba',
            'organization_id' => $organization->id,
        ]);

        $this->edition = EventEdition::create([
            'event_id' => $evento->id,
            'year' => 2026,
            'name' => 'Edición de prueba',
            'start_date' => '2026-01-10',
        ]);

        $this->equipoH = EventTeam::create(['name' => 'Equipo Uno', 'short_name' => 'E1', 'organization_id' => $organization->id]);
        $this->equipoA = EventTeam::create(['name' => 'Equipo Dos', 'short_name' => 'E2', 'organization_id' => $organization->id]);
        EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $this->equipoH->id]);
        EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $this->equipoA->id]);
    }

    private function registrar(array $cambios = [])
    {
        return $this->actingAs($this->user)->post(route('even_ediciones_fixtures_store'), array_merge([
            'edition_id' => $this->edition->id,
            'team_h' => $this->equipoH->id,
            'team_a' => $this->equipoA->id,
            'phase' => 'quarterfinals',
            'round_number' => 1,
        ], $cambios));
    }

    public function test_en_eliminacion_se_permite_el_mismo_cruce_en_otra_jornada_de_la_fase(): void
    {
        $this->registrar()->assertStatus(200)->assertSessionHasNoErrors();

        // El sorteo de la jornada 2 vuelve a cruzar a los mismos equipos.
        $this->registrar(['round_number' => 2])->assertStatus(200)->assertSessionHasNoErrors();

        $partidos = EventEditionMatch::where('phase', 'quarterfinals')->orderBy('id')->get();

        $this->assertCount(2, $partidos);
        $this->assertSame([1, 2], $partidos->pluck('round_number')->map(fn ($ronda) => (int) $ronda)->all());
    }

    public function test_en_eliminacion_sigue_bloqueado_el_duplicado_en_la_misma_jornada(): void
    {
        $this->registrar()->assertStatus(200)->assertSessionHasNoErrors();

        $this->registrar()->assertSessionHasErrors('team_h');

        $this->assertSame(1, EventEditionMatch::where('phase', 'quarterfinals')->count());
    }

    public function test_en_liga_el_cruce_repetido_sigue_bloqueado_aunque_cambie_la_jornada(): void
    {
        $this->registrar(['phase' => 'league', 'round_number' => 1])->assertStatus(200)->assertSessionHasNoErrors();

        $this->registrar(['phase' => 'league', 'round_number' => 2])->assertSessionHasErrors('team_h');

        $this->assertSame(1, EventEditionMatch::where('phase', 'league')->count());
    }
}
