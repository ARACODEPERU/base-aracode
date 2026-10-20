<?php

namespace Tests\Feature\Socialevents;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchGoal;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Services\TournamentRankingsService;
use Tests\TestCase;

/**
 * Rankings públicos de la edición: quién entra a la lista de jugadores de campo
 * y quién a la de arqueros, y cómo se traduce el detalle de goles (incluido el
 * gol en propia puerta) al puntaje de cada jugador.
 */
class TournamentRankingsTest extends TestCase
{
    use RefreshDatabase;

    private EventEdition $edition;

    private EventTeam $homeTeam;

    private EventTeam $awayTeam;

    private TournamentRankingsService $rankings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rankings = app(TournamentRankingsService::class);

        $event = EvenEvent::create(['title' => 'Evento de prueba']);

        $this->edition = EventEdition::create([
            'event_id' => $event->id,
            'year' => 2026,
            'name' => 'Edición de prueba',
            'start_date' => '2026-01-10',
            'regular_half_minutes' => 20,
            'regular_halves' => 2,
        ]);

        $this->homeTeam = EventTeam::create(['name' => 'Local', 'short_name' => 'LOC']);
        $this->awayTeam = EventTeam::create(['name' => 'Visitante', 'short_name' => 'VIS']);

        foreach ([$this->homeTeam, $this->awayTeam] as $team) {
            EventEditionTeam::create([
                'edition_id' => $this->edition->id,
                'team_id' => $team->id,
            ]);
        }
    }

    // ─── Reparto entre jugadores de campo y arqueros ───────────────────────

    public function test_el_arquero_entra_por_su_posicion_aunque_no_tenga_atajadas(): void
    {
        // Arquero que jugó sin atajar nada y mantuvo la valla invicta: con la
        // regla vieja (saves > 0) quedaba afuera de su propia lista y aparecía
        // entre los jugadores de campo.
        $this->registerPlayer($this->homeTeam, 10, 'Arquero');
        $match = $this->makeMatch();

        $this->stat($match, 10, ['saves' => 0, 'clean_sheet' => 1]);

        $keepers = $this->rankings->topGoalkeepersPayload($this->edition->id);

        $this->assertCount(1, $keepers);
        $this->assertSame(10, $keepers[0]['player_id']);
        // Valla invicta del arquero: 5 puntos según los pesos del ranking.
        $this->assertSame(5.0, $keepers[0]['points']);

        $this->assertSame([], $this->rankings->topPlayersPayload($this->edition->id));
    }

    public function test_un_jugador_de_campo_no_aparece_como_arquero(): void
    {
        $this->registerPlayer($this->homeTeam, 20, 'Defensa central');
        $match = $this->makeMatch();

        $this->stat($match, 20, ['goals' => 1]);

        $this->assertSame([], $this->rankings->topGoalkeepersPayload($this->edition->id));

        $players = $this->rankings->topPlayersPayload($this->edition->id);

        $this->assertCount(1, $players);
        $this->assertSame(20, $players[0]['player_id']);
        // Un gol pesa 1 punto en "mejor jugador" (el goleador tiene su lista).
        $this->assertSame(1.0, $players[0]['points']);
    }

    public function test_quien_atajo_sin_posicion_registrada_cuenta_como_arquero(): void
    {
        // Plantilla vieja sin posición cargada: el detalle de atajadas (quien
        // atajó) sigue siendo el respaldo para reconocer al arquero.
        $this->registerPlayer($this->awayTeam, 30, '');
        $match = $this->makeMatch();

        $this->stat($match, 30, ['saves' => 3]);

        $keepers = $this->rankings->topGoalkeepersPayload($this->edition->id);

        $this->assertCount(1, $keepers);
        $this->assertSame(30, $keepers[0]['player_id']);
        $this->assertSame(1.5, $keepers[0]['points']);

        $this->assertSame([], $this->rankings->topPlayersPayload($this->edition->id));
    }

    public function test_el_detalle_del_jugador_clasifica_igual_que_la_tarjeta(): void
    {
        $this->registerPlayer($this->homeTeam, 10, 'Guardameta');
        $match = $this->makeMatch();

        $this->stat($match, 10, ['saves' => 0, 'clean_sheet' => 1, 'minutes_played' => 40]);

        EventEditionMatchParticipation::create([
            'match_id' => $match->id,
            'player_id' => 10,
            'role' => 'starter',
        ]);

        $asKeeper = $this->rankings->playerDetail($this->edition->id, 10, 'goalkeeper');
        $asFieldPlayer = $this->rankings->playerDetail($this->edition->id, 10, 'player');

        $this->assertNotNull($asKeeper);
        $this->assertNotNull($asFieldPlayer);

        // El arquero acumula en su lista; como jugador de campo no tiene partidos.
        $this->assertSame(1, $asKeeper['summary']['matches_played']);
        $this->assertSame(5.0, $asKeeper['summary']['points']);
        $this->assertNotEmpty($asKeeper['breakdown']);
        // Como jugador de campo no le queda ningún partido que explicar: la
        // clasificación del detalle es la misma que la de la tarjeta.
        $this->assertSame([], $asFieldPlayer['breakdown']);
        $this->assertSame(0.0, $asFieldPlayer['summary']['points']);
    }

    // ─── Gol en propia puerta ─────────────────────────────────────────────

    public function test_el_gol_en_propia_puerta_no_es_gol_de_quien_lo_metio(): void
    {
        $this->registerPlayer($this->homeTeam, 40, 'Defensa central');
        $match = $this->makeMatch();

        EventEditionMatchGoal::create([
            'match_id' => $match->id,
            'team_id' => $this->homeTeam->id,
            'player_id' => 40,
            'minute' => 12,
            'minute_in_period' => 12,
            'period' => 'first',
            'goal_type' => 'normal',
            'is_own_goal' => true,
        ]);

        // El gol queda en el detalle del partido (es un gol del juego), pero no
        // se acredita a quien lo metió en su arco.
        $this->stat($match, 40, ['goals' => 0]);

        $this->assertSame([], $this->rankings->topScorersPayload($this->edition->id));

        $players = $this->rankings->topPlayersPayload($this->edition->id);

        $this->assertCount(1, $players);
        $this->assertSame(0, $players[0]['stats']['goals']);
        $this->assertSame(0.0, $players[0]['points']);
    }

    public function test_el_resto_de_goles_si_suma_junto_a_un_gol_en_propia_puerta(): void
    {
        $this->registerPlayer($this->homeTeam, 41, 'Delantero centro');
        $match = $this->makeMatch();

        // Dos goles del mismo jugador, ninguno en propia puerta.
        foreach ([10, 25] as $minute) {
            EventEditionMatchGoal::create([
                'match_id' => $match->id,
                'team_id' => $this->homeTeam->id,
                'player_id' => 41,
                'minute' => $minute,
                'period' => 'first',
                'goal_type' => 'normal',
                'is_own_goal' => false,
            ]);
        }

        $this->stat($match, 41, ['goals' => 2]);

        $scorers = $this->rankings->topScorersPayload($this->edition->id);

        $this->assertCount(1, $scorers);
        $this->assertSame(2, $scorers[0]['goals']);

        $players = $this->rankings->topPlayersPayload($this->edition->id);

        $this->assertSame(2.0, $players[0]['points']);
    }

    public function test_los_goles_pesan_menos_que_una_asistencia_y_un_mvp(): void
    {
        $this->registerPlayer($this->homeTeam, 70, 'Delantero centro');
        $this->registerPlayer($this->homeTeam, 71, 'Enganche');
        $match = $this->makeMatch();

        $this->stat($match, 70, ['goals' => 3]);
        $this->stat($match, 71, ['assists' => 1, 'is_mvp' => true]);

        $players = $this->rankings->topPlayersPayload($this->edition->id);

        // 3 goles = 3 pts; 1 asistencia + 1 MVP = 10 pts. El goleador no gana
        // el "mejor jugador" solo por hacer goles.
        $this->assertSame(71, $players[0]['player_id']);
        $this->assertSame(10.0, $players[0]['points']);
        $this->assertSame(3.0, $players[1]['points']);
    }

    public function test_la_valla_invicta_no_suma_para_el_mejor_jugador(): void
    {
        // Cada jugador de un equipo que no recibe goles queda con clean_sheet=1:
        // en "mejor jugador" eso no debe sumar puntos, solo en la lista de arqueros.
        $this->registerPlayer($this->homeTeam, 80, 'Lateral derecho');
        $this->registerPlayer($this->homeTeam, 81, 'Arquero');
        $match = $this->makeMatch();

        $this->stat($match, 80, ['assists' => 1, 'clean_sheet' => 1]);
        $this->stat($match, 81, ['clean_sheet' => 1]);

        $players = $this->rankings->topPlayersPayload($this->edition->id);

        // 1 asistencia (4) y la valla invicta no aporta nada.
        $this->assertCount(1, $players);
        $this->assertSame(80, $players[0]['player_id']);
        $this->assertSame(4.0, $players[0]['points']);

        $detail = $this->rankings->playerDetail($this->edition->id, 80, 'player');

        $this->assertSame(4.0, $detail['summary']['points']);
        $this->assertSame(
            [],
            array_values(array_filter(
                $detail['reasons'],
                fn (array $reason) => $reason['concept'] === 'Valla invicta'
            ))
        );

        // El arquero sí sigue cobrando su valla invicta (5 pts).
        $keepers = $this->rankings->topGoalkeepersPayload($this->edition->id);

        $this->assertSame(5.0, $keepers[0]['points']);
    }

    // ─── Sanciones ───────────────────────────────────────────────────

    public function test_una_sancion_descuenta_una_sola_vez_aunque_haya_varios_partidos(): void
    {
        // Antes el total de sanciones se restaba en cada acta: una sola tarjeta
        // en dos partidos costaba 2 puntos en vez de 1.
        $this->registerPlayer($this->homeTeam, 50, 'Delantero centro');

        $first = $this->makeMatch(1);
        $second = $this->makeMatch(2);

        $this->stat($first, 50, ['goals' => 2]);
        $this->stat($second, 50, ['assists' => 1]);

        $this->sanction($first, 50);

        $players = $this->rankings->topPlayersPayload($this->edition->id);

        // 2 goles (2) + 1 asistencia (4) − 1 sanción = 5.
        $this->assertSame(5.0, $players[0]['points']);

        $detail = $this->rankings->playerDetail($this->edition->id, 50, 'player');

        $this->assertSame(5.0, $detail['summary']['points']);
        $this->assertSame(
            5.0,
            round(array_sum(array_column($detail['breakdown'], 'points')), 2)
        );

        // La explicación del modal también muestra una sola aplicación.
        $sanctionsReason = collect($detail['reasons'])->firstWhere('concept', 'Sanciones');

        $this->assertSame(1, $sanctionsReason['count']);
        $this->assertSame(-1.0, (float) $sanctionsReason['points']);
    }

    public function test_el_arquero_tambien_descuenta_la_sancion_una_sola_vez(): void
    {
        $this->registerPlayer($this->homeTeam, 60, 'Arquero');

        $first = $this->makeMatch(1);
        $second = $this->makeMatch(2);

        $this->stat($first, 60, ['clean_sheet' => 1]);
        $this->stat($second, 60, ['clean_sheet' => 1]);

        $this->sanction($first, 60);

        $keepers = $this->rankings->topGoalkeepersPayload($this->edition->id);

        // 2 vallas invictas (10) − 1 sanción = 9.
        $this->assertSame(9.0, $keepers[0]['points']);

        $detail = $this->rankings->playerDetail($this->edition->id, 60, 'goalkeeper');

        $this->assertSame(9.0, $detail['summary']['points']);
    }

    // ─── Utilidades ───────────────────────────────────────────────────────

    private function makeMatch(int $round = 1): EventEditionMatch
    {
        return EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'team_h_id' => $this->homeTeam->id,
            'team_a_id' => $this->awayTeam->id,
            'phase' => 'league',
            'round_number' => $round,
            'match_date' => $round === 1 ? '2026-01-15 10:00:00' : '2026-01-22 10:00:00',
            'status' => 'finished',
        ]);
    }

    private function sanction(EventEditionMatch $match, int $personId): void
    {
        EventEditionMatchSanction::create([
            'match_id' => $match->id,
            'player_id' => $personId,
            'type' => 'yellow',
            'minute' => 20,
            'amount_fine' => 0,
        ]);
    }

    private function stat(EventEditionMatch $match, int $personId, array $attributes = []): EventEditionMatchPlayerStat
    {
        return EventEditionMatchPlayerStat::create(array_merge([
            'match_id' => $match->id,
            'player_id' => $personId,
            'team_id' => $this->homeTeam->id,
            'goals' => 0,
            'assists' => 0,
            'saves' => 0,
            'minutes_played' => 0,
            'is_mvp' => false,
            'clean_sheet' => 0,
        ], $attributes));
    }

    private function registerPlayer(EventTeam $team, int $personId, string $position): void
    {
        Person::create([
            'full_name' => 'Jugador ' . $personId,
            'document_type_id' => 1,
            'number' => '90' . str_pad((string) $personId, 6, '0', STR_PAD_LEFT),
            'gender' => 'M',
            'status' => '1',
        ]);

        EventEditionTeamPlayer::create([
            'edition_id' => $this->edition->id,
            'team_id' => $team->id,
            'person_id' => $personId,
            'jersey_number' => (string) ($personId % 100),
            'position' => $position,
            'role_in_team' => 'Ninguno',
            'registration_date' => '2026-01-11',
        ]);
    }
}
