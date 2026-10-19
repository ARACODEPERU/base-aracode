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
 * Cada lote de partidos generado por una organización queda registrado como fecha.
 * La primera fecha de la edición es gratis; las siguientes se marcan para cobro.
 */
class MatchBatchTest extends TestCase
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

    private function generarFecha()
    {
        $equipos = EventEditionTeam::where('edition_id', $this->edition->id)->pluck('team_id')->all();

        return $this->actingAs($this->user)->putJson("/socialevents/editions/{$this->edition->id}/fixtures/generate", [
            'teams' => $equipos,
            'format' => 'round_robin',
        ]);
    }

    public function test_primera_fecha_de_la_edicion_queda_registrada_como_gratis(): void
    {
        $this->generarFecha()->assertOk();

        $this->assertDatabaseCount('socialevent_match_batches', 1);
        $this->assertDatabaseHas('socialevent_match_batches', [
            'organization_id' => $this->organization->id,
            'edition_id' => $this->edition->id,
            'format' => 'round_robin',
            'teams_count' => 4,
            'matches_count' => 6,
            'amount' => 60.00,
            'is_free' => true,
        ]);
    }

    private function agregarEquipo(int $numero): void
    {
        $equipo = EventTeam::create([
            'name' => "Equipo {$numero}",
            'short_name' => "E{$numero}",
            'organization_id' => $this->organization->id,
        ]);
        EventEditionTeam::create(['edition_id' => $this->edition->id, 'team_id' => $equipo->id]);
    }

    public function test_las_fechas_siguientes_quedan_marcadas_para_cobro(): void
    {
        $this->generarFecha()->assertOk();
        // Con un quinto equipo aparecen cruces nuevos: 10 partidos en total, 4 nuevos.
        $this->agregarEquipo(5);
        $this->generarFecha()->assertOk();

        $this->assertDatabaseCount('socialevent_match_batches', 2);
        $this->assertDatabaseHas('socialevent_match_batches', ['is_free' => false, 'matches_count' => 4, 'amount' => 40.00]);
    }

    public function test_regenerar_sin_cruces_nuevos_no_crea_lote_ni_cobra(): void
    {
        $this->generarFecha()->assertOk();
        $this->generarFecha()->assertOk();

        $this->assertDatabaseCount('socialevent_match_batches', 1);
        $this->assertDatabaseCount('event_edition_matches', 6);
    }

    public function test_las_fechas_quedan_parejas_alrededor_del_partido_ya_registrado(): void
    {
        $equipos = EventEditionTeam::where('edition_id', $this->edition->id)->orderBy('team_id')->pluck('team_id')->all();

        // El organizador ya había registrado un cruce en la segunda fecha.
        $registrado = EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'team_h_id' => $equipos[0],
            'team_a_id' => $equipos[1],
            'phase' => 'league',
            'round_number' => 2,
            'status' => 'pending',
        ]);

        $this->generarFecha()->assertOk();

        $porFecha = $this->repartoPorFecha();

        self::assertCount(6, EventEditionMatch::where('edition_id', $this->edition->id)->get(), 'Con 4 equipos el todos contra todos tiene 6 partidos.');
        self::assertSame([1 => 2, 2 => 2, 3 => 2], $porFecha, 'Cada fecha debe llevar dos partidos.');
        self::assertSame(2, (int) $registrado->fresh()->round_number, 'El partido ya registrado no cambia de fecha.');
        self::assertSame(
            1,
            EventEditionMatch::where('edition_id', $this->edition->id)
                ->where('team_h_id', $equipos[0])
                ->where('team_a_id', $equipos[1])
                ->count(),
            'El cruce ya registrado no se vuelve a crear.'
        );
    }

    /**
     * Con más equipos el reparto tiene que seguir buscando un acomodo: si solo
     * empuja cada cruce a cualquier fecha con cupo, un equipo termina jugando dos
     * veces el mismo día y otra fecha queda coja.
     */
    public function test_ninguna_fecha_repite_equipos_con_seis_equipos_y_un_partido_ya_registrado(): void
    {
        $this->agregarEquipo(5);
        $this->agregarEquipo(6);

        $equipos = EventEditionTeam::where('edition_id', $this->edition->id)->orderBy('team_id')->pluck('team_id')->all();

        // El organizador ya había registrado un cruce en la segunda fecha.
        $registrado = EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'team_h_id' => $equipos[0],
            'team_a_id' => $equipos[1],
            'phase' => 'league',
            'round_number' => 2,
            'status' => 'pending',
        ]);

        $this->generarFecha()->assertOk();

        $porFecha = $this->repartoPorFecha();

        self::assertCount(15, EventEditionMatch::where('edition_id', $this->edition->id)->get(), 'Con 6 equipos el todos contra todos tiene 15 partidos.');
        self::assertSame([1 => 3, 2 => 3, 3 => 3, 4 => 3, 5 => 3], $porFecha, 'Cada fecha debe llevar tres partidos.');
        self::assertSame(2, (int) $registrado->fresh()->round_number, 'El partido ya registrado no cambia de fecha.');
    }

    /**
     * Cantidad de partidos por fecha; falla si un equipo aparece dos veces el mismo día.
     */
    private function repartoPorFecha(): array
    {
        $porFecha = [];
        $equiposPorFecha = [];

        foreach (EventEditionMatch::where('edition_id', $this->edition->id)->get() as $partido) {
            $fecha = (int) $partido->round_number;
            $porFecha[$fecha] = ($porFecha[$fecha] ?? 0) + 1;

            foreach ([$partido->team_h_id, $partido->team_a_id] as $equipo) {
                self::assertArrayNotHasKey(
                    $equipo,
                    $equiposPorFecha[$fecha] ?? [],
                    "El equipo {$equipo} juega dos veces en la fecha {$fecha}."
                );
                $equiposPorFecha[$fecha][$equipo] = true;
            }
        }

        ksort($porFecha);

        return $porFecha;
    }

    public function test_la_primera_fecha_no_descuenta_saldo_prepago(): void
    {
        $this->generarFecha()->assertOk();

        $this->assertEquals(1000.00, (float) $this->organization->fresh()->prepaid_balance);
        $this->assertDatabaseCount('socialevent_organization_ledger', 6);
        $this->assertDatabaseMissing('socialevent_organization_ledger', ['entry_type' => 'match_charge', 'amount' => -30.00]);
    }

    public function test_fecha_posterior_sin_saldo_queda_bloqueada(): void
    {
        $this->organization->update(['prepaid_balance' => 0]);

        $this->generarFecha()->assertOk();
        $this->agregarEquipo(5);
        $this->generarFecha()->assertStatus(422);

        // La transacción se revierte: solo queda el lote gratis y sus 6 partidos.
        $this->assertDatabaseCount('socialevent_match_batches', 1);
        $this->assertDatabaseCount('event_edition_matches', 6);
    }
}
