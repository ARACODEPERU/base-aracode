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
 * El organizador tiene que poder reacomodar las jornadas de un fixture generado
 * (el modal ofrece «Jornada / Fecha»), sin poder tocar lo que ya se cobró ni
 * reprogramar un partido jugado.
 */
class FixtureRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private SocialeventOrganization $organization;

    private User $user;

    private EventEdition $edition;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->user->givePermissionTo(Permission::firstOrCreate(['name' => 'even_ediciones_fixtures', 'guard_name' => 'web']));
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

        for ($i = 1; $i <= 4; $i++) {
            $equipo = EventTeam::create([
                'name' => "Equipo {$i}",
                'short_name' => "E{$i}",
                'organization_id' => $this->organization->id,
            ]);
            EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $equipo->id]);
        }
    }

    private function generarFixture(): void
    {
        $equipos = EventEditionTeam::where('edition_id', $this->edition->id)->pluck('team_id')->all();

        $this->actingAs($this->user)->putJson("/socialevents/editions/{$this->edition->id}/fixtures/generate", [
            'teams' => $equipos,
            'format' => 'round_robin',
        ])->assertOk();
    }

    private function unPartido(int $jornada = null): EventEditionMatch
    {
        return EventEditionMatch::where('edition_id', $this->edition->id)
            ->when($jornada !== null, fn ($q) => $q->where('round_number', $jornada))
            ->orderBy('round_number')
            ->firstOrFail();
    }

    private function editar(EventEditionMatch $partido, array $datos)
    {
        return $this->actingAs($this->user)->putJson("/socialevents/editions/fixtures/{$partido->id}/update", array_merge([
            'round_number' => $partido->round_number,
            'match_date' => $partido->match_date,
            'equipo_h_id' => $partido->team_h_id,
            'equipo_a_id' => $partido->team_a_id,
            'phase' => $partido->phase,
            'status' => $partido->status,
        ], $datos));
    }

    public function test_el_organizador_puede_mover_un_partido_generado_a_otra_jornada(): void
    {
        $this->generarFixture();

        $partido = $this->unPartido(3);
        $saldo = (float) $this->organization->fresh()->prepaid_balance;
        $cargos = DB::table('socialevent_organization_ledger')->count();

        // El modal manda la jornada nueva y, si no se programó horario, la fecha va vacía.
        $this->editar($partido, ['round_number' => 2, 'match_date' => '2026-10-20 18:00'])->assertOk();

        $partido->refresh();
        self::assertSame(2, (int) $partido->round_number, 'La jornada nueva debe guardarse.');
        self::assertSame('2026-10-20 18:00', $partido->match_date->format('Y-m-d H:i'));

        // Mover de jornada no cobra ni devuelve nada: el cobro es por partido.
        self::assertSame($saldo, (float) $this->organization->fresh()->prepaid_balance);
        self::assertSame($cargos, DB::table('socialevent_organization_ledger')->count());
    }

    public function test_mover_de_jornada_sin_horario_no_rompe_la_fecha_del_partido(): void
    {
        $this->generarFixture();

        // Un partido generado llega sin horario: la jornada se puede corregir igual.
        $partido = $this->unPartido(1);
        self::assertNull($partido->match_date);

        $this->editar($partido, ['round_number' => 3, 'match_date' => null])->assertOk();

        $partido->refresh();
        self::assertSame(3, (int) $partido->round_number);
        self::assertNull($partido->match_date);
    }

    public function test_no_se_pueden_cambiar_los_equipos_de_un_partido_cobrado(): void
    {
        $this->generarFixture();

        $partido = $this->unPartido(1);
        $otroEquipo = EventEditionTeam::where('edition_id', $this->edition->id)
            ->whereNotIn('team_id', [$partido->team_h_id, $partido->team_a_id])
            ->value('team_id');

        $this->editar($partido, ['equipo_a_id' => $otroEquipo])->assertStatus(422);

        self::assertSame($partido->team_a_id, $partido->fresh()->team_a_id);
    }

    public function test_no_se_reprograma_un_partido_ya_jugado(): void
    {
        $this->generarFixture();

        $partido = $this->unPartido(2);
        $partido->update(['status' => 'closed', 'score_h' => 2, 'score_a' => 1]);

        $this->editar($partido, ['round_number' => 1])->assertStatus(422);

        // Lo que no cambia jornada ni equipos se sigue pudiendo guardar.
        $this->editar($partido, ['location' => 'Cancha 2'])->assertOk();

        self::assertSame(2, (int) $partido->fresh()->round_number);
        self::assertSame('Cancha 2', $partido->fresh()->location);
    }
}
