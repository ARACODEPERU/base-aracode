<?php

namespace Tests\Feature\Socialevents;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionPlayerSuspension;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Tests\TestCase;

/**
 * Ficha pública del equipo: puesto y totales oficiales, plantilla solo de la
 * edición, sanciones con su partido, cronología factual y penales sin sumar
 * goles. Además, nada de datos personales ni montos de sanción.
 */
class TournamentTeamProfileTest extends TestCase
{
    use RefreshDatabase;

    private EventEdition $edition;

    private EventTeam $team;

    private EventTeam $rival;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $event = EvenEvent::create(['title' => 'Campeonato de Exalumnos']);

        $this->edition = EventEdition::create([
            'event_id' => $event->id,
            'year' => 2026,
            'name' => 'Campeonato 2026',
            'start_date' => '2026-01-10',
            'regular_half_minutes' => 20,
            'regular_halves' => 2,
            'status' => 'finished',
            'landing_published' => true,
            'public_slug' => 'torneo-equipos',
        ]);

        $this->team = EventTeam::create(['name' => 'Promo 1998', 'short_name' => 'P98']);
        $this->rival = EventTeam::create(['name' => 'Promoción 2008', 'short_name' => 'P08']);

        foreach ([$this->team, $this->rival] as $team) {
            EventEditionTeam::create([
                'edition_id' => $this->edition->id,
                'team_id' => $team->id,
            ]);
        }
    }

    public function test_la_ficha_muestra_la_historia_real_del_equipo(): void
    {
        // Totales oficiales: 3 partidos, 2 victorias, 1 empate, 7 a favor, 3 en contra.
        EventEditionTeam::where('edition_id', $this->edition->id)
            ->where('team_id', $this->team->id)
            ->update([
                'matches_played' => 3,
                'matches_won' => 2,
                'matches_drawn' => 1,
                'matches_lost' => 0,
                'goals_for' => 7,
                'goals_against' => 3,
                'goal_difference' => 4,
                'points' => 7,
                'bonus_points' => 1,
                'rank' => 1,
                'is_champion' => true,
            ]);

        $playerId = $this->registerPlayer($this->team, '9', 'Delantero', 'Juan Pérez', '12345678');
        $teammateId = $this->registerPlayer($this->team, '5', 'Defensa', 'Luis Torres', '87654321');
        // Jugador del rival: no debe aparecer en la plantilla de este equipo.
        $this->registerPlayer($this->rival, '10', 'Delantero', 'Jugador Rival', '11223344');

        $league = $this->makeMatch(1, 'league', 2, 1, '2026-01-15 10:00:00');
        $final = $this->makeMatch(2, 'final', 3, 2, '2026-01-29 18:00:00');

        $this->participate($league, $playerId);
        $this->participate($league, $teammateId);
        $this->participate($final, $playerId);

        $this->stat($league, $playerId, ['goals' => 2, 'assists' => 1]);
        $this->stat($league, $teammateId, ['assists' => 1]);
        $this->stat($final, $playerId, ['goals' => 1, 'is_mvp' => true]);

        EventEditionMatchSanction::create([
            'match_id' => $league->id,
            'player_id' => $playerId,
            'type' => 'yellow',
            'minute' => 22,
            'amount_fine' => 987,
        ]);

        EventEditionPlayerSuspension::create([
            'edition_id' => $this->edition->id,
            'player_id' => $playerId,
            'type' => 'matches',
            'matches_count' => 1,
            'matches_served' => 0,
            'reason' => 'Acumulación de tarjetas',
        ]);

        EventEditionAward::create([
            'edition_id' => $this->edition->id,
            'award_key' => 'best_defense',
            'label' => 'Valla menos vencida',
            'recipient_type' => 'team',
            'team_id' => $this->team->id,
            'recipient_name' => 'Promo 1998',
            'team_name' => 'Promo 1998',
            'source' => 'manual',
            'is_published' => true,
        ]);

        $response = $this->get($this->profileUrl($this->team->id));

        $response->assertOk();
        $response->assertSee('Promo 1998');
        $response->assertSee('Campeonato de Exalumnos');
        $response->assertSee('Campeonato 2026');
        // Puesto, logros oficiales y jugadores destacados.
        $response->assertSee('1.º');
        $response->assertSee('Campeón');
        $response->assertSee('Valla menos vencida');
        $response->assertSee('Máximo goleador');
        // Totales oficiales y puntos con bonus.
        $response->assertSee('Goles a favor');
        $response->assertSee('se-pf-stat__value">7<', false);
        $response->assertSee('se-pf-stat__value">8<', false);
        // Historial con el marcador del equipo y la fase real.
        $response->assertSee('2 - 1');
        $response->assertSee('3 - 2');
        $response->assertSee('Gran final');
        // Disciplina: tarjeta asociada al partido y suspensión registrada.
        $response->assertSee('Amarilla');
        $response->assertSee('22′');
        $response->assertSee('Suspensiones');
        // Cronología factual: el debut ya fue victoria, así que no se repite
        // como hito aparte; sí aparecen el cambio de fase, la final y el título.
        $response->assertSee('Debut en el torneo');
        $response->assertSee('Llegada a Gran final');
        $response->assertSee('Final del torneo');
        $response->assertSee('Campeón de la edición');
        // Plantilla de la edición, sin jugadores del rival.
        $response->assertSee('Juan Pérez');
        $response->assertSee('Luis Torres');
        $response->assertDontSee('Jugador Rival');
        // Datos privados y montos de sanción fuera de la vista pública.
        $response->assertDontSee('12345678');
        $response->assertDontSee('987');
    }

    public function test_los_penales_se_muestran_en_el_resultado_sin_sumar_goles(): void
    {
        EventEditionTeam::where('edition_id', $this->edition->id)
            ->where('team_id', $this->team->id)
            ->update([
                'matches_played' => 1,
                'matches_won' => 1,
                'matches_drawn' => 0,
                'matches_lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 3,
                'rank' => 1,
                'is_champion' => true,
            ]);

        $match = $this->makeMatch(1, 'final', 0, 0, '2026-01-29 18:00:00', [
            ['team' => 'local', 'result' => 'goal'],
            ['team' => 'local', 'result' => 'goal'],
            ['team' => 'local', 'result' => 'goal'],
            ['team' => 'local', 'result' => 'goal'],
            ['team' => 'visitor', 'result' => 'goal'],
            ['team' => 'visitor', 'result' => 'goal'],
            ['team' => 'visitor', 'result' => 'goal'],
            ['team' => 'visitor', 'result' => 'miss'],
        ]);

        $playerId = $this->registerPlayer($this->team, '1', 'Arquero', 'Arquero Uno', '55555555');
        $this->participate($match, $playerId);
        $this->stat($match, $playerId, ['saves' => 2]);

        $response = $this->get($this->profileUrl($this->team->id));

        $response->assertOk();
        // El marcador del equipo sigue siendo 0 - 0: la tanda no se suma.
        $response->assertSee('se-tf-fixture__numbers">0 - 0<', false);
        $response->assertSee('Oficial: 0 - 0 (4 - 3 PEN.)');
        $response->assertSee('Definido por penales');
        $response->assertSee('Victoria');
    }

    public function test_las_estadisticas_de_otra_edicion_no_se_mezclan(): void
    {
        $otherEdition = EventEdition::create([
            'event_id' => $this->edition->event_id,
            'year' => 2025,
            'name' => 'Campeonato 2025',
            'start_date' => '2025-01-10',
            'status' => 'finished',
            'landing_published' => true,
            'public_slug' => 'torneo-equipos-2025',
        ]);

        $otherRival = EventTeam::create(['name' => 'Promo 2001', 'short_name' => 'P01']);

        foreach ([$this->team, $otherRival] as $team) {
            EventEditionTeam::create([
                'edition_id' => $otherEdition->id,
                'team_id' => $team->id,
            ]);
        }

        // Solo la edición anterior tiene partido y jugador propio.
        $oldMatch = EventEditionMatch::create([
            'edition_id' => $otherEdition->id,
            'team_h_id' => $this->team->id,
            'team_a_id' => $otherRival->id,
            'phase' => 'league',
            'round_number' => 1,
            'match_date' => '2025-01-15 10:00:00',
            'status' => 'finished',
            'score_h' => 5,
            'score_a' => 0,
        ]);

        $oldPlayerId = $this->registerPlayer($this->team, '7', 'Delantero', 'Jugador 2025', 'DOC-2025-A', $otherEdition);

        EventEditionMatchPlayerStat::create([
            'match_id' => $oldMatch->id,
            'player_id' => $oldPlayerId,
            'team_id' => $this->team->id,
            'goals' => 4,
            'assists' => 0,
            'is_mvp' => true,
        ]);

        $response = $this->get($this->profileUrl($this->team->id));

        $response->assertOk();
        $response->assertDontSee('Jugador 2025');
        $response->assertDontSee('Promo 2001');
        $response->assertDontSee('5 - 0');
        $response->assertSee('Todavía no hay partidos programados para este equipo en la edición.');
    }

    public function test_un_equipo_ajeno_o_una_landing_oculta_devuelven_404(): void
    {
        $outsider = EventTeam::create(['name' => 'Equipo Externo']);

        $response = $this->get($this->profileUrl($outsider->id));
        $response->assertStatus(404);
        $response->assertSee('Equipo no encontrado');
        $response->assertDontSee('SQLSTATE');

        $this->edition->forceFill(['landing_published' => false])->save();

        $this->get($this->profileUrl($this->team->id))->assertStatus(404);
    }

    // ─── Utilidades ───────────────────────────────────────────────────────

    private function profileUrl(int $teamId): string
    {
        return route('socialevents_torneos_team_profile', [
            'slug' => 'torneo-equipos',
            'teamId' => $teamId,
        ]);
    }

    private function makeMatch(
        int $round,
        string $phase,
        int $scoreHome,
        int $scoreAway,
        string $date,
        array $penalties = []
    ): EventEditionMatch {
        return EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'team_h_id' => $this->team->id,
            'team_a_id' => $this->rival->id,
            'phase' => $phase,
            'round_number' => $round,
            'match_date' => $date,
            'status' => 'finished',
            'score_h' => $scoreHome,
            'score_a' => $scoreAway,
            'penalties' => $penalties !== [] ? $penalties : null,
        ]);
    }

    private function participate(EventEditionMatch $match, int $personId): void
    {
        EventEditionMatchParticipation::create([
            'match_id' => $match->id,
            'player_id' => $personId,
            'role' => 'starter',
        ]);
    }

    private function stat(EventEditionMatch $match, int $personId, array $attributes = []): EventEditionMatchPlayerStat
    {
        return EventEditionMatchPlayerStat::create(array_merge([
            'match_id' => $match->id,
            'player_id' => $personId,
            'team_id' => $this->team->id,
            'goals' => 0,
            'assists' => 0,
            'saves' => 0,
            'minutes_played' => 40,
            'is_mvp' => false,
            'clean_sheet' => 0,
        ], $attributes));
    }

    private function registerPlayer(
        EventTeam $team,
        string $jersey,
        string $position,
        string $fullName,
        string $document,
        ?EventEdition $edition = null
    ): int {
        $edition ??= $this->edition;

        $person = Person::create([
            'full_name' => $fullName,
            'document_type_id' => 1,
            'number' => $document,
            'gender' => 'M',
            'status' => '1',
        ]);

        EventEditionTeamPlayer::create([
            'edition_id' => $edition->id,
            'team_id' => $team->id,
            'person_id' => $person->id,
            'jersey_number' => $jersey,
            'position' => $position,
            'role_in_team' => 'Ninguno',
            'registration_date' => $edition->start_date ?? '2026-01-11',
        ]);

        return (int) $person->id;
    }
}
