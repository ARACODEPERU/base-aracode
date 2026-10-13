<?php

namespace Tests\Feature\Socialevents;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchGoal;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSave;
use Modules\Socialevents\Entities\EventEditionMatchSubstitution;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Services\MatchPerformanceService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Desempeño individual: cambios (quién entra, quién sale), minutos jugados y
 * prórroga opcional. Cubre el cálculo (servicio) y el guardado real del acta
 * desde la app móvil.
 */
class MatchPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private EvenEvent $event;

    private EventEdition $edition;

    private EventTeam $homeTeam;

    private EventTeam $awayTeam;

    private MatchPerformanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // La vista del acta se resuelve sin depender de un build de Vite.
        $this->withoutVite();

        $this->actingAsAdmin();
        $this->service = app(MatchPerformanceService::class);

        $this->event = EvenEvent::create(['title' => 'Evento de prueba']);

        // Edición 2x20: el preset del torneo actual. Nada de esto es fijo en código.
        $this->edition = EventEdition::create([
            'event_id' => $this->event->id,
            'year' => 2026,
            'name' => 'Edición de prueba',
            'start_date' => '2026-01-10',
            'regular_half_minutes' => 20,
            'regular_halves' => 2,
            'extra_half_minutes' => 10,
            'extra_halves' => 2,
            'extra_time_enabled' => true,
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

    // ─── Resolución de tiempos ────────────────────────────────────────────

    public function test_resuelve_los_tiempos_desde_la_edicion(): void
    {
        $match = $this->makeMatch();

        $timing = $this->service->resolvedTiming($match);

        $this->assertSame(20, $timing['half_minutes']);
        $this->assertSame(2, $timing['halves']);
        $this->assertSame(40, $timing['regular_total']);
        $this->assertSame(40, $timing['duration']);
        $this->assertFalse($timing['extra_time_played']);
    }

    public function test_el_override_del_partido_gana_sobre_la_edicion(): void
    {
        // El partido juega 30 por tiempo aunque la edición diga 20.
        $match = $this->makeMatch(['half_minutes_override' => 30]);

        $timing = $this->service->resolvedTiming($match);

        $this->assertSame(30, $timing['half_minutes']);
        $this->assertSame(60, $timing['duration']);
    }

    public function test_la_edicion_puede_jugar_2x45(): void
    {
        $this->edition->update(['regular_half_minutes' => 45]);

        $timing = $this->service->resolvedTiming($this->makeMatch());

        $this->assertSame(45, $timing['half_minutes']);
        $this->assertSame(90, $timing['duration']);
    }

    public function test_suma_la_prorroga_cuando_se_jugo(): void
    {
        $match = $this->makeMatch(['extra_time_played' => true]);

        $timing = $this->service->resolvedTiming($match);

        $this->assertTrue($timing['extra_time_played']);
        $this->assertSame(20, $timing['extra_total']);
        $this->assertSame(60, $timing['duration']);
    }

    // ─── Cálculo de minutos ───────────────────────────────────────────────

    public function test_titular_sin_cambios_juega_todo_el_partido(): void
    {
        $timing = $this->service->resolvedTiming($this->makeMatch());

        $minutes = $this->service->computeMinutes($timing, [10 => 'starter', 11 => 'substitute'], []);

        $this->assertSame(40, $minutes[10]);
        $this->assertSame(0, $minutes[11]);
    }

    public function test_calcula_los_minutos_de_quien_sale_y_quien_entra(): void
    {
        $match = $this->makeMatch();
        $timing = $this->service->resolvedTiming($match);

        $subs = $this->service->prepareSubstitutions([
            ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'second', 'minute_in_period' => 5],
        ], [
            'team_id' => $this->homeTeam->id,
            'edition_id' => $this->edition->id,
            'roster' => [10, 11],
            'roles' => [10 => 'starter', 11 => 'substitute'],
            'timing' => $timing,
        ]);

        // 2do tiempo, minuto 5 → minuto absoluto 25.
        $this->assertSame(25, $subs[0]['minute']);

        $minutes = $this->service->computeMinutes($timing, [10 => 'starter', 11 => 'substitute'], $subs);

        $this->assertSame(25, $minutes[10]);
        $this->assertSame(15, $minutes[11]);
    }

    public function test_en_la_prorroga_los_minutos_llegan_hasta_60(): void
    {
        $match = $this->makeMatch(['extra_time_played' => true]);
        $timing = $this->service->resolvedTiming($match);

        $subs = $this->service->prepareSubstitutions([
            ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'extra_first', 'minute_in_period' => 5],
        ], [
            'team_id' => $this->homeTeam->id,
            'edition_id' => $this->edition->id,
            'roster' => [10, 11],
            'roles' => [10 => 'starter', 11 => 'substitute'],
            'timing' => $timing,
        ]);

        // 1er suplementario, minuto 5 → minuto absoluto 45 de un partido de 60.
        $this->assertSame(45, $subs[0]['minute']);

        $minutes = $this->service->computeMinutes($timing, [10 => 'starter', 11 => 'substitute'], $subs);

        $this->assertSame(45, $minutes[10]);
        $this->assertSame(15, $minutes[11]);
    }

    public function test_un_suplente_que_entra_y_sale_solo_juega_su_ventana(): void
    {
        $match = $this->makeMatch();
        $timing = $this->service->resolvedTiming($match);

        $subs = $this->service->prepareSubstitutions([
            ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'first', 'minute_in_period' => 10],
            ['player_out_id' => 11, 'player_in_id' => 12, 'period' => 'second', 'minute_in_period' => 0],
        ], [
            'team_id' => $this->homeTeam->id,
            'edition_id' => $this->edition->id,
            'roster' => [10, 11, 12],
            'roles' => [10 => 'starter', 11 => 'substitute', 12 => 'substitute'],
            'timing' => $timing,
        ]);

        $minutes = $this->service->computeMinutes(
            $timing,
            [10 => 'starter', 11 => 'substitute', 12 => 'substitute'],
            $subs
        );

        $this->assertSame(10, $minutes[10]);   // sale en el minuto 10
        $this->assertSame(10, $minutes[11]);   // entra en 10, sale en 20
        $this->assertSame(20, $minutes[12]);   // entra en 20 y termina
    }

    // ─── Validaciones ─────────────────────────────────────────────────────

    public function test_rechaza_un_cambio_donde_sale_y_entra_el_mismo_jugador(): void
    {
        $this->expectException(ValidationException::class);

        $this->prepareFor(10, 10);
    }

    public function test_rechaza_un_jugador_que_no_pertenece_al_equipo(): void
    {
        $this->expectException(ValidationException::class);

        $this->prepareFor(10, 99);
    }

    public function test_rechaza_cambios_de_la_prorroga_si_no_se_jugo(): void
    {
        $this->expectException(ValidationException::class);

        $this->prepareFor(10, 11, 'extra_first', 5);
    }

    public function test_rechaza_un_minuto_fuera_del_tiempo_jugado(): void
    {
        $this->expectException(ValidationException::class);

        // El primer tiempo dura 20: el minuto 25 no existe ahí.
        $this->prepareFor(10, 11, 'first', 25);
    }

    public function test_rechaza_un_jugador_que_ya_salio_antes(): void
    {
        $this->expectException(ValidationException::class);

        $match = $this->makeMatch();
        $timing = $this->service->resolvedTiming($match);

        $this->service->prepareSubstitutions([
            ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'first', 'minute_in_period' => 5],
            ['player_out_id' => 10, 'player_in_id' => 12, 'period' => 'second', 'minute_in_period' => 5],
        ], [
            'team_id' => $this->homeTeam->id,
            'edition_id' => $this->edition->id,
            'roster' => [10, 11, 12],
            'roles' => [10 => 'starter', 11 => 'substitute', 12 => 'substitute'],
            'timing' => $timing,
        ]);
    }

    public function test_descarta_los_cambios_de_jugadores_que_ya_no_estan_en_el_acta(): void
    {
        $match = $this->makeMatch();
        $timing = $this->service->resolvedTiming($match);

        // El jugador 11 se quitó de la alineación: su cambio desaparece solo.
        $subs = $this->service->prepareSubstitutions([
            ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'first', 'minute_in_period' => 5],
        ], [
            'team_id' => $this->homeTeam->id,
            'edition_id' => $this->edition->id,
            'roster' => [10, 11],
            'roles' => [10 => 'starter'],
            'timing' => $timing,
        ]);

        $this->assertSame([], $subs);
    }

    // ─── Guardado real del acta (API del móvil) ────────────────────────────

    public function test_guarda_el_acta_con_cambios_y_calcula_los_minutos(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->putJson($this->resultUrl($match), [
            'score_h' => 2,
            'score_a' => 1,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'substitutions_h' => [
                [
                    'player_out_id' => 10,
                    'player_in_id' => 11,
                    'period' => 'second',
                    'minute_in_period' => 5,
                    'reason' => 'tactical',
                ],
            ],
            'substitutions_a' => [],
        ]);

        $response->assertOk();

        // Minuto absoluto del cambio: 20 + 5 = 25
        $this->assertDatabaseHas('event_edition_match_substitutions', [
            'match_id' => $match->id,
            'player_out_id' => 10,
            'player_in_id' => 11,
            'minute' => 25,
            'period' => 'second',
        ]);

        $this->assertSame(25, $this->minutesOf($match->id, 10));
        $this->assertSame(15, $this->minutesOf($match->id, 11));

        // Quien no se movió de la cancha juega los 40 completos.
        $this->assertSame(40, $this->minutesOf($match->id, 12));
        // El suplente que nunca entró no suma minutos.
        $this->assertSame(0, $this->minutesOf($match->id, 13));
    }

    public function test_guarda_la_prorroga_y_el_marcador_reglamentario(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->putJson($this->resultUrl($match), [
            'score_h' => 2,
            'score_a' => 1,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'extra_time_played' => true,
            'score_regular_h' => 1,
            'score_regular_a' => 1,
            'substitutions_h' => [
                [
                    'player_out_id' => 10,
                    'player_in_id' => 11,
                    'period' => 'extra_first',
                    'minute_in_period' => 5,
                ],
            ],
        ])->assertOk();

        $match->refresh();

        $this->assertTrue((bool) $match->extra_time_played);
        $this->assertSame(1, $match->score_regular_h);
        $this->assertSame(1, $match->score_regular_a);

        // Con prórroga la duración efectiva es 60: 45 jugados y 15 para el que entró.
        $this->assertSame(45, $this->minutesOf($match->id, 10));
        $this->assertSame(15, $this->minutesOf($match->id, 11));
    }

    public function test_corregir_el_acta_no_duplica_cambios(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $payload = [
            'score_h' => 1,
            'score_a' => 0,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'substitutions_h' => [
                ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'second', 'minute_in_period' => 5],
            ],
        ];

        $this->putJson($this->resultUrl($match), $payload)->assertOk();
        $this->putJson($this->resultUrl($match), $payload)->assertOk();

        $this->assertSame(1, EventEditionMatchSubstitution::where('match_id', $match->id)->count());
    }

    public function test_quitarlo_de_la_alineacion_borra_sus_minutos_y_sus_cambios(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->putJson($this->resultUrl($match), [
            'score_h' => 1,
            'score_a' => 0,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'substitutions_h' => [
                ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'second', 'minute_in_period' => 5],
            ],
        ])->assertOk();

        // El jugador 11 sale del acta (no participó) y el cambio desaparece con él.
        $withoutPlayer = array_map(function ($player) {
            if ($player['person_id'] === 11) {
                $player['match_role'] = null;
            }

            return $player;
        }, $homePlayers);

        $this->putJson($this->resultUrl($match), [
            'score_h' => 1,
            'score_a' => 0,
            'edition_id' => $this->edition->id,
            'players_h' => $withoutPlayer,
            'players_a' => $awayPlayers,
            'substitutions_h' => [
                ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'second', 'minute_in_period' => 5],
            ],
        ])->assertOk();

        $this->assertSame(0, EventEditionMatchSubstitution::where('match_id', $match->id)->count());
        $this->assertNull(
            EventEditionMatchPlayerStat::where('match_id', $match->id)->where('player_id', 11)->first()
        );
    }

    public function test_respeta_los_minutos_escritos_a_mano(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $manual = array_map(function ($player) {
            if ($player['person_id'] === 10) {
                $player['minutes_played'] = 33;
                $player['minutes_manual'] = true;
            }

            return $player;
        }, $homePlayers);

        $this->putJson($this->resultUrl($match), [
            'score_h' => 0,
            'score_a' => 0,
            'edition_id' => $this->edition->id,
            'players_h' => $manual,
            'players_a' => $awayPlayers,
            'substitutions_h' => [
                ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'second', 'minute_in_period' => 5],
            ],
        ])->assertOk();

        $this->assertSame(33, $this->minutesOf($match->id, 10));
    }

    public function test_sin_bloque_de_cambios_el_acta_conserva_los_minutos_del_formulario(): void
    {
        // El móvil todavía no manda `substitutions_h/a`: sus minutos escritos a
        // mano (sin la marca de manual) no deben ser pisados por el cálculo.
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $homePlayers[0]['minutes_played'] = 33; // jugador 10

        $this->putJson($this->resultUrl($match), [
            'score_h' => 0,
            'score_a' => 0,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
        ])->assertOk();

        $this->assertSame(33, $this->minutesOf($match->id, 10));
    }

    public function test_con_bloque_de_cambios_vacio_el_acta_recalcula_los_minutos(): void
    {
        // La web siempre manda el bloque: un titular juega el partido completo
        // aunque el formulario venga con el contador viejo.
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $homePlayers[0]['minutes_played'] = 7; // jugador 10, titular

        $this->putJson($this->resultUrl($match), [
            'score_h' => 0,
            'score_a' => 0,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'substitutions_h' => [],
            'substitutions_a' => [],
        ])->assertOk();

        $this->assertSame(40, $this->minutesOf($match->id, 10));
    }

    public function test_rechaza_un_cambio_invalido_sin_escribir_nada(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->putJson($this->resultUrl($match), [
            'score_h' => 1,
            'score_a' => 0,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'substitutions_h' => [
                ['player_out_id' => 10, 'player_in_id' => 10, 'period' => 'second', 'minute_in_period' => 5],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, EventEditionMatchSubstitution::where('match_id', $match->id)->count());
        $this->assertSame(0, EventEditionMatchPlayerStat::where('match_id', $match->id)->count());
    }

    // ─── Guardado del acta desde la web (el modal de resultados) ───────────

    public function test_guarda_el_acta_desde_la_web_con_cambios_y_minutos(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 3,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'substitutions_h' => [
                ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'second', 'minute_in_period' => 10],
            ],
        ]);

        // El guardado del acta responde 200 (no redirige) y sin errores de sesión.
        $response->assertOk();
        $response->assertSessionHasNoErrors();

        // 20 + 10 = minuto 30 del partido.
        $this->assertDatabaseHas('event_edition_match_substitutions', [
            'match_id' => $match->id,
            'player_out_id' => 10,
            'minute' => 30,
        ]);

        $this->assertSame(30, $this->minutesOf($match->id, 10));
        $this->assertSame(10, $this->minutesOf($match->id, 11));
        $this->assertSame('finished', $match->fresh()->status);
    }

    public function test_la_web_rechaza_un_cambio_invalido_sin_guardar_el_acta(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'substitutions_h' => [
                // Minuto 25 del primer tiempo de 20: no existe.
                ['player_out_id' => 10, 'player_in_id' => 11, 'period' => 'first', 'minute_in_period' => 25],
            ],
        ]);

        $response->assertSessionHasErrors('substitutions_h.0');

        $this->assertSame(0, EventEditionMatchSubstitution::where('match_id', $match->id)->count());
        $this->assertSame('pending', $match->fresh()->status);
    }

    // ─── Goles con su tiempo (reglamentario vs. prórroga) ──────────────────

    public function test_guarda_los_goles_con_su_tiempo_y_minuto(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 2,
            'score_a' => 1,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            // Un gol en el primer tiempo (minuto 10) y otro en el segundo (minuto 5).
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 10],
                ['player_id' => 12, 'period' => 'second', 'minute_in_period' => 5],
            ],
            'goals_a' => [
                ['player_id' => 20, 'period' => 'second', 'minute_in_period' => 8],
            ],
        ]);

        $response->assertOk();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('event_edition_match_goals', [
            'match_id' => $match->id,
            'player_id' => 10,
            'period' => 'first',
            'minute_in_period' => 10,
            'minute' => 10,
        ]);

        // 20 + 5 = minuto 25 del partido.
        $this->assertDatabaseHas('event_edition_match_goals', [
            'match_id' => $match->id,
            'player_id' => 12,
            'period' => 'second',
            'minute_in_period' => 5,
            'minute' => 25,
        ]);

        // 20 + 8 = minuto 28 del partido.
        $this->assertDatabaseHas('event_edition_match_goals', [
            'match_id' => $match->id,
            'player_id' => 20,
            'period' => 'second',
            'minute' => 28,
        ]);

        // El detalle manda sobre el contador del acta (que venía en cero).
        $this->assertSame(1, $this->goalsOf($match->id, 10));
        $this->assertSame(1, $this->goalsOf($match->id, 12));
        $this->assertSame(1, $this->goalsOf($match->id, 20));
        $this->assertSame(0, $this->goalsOf($match->id, 11));
    }

    public function test_separa_los_goles_del_reglamentario_de_los_de_la_prorroga(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // El caso real: 1-1 al terminar el reglamentario y 2-2 con la prórroga.
        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 2,
            'score_a' => 2,
            'extra_time_played' => true,
            'score_regular_h' => 1,
            'score_regular_a' => 1,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 12],
                ['player_id' => 12, 'period' => 'extra_first', 'minute_in_period' => 4],
            ],
            'goals_a' => [
                ['player_id' => 20, 'period' => 'second', 'minute_in_period' => 15],
            ],
        ])->assertOk();

        // El reporte puede contar cada tiempo por separado.
        $this->assertSame(1, EventEditionMatchGoal::where('match_id', $match->id)->where('period', 'first')->count());
        $this->assertSame(1, EventEditionMatchGoal::where('match_id', $match->id)->where('period', 'second')->count());
        $this->assertSame(1, EventEditionMatchGoal::where('match_id', $match->id)->where('period', 'extra_first')->count());

        // El gol del alargue se ubica después de los 40 reglamentarios: 40 + 4.
        $extraGoal = EventEditionMatchGoal::where('match_id', $match->id)
            ->where('period', 'extra_first')
            ->first();

        $this->assertSame(44, (int) $extraGoal->minute);
    }

    public function test_un_gol_de_la_prorroga_exige_que_el_partido_la_jugara(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'extra_first', 'minute_in_period' => 4],
            ],
        ]);

        $response->assertSessionHasErrors('goals_h.0');

        $this->assertSame(0, EventEditionMatchGoal::where('match_id', $match->id)->count());
        $this->assertSame('pending', $match->fresh()->status);
    }

    public function test_un_gol_sin_tiempo_registrado_se_guarda_igual(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // Las actas viejas no tienen minuto: el gol no se pierde ni se inventa.
        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => null, 'minute_in_period' => null],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('event_edition_match_goals', [
            'match_id' => $match->id,
            'player_id' => 10,
            'period' => null,
            'minute' => null,
        ]);

        $this->assertSame(1, $this->goalsOf($match->id, 10));
    }

    public function test_corregir_el_acta_no_duplica_los_goles(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $payload = [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'second', 'minute_in_period' => 5],
            ],
        ];

        $this->post(route('even_edition_match_score_update'), $payload)->assertOk();
        $this->post(route('even_edition_match_score_update'), $payload)->assertOk();

        $this->assertSame(1, EventEditionMatchGoal::where('match_id', $match->id)->count());
        $this->assertSame(1, $this->goalsOf($match->id, 10));
    }

    public function test_el_movil_guarda_los_goles_y_los_devuelve(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->putJson($this->resultUrl($match), [
            'score_h' => 1,
            'score_a' => 1,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 7],
            ],
            'goals_a' => [
                ['player_id' => 20, 'period' => 'first', 'minute_in_period' => 9],
            ],
        ])->assertOk();

        $this->assertSame(2, EventEditionMatchGoal::where('match_id', $match->id)->count());

        // Lo que el móvil guarda se le devuelve al abrir el acta otra vez.
        $this->getJson("/api/socialevents/v1/admin/matches/{$match->id}/players")
            ->assertOk()
            ->assertJsonPath('data.goals.0.player_id', 10)
            ->assertJsonPath('data.goals.0.period', 'first')
            ->assertJsonPath('data.goals.0.minute', 7)
            ->assertJsonPath('data.goals.1.player_id', 20);
    }

    public function test_el_movil_conserva_quien_pateo_los_penales(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->putJson($this->resultUrl($match), [
            'score_h' => 2,
            'score_a' => 2,
            'edition_id' => $this->edition->id,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'penalty_rounds' => 5,
            'penalties' => [
                ['team' => 'local', 'round' => 1, 'kickOrder' => 1, 'kicker' => 10, 'goalkeeper' => 20, 'result' => 'goal'],
                ['team' => 'visitor', 'round' => 1, 'kickOrder' => 2, 'kicker' => 20, 'goalkeeper' => 10, 'result' => 'saved'],
            ],
        ])->assertOk();

        $penalties = $match->fresh()->penalties;

        $this->assertSame(10, (int) $penalties[0]['kicker']);
        $this->assertSame(20, (int) $penalties[0]['goalkeeper']);
        $this->assertSame(20, (int) $penalties[1]['kicker']);
        $this->assertSame('saved', $penalties[1]['result']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Arma el contexto de un solo cambio del equipo local para probar validaciones.
     */
    // ─── Detalle del gol: tipo y asistencia ───────────────────────────────

    public function test_guarda_el_tipo_de_gol_y_el_asistente(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                [
                    'player_id' => 10,
                    'period' => 'second',
                    'minute_in_period' => 7,
                    'goal_type' => 'header',
                    'assist_player_id' => 12,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('event_edition_match_goals', [
            'match_id' => $match->id,
            'player_id' => 10,
            'goal_type' => 'header',
            'assist_player_id' => 12,
        ]);

        // Lo que devuelve el detalle para la vista y los reportes.
        $goal = collect($this->service->goalsForMatch($match->id))->firstWhere('player_id', 10);

        $this->assertSame('header', $goal['goal_type']);
        $this->assertSame('Cabeza', $goal['goal_type_label']);
        $this->assertSame(12, (int) $goal['assist_player_id']);
    }

    public function test_un_gol_en_propia_puerta_no_cuenta_como_gol_del_autor(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                [
                    'player_id' => 10,
                    'period' => 'first',
                    'minute_in_period' => 12,
                    'goal_type' => 'normal',
                    'is_own_goal' => true,
                ],
            ],
        ])->assertOk();

        // El gol queda registrado en el detalle (es un gol del partido)...
        $this->assertDatabaseHas('event_edition_match_goals', [
            'match_id' => $match->id,
            'player_id' => 10,
            'is_own_goal' => true,
        ]);

        // ...pero no se le acredita a quien lo metió en su arco: ni gol ni
        // asistencia (nadie lo habilitó).
        $this->assertSame(0, $this->goalsOf($match->id, 10));
        $this->assertSame(0, $this->assistsOf($match->id, 10));
    }

    public function test_los_goles_del_detalle_siguen_sumando_sin_ser_en_propia_puerta(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 2,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 3, 'goal_type' => 'normal'],
                ['player_id' => 10, 'period' => 'second', 'minute_in_period' => 4, 'goal_type' => 'penalty'],
                ['player_id' => 12, 'period' => 'second', 'minute_in_period' => 9, 'goal_type' => 'normal', 'is_own_goal' => true],
            ],
        ])->assertOk();

        $this->assertSame(2, $this->goalsOf($match->id, 10));
        $this->assertSame(0, $this->goalsOf($match->id, 12));
    }

    public function test_un_gol_sin_tipo_registrado_es_jugada(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 3],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('event_edition_match_goals', [
            'match_id' => $match->id,
            'player_id' => 10,
            'goal_type' => 'normal',
        ]);
    }

    public function test_rechaza_un_tipo_de_gol_desconocido(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 3, 'goal_type' => 'chilena'],
            ],
        ]);

        $response->assertSessionHasErrors('goals_h.0');

        $this->assertSame(0, EventEditionMatchGoal::where('match_id', $match->id)->count());
        $this->assertSame('pending', $match->fresh()->status);
    }

    public function test_rechaza_un_asistente_de_otro_equipo(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 3, 'assist_player_id' => 20],
            ],
        ]);

        $response->assertSessionHasErrors('goals_h.0');

        $this->assertSame(0, EventEditionMatchGoal::where('match_id', $match->id)->count());
    }

    public function test_rechaza_que_el_asistente_sea_el_mismo_autor(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 3, 'assist_player_id' => 10],
            ],
        ]);

        $response->assertSessionHasErrors('goals_h.0');
    }

    public function test_descarta_la_asistencia_de_un_jugador_que_ya_no_esta_en_el_acta(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // 13 queda fuera de la alineación enviada: su asistencia se descarta,
        // pero el gol no se pierde por un dato accesorio.
        $actaHome = array_values(array_filter(
            $homePlayers,
            fn ($player) => in_array($player['person_id'], [10, 12], true)
        ));

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $actaHome,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 3, 'assist_player_id' => 13],
            ],
        ])->assertOk();

        $goal = EventEditionMatchGoal::where('match_id', $match->id)->first();

        $this->assertNotNull($goal);
        $this->assertNull($goal->assist_player_id);
    }

    // ─── Asistencias: salen del detalle, no de un contador aparte ─────────

    public function test_las_asistencias_se_leen_del_asistente_registrado_en_cada_gol(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // El acta trae un contador viejo de asistencias: con detalle de goles
        // presente, el asistente de cada gol es el que manda.
        $homePlayers[0]['assists'] = 5; // jugador 10

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 2,
            'score_a' => 1,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 10, 'assist_player_id' => 11],
                ['player_id' => 12, 'period' => 'second', 'minute_in_period' => 5, 'assist_player_id' => 11],
            ],
            'goals_a' => [
                ['player_id' => 20, 'period' => 'second', 'minute_in_period' => 8],
            ],
        ])->assertOk();

        // El jugador 11 asistió los dos goles del local.
        $this->assertSame(2, $this->assistsOf($match->id, 11));

        // El contador viejo del acta se descarta y quien no asistió queda en cero.
        $this->assertSame(0, $this->assistsOf($match->id, 10));
        $this->assertSame(0, $this->assistsOf($match->id, 12));
        $this->assertSame(0, $this->assistsOf($match->id, 20));
    }

    public function test_sin_detalle_de_goles_el_acta_conserva_sus_asistencias(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // Un acta antigua (app sin detalle) sigue guardando lo que envía.
        $homePlayers[0]['assists'] = 3; // jugador 10

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
        ])->assertOk();

        $this->assertSame(3, $this->assistsOf($match->id, 10));
    }

    public function test_corregir_el_detalle_de_goles_recalcula_las_asistencias(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $acta = [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
        ];

        $this->post(route('even_edition_match_score_update'), $acta + [
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 4, 'assist_player_id' => 12],
            ],
        ])->assertOk();

        $this->assertSame(1, $this->assistsOf($match->id, 12));

        // Se corrige el detalle: ahora asiste el 11 y el 12 vuelve a cero.
        $this->post(route('even_edition_match_score_update'), $acta + [
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 4, 'assist_player_id' => 11],
            ],
        ])->assertOk();

        $this->assertSame(1, $this->assistsOf($match->id, 11));
        $this->assertSame(0, $this->assistsOf($match->id, 12));
    }

    // ─── Resumen para los reportes ────────────────────────────────────────

    public function test_el_resumen_separa_reglamentario_alargue_y_penales(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // El caso del reporte: 1-1 al terminar el reglamentario, 2-2 con el
        // alargue y 4-5 en la tanda de penales.
        $penalties = [];

        foreach (range(1, 5) as $round) {
            $penalties[] = [
                'team' => 'local', 'round' => $round, 'kicker' => 10,
                'result' => $round <= 4 ? 'goal' : 'miss', 'goalkeeper' => 20, 'kickOrder' => $round,
            ];
        }

        foreach (range(1, 5) as $round) {
            $penalties[] = [
                'team' => 'visitor', 'round' => $round, 'kicker' => 20,
                'result' => 'goal', 'goalkeeper' => 10, 'kickOrder' => $round,
            ];
        }

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 2,
            'score_a' => 2,
            'extra_time_played' => true,
            'score_regular_h' => 1,
            'score_regular_a' => 1,
            'penalty_rounds' => 5,
            'penalties' => $penalties,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 12],
                ['player_id' => 12, 'period' => 'extra_first', 'minute_in_period' => 4],
            ],
            'goals_a' => [
                ['player_id' => 20, 'period' => 'second', 'minute_in_period' => 15],
                ['player_id' => 21, 'period' => 'extra_second', 'minute_in_period' => 2],
            ],
        ])->assertOk();

        $breakdown = $this->service->goalBreakdown(
            $this->service->goalsForMatch($match->id),
            $match->fresh()->penalties
        );

        $this->assertSame(2, $breakdown['regulation']); // 1 de cada equipo
        $this->assertSame(2, $breakdown['extra']);      // 1 de cada equipo
        $this->assertSame(9, $breakdown['penalties']);  // 4 + 5 anotados de la tanda
        $this->assertSame(0, $breakdown['unlocated']);
        $this->assertSame(4, $breakdown['total']);
    }

    public function test_un_gol_sin_tiempo_no_se_reparte_entre_los_tiempos(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 2,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 5],
                ['player_id' => 12, 'period' => null],
            ],
        ])->assertOk();

        $breakdown = $this->service->goalBreakdown($this->service->goalsForMatch($match->id));

        $this->assertSame(1, $breakdown['regulation']);
        $this->assertSame(0, $breakdown['extra']);
        $this->assertSame(1, $breakdown['unlocated']);
        $this->assertSame(2, $breakdown['total']);
    }

    // ─── Vista dedicada del acta ──────────────────────────────────────────

    public function test_la_vista_dedicada_del_acta_se_abre_con_el_partido(): void
    {
        [$match] = $this->scenarioWithPlayers();

        $this->get(route('even_ediciones_partido_resultado', [$this->edition->id, $match->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Las páginas de los módulos se resuelven por el glob de Vite, no
                // por el view finder del servidor: se compara el nombre sin pedir
                // que la ruta exista en disco.
                ->component('Socialevents::Editions/MatchScore', false)
                ->where('match.id', $match->id)
                ->has('edition')
                ->has('match.equipolocal.players')
                ->has('match.timing')
            );
    }

    public function test_la_vista_dedicada_trae_los_goles_con_tipo_y_asistencia(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'goals_h' => [
                [
                    'player_id' => 10,
                    'period' => 'second',
                    'minute_in_period' => 7,
                    'goal_type' => 'header',
                    'assist_player_id' => 12,
                ],
            ],
        ])->assertOk();

        $this->get(route('even_ediciones_partido_resultado', [$this->edition->id, $match->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Socialevents::Editions/MatchScore', false)
                ->where('match.goals.0.goal_type', 'header')
                ->where('match.goals.0.goal_type_label', 'Cabeza')
                ->where('match.goals.0.assist_player_id', 12)
                ->where('match.goals.0.minute', 27)
            );
    }

    // ─── Detalle de la atajada: cómo fue, quién la pateó y en qué tiempo ────

    public function test_guarda_el_detalle_de_la_atajada_con_tipo_pateador_y_tiempo(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'saves_h' => [
                [
                    'player_id' => 10,
                    'period' => 'second',
                    'minute_in_period' => 7,
                    'save_type' => 'one_on_one',
                    'is_penalty' => false,
                    'shooter_player_id' => 20,
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('event_edition_match_saves', [
            'match_id' => $match->id,
            'player_id' => 10,
            'save_type' => 'one_on_one',
            'shooter_player_id' => 20,
            'period' => 'second',
            'minute_in_period' => 7,
        ]);

        // El minuto absoluto se calcula desde el tiempo elegido (20 + 7).
        $this->assertSame(27, (int) EventEditionMatchSave::where('match_id', $match->id)->first()->minute);

        // Lo que devuelve el detalle para la vista y los reportes.
        $save = collect($this->service->savesForMatch($match->id))->firstWhere('player_id', 10);

        $this->assertSame('one_on_one', $save['save_type']);
        $this->assertSame('Uno contra uno', $save['save_type_label']);
        $this->assertSame(20, (int) $save['shooter_player_id']);
    }

    public function test_una_atajada_sin_tipo_registrado_es_un_tiro(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'saves_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 3],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('event_edition_match_saves', [
            'match_id' => $match->id,
            'player_id' => 10,
            'save_type' => 'shot',
            'is_penalty' => false,
        ]);
    }

    public function test_las_atajadas_del_detalle_mandan_sobre_el_contador_del_acta(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // El acta trae el contador viejo del arquero: con detalle presente, el
        // detalle es la única fuente.
        $homePlayers[0]['saves'] = 7; // jugador 10

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'saves_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 2, 'save_type' => 'header'],
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 9, 'save_type' => 'olympic'],
            ],
        ])->assertOk();

        $this->assertSame(2, $this->savesOf($match->id, 10));
        // Quien no atajó queda en cero, aunque el acta no lo diga.
        $this->assertSame(0, $this->savesOf($match->id, 12));
    }

    public function test_sin_detalle_de_atajadas_el_acta_conserva_su_contador(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // El guardado de la app móvil (sin detalle) no debe perder sus atajadas.
        $homePlayers[0]['saves'] = 3; // jugador 10

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
        ])->assertOk();

        $this->assertSame(3, $this->savesOf($match->id, 10));
    }

    public function test_la_ronda_de_penales_se_registra_sin_minuto_de_juego(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 1,
            'score_a' => 1,
            'has_penalties' => true,
            'penalty_rounds' => 5,
            'penalties' => [
                ['team' => 'visitor', 'round' => 1, 'kickOrder' => 1, 'kicker' => 20, 'goalkeeper' => 10, 'result' => 'saved'],
            ],
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'saves_h' => [
                [
                    'player_id' => 10,
                    'period' => 'penalty_shootout',
                    'minute_in_period' => 40,
                    'save_type' => 'shot',
                    'is_penalty' => true,
                    'shooter_player_id' => 20,
                ],
            ],
        ])->assertOk();

        $save = EventEditionMatchSave::where('match_id', $match->id)->first();

        $this->assertSame('penalty_shootout', $save->period);
        $this->assertTrue((bool) $save->is_penalty);
        // La tanda no se mide en minutos de juego: no se guarda un minuto inventado.
        $this->assertNull($save->minute);
        $this->assertNull($save->minute_in_period);
    }

    public function test_rechaza_atajadas_de_la_ronda_de_penales_si_el_partido_no_la_jugo(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'saves_h' => [
                ['player_id' => 10, 'period' => 'penalty_shootout'],
            ],
        ]);

        $response->assertSessionHasErrors('saves_h.0');

        $this->assertSame(0, EventEditionMatchSave::where('match_id', $match->id)->count());
    }

    public function test_rechaza_atajadas_de_la_prorroga_si_el_partido_no_la_jugo(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'saves_h' => [
                ['player_id' => 10, 'period' => 'extra_first', 'minute_in_period' => 3],
            ],
        ]);

        $response->assertSessionHasErrors('saves_h.0');
    }

    public function test_rechaza_un_pateador_que_no_es_del_equipo_rival(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $response = $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'saves_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 1, 'shooter_player_id' => 12],
            ],
        ]);

        $response->assertSessionHasErrors('saves_h.0');

        $this->assertSame(0, EventEditionMatchSave::where('match_id', $match->id)->count());
    }

    public function test_descarta_el_pateador_que_ya_no_esta_en_el_acta_rival(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        // 21 queda fuera de la alineación rival: el pateador se descarta, pero
        // la atajada no se pierde por un dato accesorio.
        $actaAway = array_values(array_filter(
            $awayPlayers,
            fn ($player) => $player['person_id'] === 20
        ));

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $actaAway,
            'saves_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 1, 'shooter_player_id' => 21],
            ],
        ])->assertOk();

        $save = EventEditionMatchSave::where('match_id', $match->id)->first();

        $this->assertNotNull($save);
        $this->assertNull($save->shooter_player_id);
    }

    public function test_corregir_el_detalle_de_atajadas_reemplaza_el_anterior(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $acta = [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
        ];

        $this->post(route('even_edition_match_score_update'), $acta + [
            'saves_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 4, 'save_type' => 'header'],
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 6, 'save_type' => 'shot'],
            ],
        ])->assertOk();

        $this->assertSame(2, $this->savesOf($match->id, 10));

        // Se corrige el detalle: queda una sola atajada y con otro tipo.
        $this->post(route('even_edition_match_score_update'), $acta + [
            'saves_h' => [
                ['player_id' => 10, 'period' => 'first', 'minute_in_period' => 4, 'save_type' => 'olympic'],
            ],
        ])->assertOk();

        $this->assertSame(1, $this->savesOf($match->id, 10));
        $this->assertDatabaseHas('event_edition_match_saves', [
            'match_id' => $match->id,
            'player_id' => 10,
            'save_type' => 'olympic',
        ]);
        $this->assertSame(1, EventEditionMatchSave::where('match_id', $match->id)->count());
    }

    public function test_la_vista_dedicada_trae_las_atajadas_con_tipo_y_pateador(): void
    {
        [$match, $homePlayers, $awayPlayers] = $this->scenarioWithPlayers();

        $this->post(route('even_edition_match_score_update'), [
            'id' => $match->id,
            'edition_id' => $this->edition->id,
            'equipo_h_id' => $this->homeTeam->id,
            'equipo_a_id' => $this->awayTeam->id,
            'score_h' => 0,
            'score_a' => 0,
            'players_h' => $homePlayers,
            'players_a' => $awayPlayers,
            'saves_h' => [
                [
                    'player_id' => 10,
                    'period' => 'second',
                    'minute_in_period' => 7,
                    'save_type' => 'olympic',
                    'shooter_player_id' => 20,
                ],
            ],
        ])->assertOk();

        $this->get(route('even_ediciones_partido_resultado', [$this->edition->id, $match->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Socialevents::Editions/MatchScore', false)
                ->where('match.saves.0.save_type', 'olympic')
                ->where('match.saves.0.save_type_label', 'Tiro olímpico')
                ->where('match.saves.0.shooter_player_id', 20)
                ->where('match.saves.0.minute', 27)
            );
    }

    private function prepareFor(int $outId, int $inId, string $period = 'second', int $minute = 5): array
    {
        $timing = $this->service->resolvedTiming($this->makeMatch());

        return $this->service->prepareSubstitutions([
            ['player_out_id' => $outId, 'player_in_id' => $inId, 'period' => $period, 'minute_in_period' => $minute],
        ], [
            'team_id' => $this->homeTeam->id,
            'edition_id' => $this->edition->id,
            'roster' => [10, 11, 12],
            'roles' => [10 => 'starter', 11 => 'substitute', 12 => 'starter'],
            'timing' => $timing,
        ]);
    }

    private function makeMatch(array $attributes = []): EventEditionMatch
    {
        return EventEditionMatch::create(array_merge([
            'edition_id' => $this->edition->id,
            'team_h_id' => $this->homeTeam->id,
            'team_a_id' => $this->awayTeam->id,
            'phase' => 'league',
            'round_number' => 1,
            'match_date' => '2026-01-15 10:00:00',
            'status' => 'pending',
        ], $attributes));
    }

    /**
     * Partido con la plantilla de ambos equipos: 10/12 titulares, 11/13 suplentes.
     *
     * @return array{0:EventEditionMatch,1:array,2:array}
     */
    private function scenarioWithPlayers(): array
    {
        $match = $this->makeMatch();

        $homePlayers = [];
        $awayPlayers = [];

        foreach ([10 => 'starter', 11 => 'substitute', 12 => 'starter', 13 => 'substitute'] as $personId => $role) {
            $this->registerPlayer($this->homeTeam, $personId);
            $homePlayers[] = $this->playerPayload($personId, $role);
        }

        foreach ([20 => 'starter', 21 => 'substitute'] as $personId => $role) {
            $this->registerPlayer($this->awayTeam, $personId);
            $awayPlayers[] = $this->playerPayload($personId, $role);
        }

        return [$match, $homePlayers, $awayPlayers];
    }

    /**
     * Jugador tal como lo envía el acta real (roles, estadísticas y tarjetas),
     * que es la forma que espera el controlador web.
     */
    private function playerPayload(int $personId, string $role): array
    {
        return [
            'person_id' => $personId,
            'match_role' => $role,
            'goals' => 0,
            'assists' => 0,
            'saves' => 0,
            'yellow_cards' => 0,
            'red_cards' => 0,
            'is_double_yellow' => false,
            'is_mvp' => false,
        ];
    }

    private function registerPlayer(EventTeam $team, int $personId): void
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
            'position' => 'Mediocampista',
            'role_in_team' => 'Ninguno',
            'registration_date' => '2026-01-11',
        ]);
    }

    private function minutesOf(int $matchId, int $personId): int
    {
        $stat = EventEditionMatchPlayerStat::where('match_id', $matchId)
            ->where('player_id', $personId)
            ->first();

        return $stat ? (int) $stat->minutes_played : -1;
    }

    private function savesOf(int $matchId, int $personId): int
    {
        $stat = EventEditionMatchPlayerStat::where('match_id', $matchId)
            ->where('player_id', $personId)
            ->first();

        return $stat ? (int) $stat->saves : -1;
    }

    private function goalsOf(int $matchId, int $personId): int
    {
        $stat = EventEditionMatchPlayerStat::where('match_id', $matchId)
            ->where('player_id', $personId)
            ->first();

        return $stat ? (int) $stat->goals : -1;
    }

    private function assistsOf(int $matchId, int $personId): int
    {
        $stat = EventEditionMatchPlayerStat::where('match_id', $matchId)
            ->where('player_id', $personId)
            ->first();

        return $stat ? (int) $stat->assists : -1;
    }

    private function resultUrl(EventEditionMatch $match): string
    {
        return "/api/socialevents/v1/admin/matches/{$match->id}/result";
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();

        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        // Permiso que exige la ruta web del acta (POST /editions/fixtures/match/score).
        $permission = Permission::findOrCreate('even_ediciones_partido_resultado', 'web');
        $role->givePermissionTo($permission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Sanctum::actingAs($user);
        $this->actingAs($user);

        return $user;
    }
}
