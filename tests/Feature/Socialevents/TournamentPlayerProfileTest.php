<?php

namespace Tests\Feature\Socialevents;

use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Tests\TestCase;

/**
 * Ficha pública del jugador: totales reales, puesto en la tabla de goleadores
 * con sus desempates, penales sin sumar al marcador y nada de datos privados.
 */
class TournamentPlayerProfileTest extends TestCase
{
    use RefreshDatabase;

    private EventEdition $edition;

    private EventTeam $homeTeam;

    private EventTeam $awayTeam;

    protected function setUp(): void
    {
        parent::setUp();

        // La vista usa @vite; en pruebas no hace falta compilar assets.
        $this->withoutVite();

        $event = EvenEvent::create(['title' => 'Campeonato de Exalumnos']);

        $this->edition = EventEdition::create([
            'event_id' => $event->id,
            'year' => 2026,
            'name' => 'Campeonato 2026',
            'start_date' => '2026-01-10',
            'regular_half_minutes' => 20,
            'regular_halves' => 2,
            'landing_published' => true,
            'public_slug' => 'torneo-prueba',
        ]);

        $this->homeTeam = EventTeam::create(['name' => 'Promo 1998', 'short_name' => 'P98']);
        $this->awayTeam = EventTeam::create(['name' => 'Promoción 2008', 'short_name' => 'P08']);

        foreach ([$this->homeTeam, $this->awayTeam] as $team) {
            EventEditionTeam::create([
                'edition_id' => $this->edition->id,
                'team_id' => $team->id,
            ]);
        }
    }

    public function test_la_ficha_muestra_el_rendimiento_real_y_no_expone_datos_privados(): void
    {
        $playerId = $this->registerPlayer($this->homeTeam, 101, 'Delantero', '9', 'Juan Pérez', '12345678');

        $first = $this->makeMatch(1, 2, 1);
        $second = $this->makeMatch(2, 0, 1);

        $this->participate($first, $playerId);
        $this->participate($second, $playerId);

        $this->stat($first, $playerId, ['goals' => 2, 'assists' => 1, 'is_mvp' => true]);
        $this->stat($second, $playerId, ['goals' => 1]);

        EventEditionMatchSanction::create([
            'match_id' => $first->id,
            'player_id' => $playerId,
            'type' => 'yellow',
            'minute' => 22,
            'amount_fine' => 987,
        ]);

        $response = $this->get($this->profileUrl($playerId));

        $response->assertOk();
        $response->assertSee('Juan Pérez');
        $response->assertSee('Promo 1998');
        $response->assertSee('Delantero');
        $response->assertSee('Partidos jugados');
        // Tarjetas: 2 partidos, 3 goles, 1 asistencia, 1 MVP y 1 sanción.
        $response->assertSee('se-pf-stat__value">2<', false);
        $response->assertSee('se-pf-stat__value">3<', false);
        $response->assertSee('se-pf-stat__value">1<', false);
        $response->assertSee('Amarilla');
        $response->assertSee('2 - 1');
        $response->assertSee('Partido a partido');

        // Datos personales y montos de sanción quedan fuera de la vista pública.
        $response->assertDontSee('12345678');
        $response->assertDontSee('987');
    }

    public function test_el_puesto_en_goleadores_respeta_menos_partidos_como_primer_desempate(): void
    {
        // Ambos con 2 goles: quien jugó menos partidos va primero.
        $manyMatches = $this->registerPlayer($this->homeTeam, 201, 'Delantero', '7', 'Jugador Dos Partidos', '20000001');
        $oneMatch = $this->registerPlayer($this->awayTeam, 202, 'Delantero', '11', 'Jugador Un Partido', '20000002');

        $first = $this->makeMatch(1, 1, 0);
        $second = $this->makeMatch(2, 1, 0);

        $this->participate($first, $manyMatches);
        $this->participate($second, $manyMatches);
        $this->stat($first, $manyMatches, ['goals' => 1, 'assists' => 1]);
        $this->stat($second, $manyMatches, ['goals' => 1]);

        $this->participate($first, $oneMatch);
        $this->stat($first, $oneMatch, ['goals' => 2]);

        $response = $this->get($this->profileUrl($manyMatches));

        $response->assertOk();
        // El jugador con menos partidos ocupa el primer lugar, así que este
        // jugador queda segundo aunque tenga los mismos goles.
        $response->assertSee('#2');
        $response->assertSeeInOrder([
            'Criterio principal del ranking',
            'Primer desempate: menos partidos',
            'Segundo desempate: más asistencias',
        ]);
    }

    public function test_los_penales_aparecen_en_el_marcador_sin_sumar_goles_al_jugador(): void
    {
        $playerId = $this->registerPlayer($this->homeTeam, 301, '', '1', 'Arquero Penales', '30000001');

        $match = $this->makeMatch(1, 0, 0, [
            ['team' => 'local', 'result' => 'goal'],
            ['team' => 'local', 'result' => 'goal'],
            ['team' => 'local', 'result' => 'goal'],
            ['team' => 'local', 'result' => 'goal'],
            ['team' => 'visitor', 'result' => 'goal'],
            ['team' => 'visitor', 'result' => 'goal'],
            ['team' => 'visitor', 'result' => 'goal'],
            ['team' => 'visitor', 'result' => 'miss'],
        ]);

        $this->participate($match, $playerId);
        $this->stat($match, $playerId, ['saves' => 2]);

        $response = $this->get($this->profileUrl($playerId));

        $response->assertOk();
        $response->assertSee('0 - 0 (4 - 3 PEN.)');
        $response->assertSee('Penales');
    }

    public function test_un_jugador_fuera_de_la_edicion_recibe_una_pagina_amigable(): void
    {
        $response = $this->get($this->profileUrl(999));

        $response->assertStatus(404);
        $response->assertSee('Jugador no encontrado');
        $response->assertDontSee('SQLSTATE');
    }

    public function test_la_ficha_no_se_publica_si_la_landing_esta_oculta(): void
    {
        $playerId = $this->registerPlayer($this->homeTeam, 401, 'Delantero', '9', 'Jugador Oculto', '40000001');
        $match = $this->makeMatch(1, 1, 0);
        $this->participate($match, $playerId);
        $this->stat($match, $playerId, ['goals' => 1]);

        $this->edition->forceFill(['landing_published' => false])->save();

        $this->get($this->profileUrl($playerId))->assertStatus(404);
    }

    // ─── Utilidades ───────────────────────────────────────────────────────

    private function profileUrl(int $playerId): string
    {
        return route('socialevents_torneos_player_profile', [
            'slug' => 'torneo-prueba',
            'playerId' => $playerId,
        ]);
    }

    private function makeMatch(
        int $round,
        int $scoreHome,
        int $scoreAway,
        array $penalties = []
    ): EventEditionMatch {
        return EventEditionMatch::create([
            'edition_id' => $this->edition->id,
            'team_h_id' => $this->homeTeam->id,
            'team_a_id' => $this->awayTeam->id,
            'phase' => 'league',
            'round_number' => $round,
            'match_date' => $round === 1 ? '2026-01-15 10:00:00' : '2026-01-22 10:00:00',
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
            'team_id' => $this->homeTeam->id,
            'goals' => 0,
            'assists' => 0,
            'saves' => 0,
            'minutes_played' => 40,
            'is_mvp' => false,
            'clean_sheet' => 0,
        ], $attributes));
    }

    /**
     * Crea la persona y devuelve su id real: person_id es la clave que usan
     * las estadísticas, no un número arbitrario.
     */
    private function registerPlayer(
        EventTeam $team,
        int $unusedPersonId,
        string $position,
        string $jersey,
        string $fullName,
        string $document
    ): int {
        $person = Person::create([
            'full_name' => $fullName,
            'document_type_id' => 1,
            'number' => $document,
            'gender' => 'M',
            'status' => '1',
        ]);

        EventEditionTeamPlayer::create([
            'edition_id' => $this->edition->id,
            'team_id' => $team->id,
            'person_id' => $person->id,
            'jersey_number' => $jersey,
            'position' => $position,
            'role_in_team' => 'Ninguno',
            'registration_date' => '2026-01-11',
        ]);

        return (int) $person->id;
    }
}
