<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Support\TournamentMatchResultPresenter;
use Modules\Socialevents\Support\TournamentPhaseLabels;

/**
 * Reporte de fin de temporada de una edición.
 *
 * Reúne en un solo payload las secciones que el organizador imprime: tabla,
 * equipos, podio, historial de cada equipo, rankings de la landing y disciplina.
 * No guarda nada: todo se calcula desde los datos ya registrados.
 */
class TournamentSeasonReportService
{
    /** Estados con resultado válido para el reporte. */
    private const PLAYED_STATUSES = ['finished', 'closed', 'walk_over', 'no_points'];

    /** Secciones disponibles para la vista y el PDF. */
    public const SECTIONS = [
        'summary' => 'Resumen general',
        'standings' => 'Tabla de posiciones',
        'teams' => 'Equipos participantes',
        'podium' => 'Podio (1.º al 4.º)',
        'team_history' => 'Historia por equipo',
        'player_ranking' => 'Ranking de jugadores',
        'scorers' => 'Goleadores',
        'discipline' => 'Disciplina',
        'highlights' => 'Datos destacados',
    ];

    public function __construct(
        private TournamentStandingsService $standingsService,
        private TournamentRankingsService $rankingsService,
        private TournamentChampionService $championService,
        private TournamentTieService $tieService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(EventEdition $edition): array
    {
        $edition->loadMissing('evento');

        $editionId = (int) $edition->id;
        $standings = collect($this->standingsService->asStandingsPayload($editionId));
        $teamModels = EventEditionTeam::with(['equipo.manager'])
            ->where('edition_id', $editionId)
            ->get()
            ->keyBy('team_id');
        $roster = EventEditionTeamPlayer::with(['person', 'team'])
            ->where('edition_id', $editionId)
            ->get();
        $matches = EventEditionMatch::with([
            'equipolocal:id,name,short_name,logo_path',
            'equipovisitante:id,name,short_name,logo_path',
        ])
            ->where('edition_id', $editionId)
            ->orderByRaw('match_date IS NULL')
            ->orderBy('match_date')
            ->orderBy('id')
            ->get();
        $playedMatches = $matches
            ->filter(fn (EventEditionMatch $match) => $this->isPlayed($match))
            ->values();
        $sanctions = EventEditionMatchSanction::query()
            ->whereHas('match', fn ($query) => $query->where('edition_id', $editionId))
            ->get();
        $stats = EventEditionMatchPlayerStat::with(['player.person'])
            ->whereHas('match', fn ($query) => $query->where('edition_id', $editionId))
            ->get();

        $playerTeamId = $roster
            ->mapWithKeys(fn (EventEditionTeamPlayer $player) => [(int) $player->person_id => (int) $player->team_id])
            ->all();
        $statTeamId = [];

        foreach ($stats as $stat) {
            $statTeamId[(int) $stat->match_id][(int) $stat->player_id] = (int) $stat->team_id;
        }

        [$cardsByMatchTeam, $cardsByPlayerMatch, $playerCardTotals] = $this->cardIndexes(
            $sanctions,
            $playerTeamId,
            $statTeamId
        );

        $champion = $this->championService->forEdition($edition);
        $history = $this->teamHistory($standings, $playedMatches, $cardsByMatchTeam);
        $podium = $this->podium($standings, $champion, $teamModels, $matches);
        $scorers = $this->scorers($editionId);
        $playersRanking = $this->playerRanking($editionId, 'player', $cardsByPlayerMatch, $playerCardTotals);
        $goalkeepersRanking = $this->playerRanking($editionId, 'goalkeeper', $cardsByPlayerMatch, $playerCardTotals);
        $discipline = $this->discipline($history, $sanctions, $roster, $playerCardTotals);
        $summary = $this->summary(
            $standings,
            $playedMatches,
            $matches->count(),
            $roster->count(),
            $discipline,
            $champion
        );

        return [
            'edition' => [
                'id' => $editionId,
                'name' => $edition->name,
                'year' => $edition->year,
                'status' => $edition->status,
                'status_label' => $this->statusLabel($edition->status),
                'status_finished' => $edition->status === 'finished',
                'competition_format' => $edition->competition_format,
                'format_label' => $this->formatLabel($edition->competition_format),
                'start_date' => $edition->start_date,
                'end_date' => $edition->end_date,
                'event_title' => $edition->evento?->title,
                'landing_url' => $edition->landingUrl(),
            ],
            'generated_at' => now()->format('d/m/Y H:i'),
            'summary' => $summary,
            'standings' => $standings->all(),
            'teams' => $this->teams($standings, $teamModels, $roster),
            'podium' => $podium,
            'team_history' => $history,
            'player_ranking' => [
                'players' => $playersRanking,
                'goalkeepers' => $goalkeepersRanking,
            ],
            'scorers' => $scorers,
            'discipline' => $discipline,
            'highlights' => $this->highlights($standings, $history, $playedMatches, $scorers, $stats, $roster),
        ];
    }

    /**
     * Índices de tarjetas por partido/equipo y por jugador/partido.
     *
     * @param  Collection<int, EventEditionMatchSanction>  $sanctions
     * @param  array<int, int>  $playerTeamId
     * @param  array<int, array<int, int>>  $statTeamId
     * @return array{0: array<int, array<int, array<string, int>>>, 1: array<int, array<int, array<string, int>>>, 2: array<int, array<string, int>>}
     */
    private function cardIndexes(Collection $sanctions, array $playerTeamId, array $statTeamId): array
    {
        $byMatchTeam = [];
        $byPlayerMatch = [];
        $playerTotals = [];

        foreach ($sanctions as $sanction) {
            $type = (string) $sanction->type;

            if (! array_key_exists($type, $this->emptyCards())) {
                continue;
            }

            $matchId = (int) $sanction->match_id;
            $playerId = (int) $sanction->player_id;
            // Primero el equipo con el que jugó ese partido; si no hay acta,
            // se usa su plantilla actual.
            $teamId = $statTeamId[$matchId][$playerId] ?? $playerTeamId[$playerId] ?? null;

            if ($teamId !== null) {
                $byMatchTeam[$matchId][$teamId] ??= $this->emptyCards();
                $byMatchTeam[$matchId][$teamId][$type]++;
            }

            $byPlayerMatch[$playerId][$matchId] ??= $this->emptyCards();
            $byPlayerMatch[$playerId][$matchId][$type]++;

            $playerTotals[$playerId] ??= $this->emptyCards();
            $playerTotals[$playerId][$type]++;
        }

        return [$byMatchTeam, $byPlayerMatch, $playerTotals];
    }

    /**
     * @return array{yellow: int, red: int, double_yellow: int}
     */
    private function emptyCards(): array
    {
        return ['yellow' => 0, 'red' => 0, 'double_yellow' => 0];
    }

    /**
     * Rojas efectivas: la roja directa y la doble amarilla expulsan.
     *
     * @param  array<string, int>  $cards
     */
    private function sendingOffs(array $cards): int
    {
        return (int) ($cards['red'] ?? 0) + (int) ($cards['double_yellow'] ?? 0);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $standings
     * @param  Collection<int, EventEditionMatch>  $matches
     * @param  array<int, array<int, array<string, int>>>  $cardsByMatchTeam
     * @return array<int, array<string, mixed>>
     */
    private function teamHistory(Collection $standings, Collection $matches, array $cardsByMatchTeam): array
    {
        return $standings
            ->map(function (array $row) use ($matches, $cardsByMatchTeam) {
                $teamId = (int) $row['team_id'];
                $teamMatches = $matches
                    ->filter(fn (EventEditionMatch $match) => (int) $match->team_h_id === $teamId || (int) $match->team_a_id === $teamId)
                    ->values();

                $totals = [
                    'played' => 0,
                    'won' => 0,
                    'drawn' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'yellow' => 0,
                    'red' => 0,
                ];

                $rows = $teamMatches->map(function (EventEditionMatch $match) use ($teamId, $cardsByMatchTeam, &$totals) {
                    $isHome = (int) $match->team_h_id === $teamId;
                    $goalsFor = (int) ($isHome ? $match->score_h : $match->score_a);
                    $goalsAgainst = (int) ($isHome ? $match->score_a : $match->score_h);
                    $result = $goalsFor > $goalsAgainst ? 'G' : ($goalsFor === $goalsAgainst ? 'E' : 'P');
                    $cards = $cardsByMatchTeam[(int) $match->id][$teamId] ?? $this->emptyCards();
                    $presenter = TournamentMatchResultPresenter::forMatch($match);

                    $totals['played']++;
                    $totals[$result === 'G' ? 'won' : ($result === 'E' ? 'drawn' : 'lost')]++;
                    $totals['goals_for'] += $goalsFor;
                    $totals['goals_against'] += $goalsAgainst;
                    $totals['yellow'] += (int) $cards['yellow'];
                    $totals['red'] += $this->sendingOffs($cards);

                    return [
                        'match_id' => (int) $match->id,
                        'date' => $match->match_date?->format('d/m/Y'),
                        'time' => $match->match_date?->format('H:i'),
                        'phase' => $match->phase,
                        'phase_label' => TournamentPhaseLabels::label($match->phase),
                        'round' => $match->round_number,
                        'opponent' => $isHome
                            ? ($match->equipovisitante?->name ?? $match->placeholder_a ?? 'Por definir')
                            : ($match->equipolocal?->name ?? $match->placeholder_h ?? 'Por definir'),
                        'venue' => $isHome ? 'Local' : 'Visitante',
                        'score' => $presenter['final_label'],
                        'goals_for' => $goalsFor,
                        'goals_against' => $goalsAgainst,
                        'result' => $result,
                        'yellow' => (int) $cards['yellow'],
                        'red' => $this->sendingOffs($cards),
                    ];
                })->all();

                return [
                    'team_id' => $teamId,
                    'team_name' => $row['team_name'],
                    'team_logo' => $row['team_logo'],
                    'position' => $row['position'],
                    'matches' => $rows,
                    'totals' => $totals + [
                        'goal_difference' => $totals['goals_for'] - $totals['goals_against'],
                    ],
                ];
            })
            ->all();
    }

    /**
     * Podio: 1.º y 2.º desde la final; 3.º y 4.º desde el partido por el tercer
     * puesto. Si ese partido no define ganador (no se jugó o terminó 0-0 sin
     * penales), los dos equipos del partido se ordenan por los puntos de la tabla.
     *
     * @param  Collection<int, array<string, mixed>>  $standings
     * @param  array<string, mixed>|null  $champion
     * @param  Collection<int, EventEditionTeam>  $teamModels
     * @param  Collection<int, EventEditionMatch>  $matches
     * @return array<int, array<string, mixed>>
     */
    private function podium(
        Collection $standings,
        ?array $champion,
        Collection $teamModels,
        Collection $matches
    ): array {
        $medals = [1 => 'Campeón', 2 => 'Subcampeón', 3 => 'Tercer puesto', 4 => 'Cuarto puesto'];
        $championTeamId = isset($champion['team_id']) ? (int) $champion['team_id'] : null;
        $rowsByTeam = $standings->keyBy(fn (array $row) => (int) $row['team_id']);
        $ordered = [];

        $push = function (int $teamId) use (&$ordered): void {
            if ($teamId > 0 && ! in_array($teamId, $ordered, true)) {
                $ordered[] = $teamId;
            }
        };

        // 1.º y 2.º: campeón de la final y el otro finalista.
        if ($championTeamId !== null) {
            $push($championTeamId);

            $finalGroup = $this->phaseGroup($matches, 'final', $championTeamId);
            $runnerUpId = $this->teamIdsInGroup($finalGroup)
                ->first(fn (int $teamId) => $teamId !== $championTeamId);

            if ($runnerUpId !== null) {
                $push((int) $runnerUpId);
            }
        }

        // Si la final no está definida, se conserva el orden de la tabla para 1.º y 2.º.
        foreach ($standings as $row) {
            if (count($ordered) >= 2) {
                break;
            }

            $push((int) $row['team_id']);
        }

        // 3.º y 4.º: ganador y perdedor del partido por el tercer puesto.
        $thirdPlaceGroup = $this->phaseGroup($matches, 'third_place');
        $thirdPlaceTeams = $this->teamIdsInGroup($thirdPlaceGroup)->take(2)->all();

        if (count($thirdPlaceTeams) >= 2) {
            $winnerId = $this->groupWinner($thirdPlaceGroup);

            if ($winnerId !== null && in_array($winnerId, $thirdPlaceTeams, true)) {
                // El ganador entra primero; después el otro equipo del partido.
                $thirdPlaceTeams = array_values(array_unique(array_merge([$winnerId], $thirdPlaceTeams)));
            } else {
                // No hubo partido o quedó 0-0 sin penales: mandan los puntos.
                $thirdPlaceTeams = $this->sortByPoints($thirdPlaceTeams, $standings);
            }

            foreach ($thirdPlaceTeams as $teamId) {
                if (count($ordered) >= 4) {
                    break;
                }

                $push((int) $teamId);
            }
        }

        // Sin partido por el tercer puesto (o con datos incompletos), completa con la tabla.
        foreach ($standings as $row) {
            if (count($ordered) >= 4) {
                break;
            }

            $push((int) $row['team_id']);
        }

        return collect(array_slice($ordered, 0, 4))
            ->values()
            ->map(function (int $teamId, int $index) use ($medals, $championTeamId, $rowsByTeam, $teamModels) {
                $position = $index + 1;
                $row = $rowsByTeam->get($teamId);
                $team = $teamModels->get($teamId);

                return [
                    'position' => $position,
                    'label' => $medals[$position],
                    'team_id' => $teamId,
                    'team_name' => $row['team_name'] ?? $team?->equipo?->name ?? ('Equipo '.$teamId),
                    'team_logo' => $row['team_logo'] ?? null,
                    'manager' => $team?->equipo?->manager?->full_name,
                    'points' => (int) ($row['points'] ?? 0),
                    'matches_played' => (int) ($row['matches_played'] ?? 0),
                    'matches_won' => (int) ($row['matches_won'] ?? 0),
                    'matches_drawn' => (int) ($row['matches_drawn'] ?? 0),
                    'matches_lost' => (int) ($row['matches_lost'] ?? 0),
                    'goals_for' => (int) ($row['goals_for'] ?? 0),
                    'goals_against' => (int) ($row['goals_against'] ?? 0),
                    'goal_difference' => (int) ($row['goal_difference'] ?? 0),
                    'is_champion' => $championTeamId !== null && $championTeamId === $teamId,
                ];
            })
            ->all();
    }

    /**
     * Agrupa los partidos de una fase por cruce (ida/vuelta) y devuelve el grupo
     * más reciente; si se indica un equipo, prioriza el cruce donde participó.
     *
     * @param  Collection<int, EventEditionMatch>  $matches
     * @return Collection<int, EventEditionMatch>
     */
    private function phaseGroup(Collection $matches, string $phase, ?int $preferredTeamId = null): Collection
    {
        $filtered = $matches
            ->filter(fn (EventEditionMatch $match) => TournamentPhaseLabels::canonical($match->phase) === $phase)
            ->values();

        if ($filtered->isEmpty()) {
            return collect();
        }

        $byId = $filtered->keyBy('id');
        $groups = [];

        foreach ($filtered as $match) {
            $rootId = $match->tie_id && $byId->has((int) $match->tie_id)
                ? (int) $match->tie_id
                : (int) $match->id;

            $groups[$rootId][] = $match;
        }

        $groups = collect($groups)
            ->map(fn (array $legs) => collect($legs)->sortBy('leg')->values())
            ->sortByDesc(fn (Collection $legs) => $this->groupSortKey($legs))
            ->values();

        if ($preferredTeamId !== null) {
            $preferred = $groups->first(
                fn (Collection $legs) => $this->teamIdsInGroup($legs)->contains($preferredTeamId)
            );

            if ($preferred) {
                return $preferred;
            }
        }

        return $groups->first() ?? collect();
    }

    /**
     * @param  Collection<int, EventEditionMatch>  $legs
     */
    private function groupSortKey(Collection $legs): string
    {
        return $legs
            ->map(fn (EventEditionMatch $match) => sprintf(
                '%s-%010d',
                $match->match_date?->format('Y-m-d H:i:s') ?? '0000-00-00 00:00:00',
                (int) $match->id
            ))
            ->max() ?? '';
    }

    /**
     * Equipos que participaron en un cruce, sin placeholders ni duplicados.
     *
     * @param  Collection<int, EventEditionMatch>  $legs
     * @return Collection<int, int>
     */
    private function teamIdsInGroup(Collection $legs): Collection
    {
        return $legs
            ->flatMap(fn (EventEditionMatch $match) => [
                $match->team_h_id !== null ? (int) $match->team_h_id : 0,
                $match->team_a_id !== null ? (int) $match->team_a_id : 0,
            ])
            ->filter(fn (int $teamId) => $teamId > 0)
            ->unique()
            ->values();
    }

    /**
     * Ganador de un cruce ya jugado. Devuelve null si falta una pierna o si el
     * marcador empata y no hay penales: en ese caso el podio usa los puntos.
     *
     * @param  Collection<int, EventEditionMatch>  $legs
     */
    private function groupWinner(Collection $legs): ?int
    {
        $legs = $legs->sortBy('leg')->values();

        if ($legs->isEmpty() || ! $legs->every(
            fn (EventEditionMatch $match) => in_array((string) $match->status, self::PLAYED_STATUSES, true)
        )) {
            return null;
        }

        if ($legs->count() > 1) {
            $root = $legs->first(fn (EventEditionMatch $match) => ! $match->tie_id) ?? $legs->first();
            $summary = $this->tieService->summary($root);

            if (empty($summary['is_tie']) || empty($summary['complete']) || empty($summary['winner_team_id'])) {
                return null;
            }

            return (int) $summary['winner_team_id'];
        }

        $match = $legs->first();

        // Una final a ida y vuelta con una sola pierna todavía no está definida.
        if ($match->tie_format === TournamentTieService::FORMAT_HOME_AND_AWAY) {
            return null;
        }

        if ($match->team_h_id === null || $match->team_a_id === null) {
            return null;
        }

        $result = TournamentMatchResultPresenter::forMatch($match);
        $winnerSide = $result['shootout']['winner_side'] ?? null;

        if ($winnerSide === null) {
            if ((int) $result['final_h'] === (int) $result['final_a']) {
                return null;
            }

            $winnerSide = $result['final_h'] > $result['final_a'] ? 'home' : 'away';
        }

        return $winnerSide === 'home' ? (int) $match->team_h_id : (int) $match->team_a_id;
    }

    /**
     * Ordena dos o más equipos por puntos de la tabla; el desempate usa la
     * posición final que ya calcularon los standings.
     *
     * @param  array<int, int>  $teamIds
     * @param  Collection<int, array<string, mixed>>  $standings
     * @return array<int, int>
     */
    private function sortByPoints(array $teamIds, Collection $standings): array
    {
        $rowsByTeam = $standings->keyBy(fn (array $row) => (int) $row['team_id']);

        usort($teamIds, function (int $a, int $b) use ($rowsByTeam): int {
            $rowA = $rowsByTeam->get($a);
            $rowB = $rowsByTeam->get($b);
            $pointsA = (int) ($rowA['points'] ?? -1);
            $pointsB = (int) ($rowB['points'] ?? -1);

            if ($pointsA !== $pointsB) {
                return $pointsB <=> $pointsA;
            }

            $positionA = (int) ($rowA['position'] ?? 999);
            $positionB = (int) ($rowB['position'] ?? 999);
            $byPosition = $positionA <=> $positionB;

            return $byPosition !== 0 ? $byPosition : $a <=> $b;
        });

        return $teamIds;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $standings
     * @param  Collection<int, EventEditionTeam>  $teamModels
     * @param  Collection<int, EventEditionTeamPlayer>  $roster
     * @return array<int, array<string, mixed>>
     */
    private function teams(Collection $standings, Collection $teamModels, Collection $roster): array
    {
        $playersByTeam = $roster->groupBy('team_id');

        return $standings
            ->map(function (array $row) use ($teamModels, $playersByTeam) {
                $team = $teamModels->get((int) $row['team_id']);
                $players = $playersByTeam->get((int) $row['team_id'], collect());

                return [
                    'team_id' => (int) $row['team_id'],
                    'team_name' => $row['team_name'],
                    'short_name' => $row['team_short_name'],
                    'team_logo' => $row['team_logo'],
                    'manager' => $team?->equipo?->manager?->full_name,
                    'players_count' => $players->count(),
                    'position' => (int) $row['position'],
                    'points' => (int) $row['points'],
                    'matches_played' => (int) $row['matches_played'],
                    'has_sanction' => (bool) $row['has_sanction'],
                    'point_adjustments' => (int) $row['point_adjustments'],
                ];
            })
            ->all();
    }

    /**
     * Ranking de la landing con el detalle partido a partido.
     *
     * @param  array<int, array<int, array<string, int>>>  $cardsByPlayerMatch
     * @param  array<int, array<string, int>>  $playerCardTotals
     * @return array<int, array<string, mixed>>
     */
    private function playerRanking(int $editionId, string $category, array $cardsByPlayerMatch, array $playerCardTotals): array
    {
        $rows = $category === 'goalkeeper'
            ? $this->rankingsService->topGoalkeepers($editionId, 5)
            : $this->rankingsService->topPlayers($editionId, 5);

        return $rows
            ->map(function (array $row) use ($editionId, $category, $cardsByPlayerMatch, $playerCardTotals) {
                $player = $row['player'] ?? null;
                $playerId = (int) ($player?->person_id ?? 0);

                if ($playerId === 0) {
                    return null;
                }

                $detail = $this->rankingsService->playerDetail($editionId, $playerId, $category);

                if ($detail === null) {
                    return null;
                }

                $breakdown = collect($detail['breakdown'] ?? [])
                    ->map(function (array $line) use ($playerId, $cardsByPlayerMatch) {
                        $cards = $cardsByPlayerMatch[$playerId][(int) $line['match_id']] ?? $this->emptyCards();
                        $line['yellow'] = (int) $cards['yellow'];
                        $line['red'] = $this->sendingOffs($cards);

                        return $line;
                    })
                    ->all();

                $totals = $playerCardTotals[$playerId] ?? $this->emptyCards();

                return [
                    'player_id' => $playerId,
                    'name' => $detail['player']['name'] ?? ($player?->person?->full_name ?? 'Jugador'),
                    'photo' => $detail['player']['photo'] ?? null,
                    'position' => $detail['player']['position'] ?? null,
                    'jersey_number' => $detail['player']['jersey_number'] ?? null,
                    'team_name' => $detail['player']['team_name'] ?? null,
                    'team_logo' => $detail['player']['team_logo'] ?? null,
                    'points' => round((float) ($detail['summary']['points'] ?? $row['points'] ?? 0), 2),
                    'summary' => $detail['summary'] ?? [],
                    'cards' => [
                        'yellow' => (int) $totals['yellow'],
                        'red' => $this->sendingOffs($totals),
                    ],
                    'breakdown' => $breakdown,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scorers(int $editionId): array
    {
        return $this->rankingsService->scorersTable($editionId)
            ->take(10)
            ->values()
            ->map(function (array $row) {
                return [
                    'player_id' => (int) ($row['player']?->person_id ?? 0),
                    'name' => $row['player']?->person?->full_name ?? 'Jugador',
                    'team_name' => $row['player']?->team?->name,
                    'goals' => (int) $row['goals'],
                    'assists' => (int) ($row['assists'] ?? 0),
                    'matches_played' => (int) ($row['matches_played'] ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     * @param  Collection<int, EventEditionMatchSanction>  $sanctions
     * @param  Collection<int, EventEditionTeamPlayer>  $roster
     * @param  array<int, array<string, int>>  $playerCardTotals
     * @return array<string, mixed>
     */
    private function discipline(array $history, Collection $sanctions, Collection $roster, array $playerCardTotals): array
    {
        $totals = ['yellow' => 0, 'red' => 0, 'double_yellow' => 0];

        foreach ($sanctions as $sanction) {
            $type = (string) $sanction->type;

            if (array_key_exists($type, $totals)) {
                $totals[$type]++;
            }
        }

        $playersById = $roster->keyBy('person_id');

        $topPlayers = collect($playerCardTotals)
            ->map(function (array $cards, int $playerId) use ($playersById) {
                $player = $playersById->get($playerId);

                return [
                    'player_id' => $playerId,
                    'name' => $player?->person?->full_name ?? 'Jugador',
                    'team_name' => $player?->team?->name,
                    'yellow' => (int) $cards['yellow'],
                    'red' => $this->sendingOffs($cards),
                    'total' => (int) $cards['yellow'] + $this->sendingOffs($cards),
                ];
            })
            ->sort(function (array $a, array $b) {
                if ($a['red'] !== $b['red']) {
                    return $b['red'] <=> $a['red'];
                }

                if ($a['yellow'] !== $b['yellow']) {
                    return $b['yellow'] <=> $a['yellow'];
                }

                return $a['name'] <=> $b['name'];
            })
            ->take(10)
            ->values()
            ->all();

        $byTeam = collect($history)
            ->map(fn (array $team) => [
                'team_id' => $team['team_id'],
                'team_name' => $team['team_name'],
                'yellow' => (int) $team['totals']['yellow'],
                'red' => (int) $team['totals']['red'],
                'total' => (int) $team['totals']['yellow'] + (int) $team['totals']['red'],
                'matches_played' => (int) $team['totals']['played'],
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'totals' => [
                'yellow' => (int) $totals['yellow'],
                'red' => $this->sendingOffs($totals),
                'double_yellow' => (int) $totals['double_yellow'],
                'total' => (int) $totals['yellow'] + $this->sendingOffs($totals),
            ],
            'by_team' => $byTeam,
            'top_players' => $topPlayers,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $standings
     * @param  Collection<int, EventEditionMatch>  $playedMatches
     * @param  array<string, mixed>  $discipline
     * @param  array<string, mixed>|null  $champion
     * @return array<string, mixed>
     */
    private function summary(
        Collection $standings,
        Collection $playedMatches,
        int $totalMatches,
        int $totalPlayers,
        array $discipline,
        ?array $champion
    ): array {
        $goals = $playedMatches->sum(fn (EventEditionMatch $match) => (int) $match->score_h + (int) $match->score_a);
        $played = $playedMatches->count();
        $leader = $standings->first();

        return [
            'teams' => $standings->count(),
            'players' => $totalPlayers,
            'matches_total' => $totalMatches,
            'matches_played' => $played,
            'matches_pending' => max(0, $totalMatches - $played),
            'goals' => (int) $goals,
            'goals_average' => $played > 0 ? round($goals / $played, 2) : 0,
            'yellow_cards' => (int) $discipline['totals']['yellow'],
            'red_cards' => (int) $discipline['totals']['red'],
            // Sin final terminada no se corona a nadie: la tabla no reemplaza al campeón.
            'champion_name' => $champion['name'] ?? null,
            'leader_name' => $leader['team_name'] ?? null,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $standings
     * @param  array<int, array<string, mixed>>  $history
     * @param  Collection<int, EventEditionMatch>  $playedMatches
     * @param  array<int, array<string, mixed>>  $scorers
     * @param  Collection<int, EventEditionMatchPlayerStat>  $stats
     * @param  Collection<int, EventEditionTeamPlayer>  $roster
     * @return array<string, mixed>
     */
    private function highlights(
        Collection $standings,
        array $history,
        Collection $playedMatches,
        array $scorers,
        Collection $stats,
        Collection $roster
    ): array {
        $withMatches = $standings->filter(fn (array $row) => (int) $row['matches_played'] > 0);

        $bestAttack = $withMatches->sortByDesc('goals_for')->first();
        $bestDefense = $withMatches->sortBy('goals_against')->first();
        $mostDisciplined = collect($history)
            ->filter(fn (array $team) => (int) $team['totals']['played'] > 0)
            ->sortBy(fn (array $team) => (int) $team['totals']['yellow'] + (int) $team['totals']['red'])
            ->first();

        $biggestWin = $playedMatches
            ->sortByDesc(fn (EventEditionMatch $match) => abs((int) $match->score_h - (int) $match->score_a))
            ->first();

        $mvpLeader = $stats
            ->filter(fn (EventEditionMatchPlayerStat $stat) => (bool) $stat->is_mvp)
            ->groupBy('player_id')
            ->map(fn (Collection $rows, $playerId) => [
                'count' => $rows->count(),
                'player' => $roster->firstWhere('person_id', (int) $playerId),
            ])
            ->sortByDesc('count')
            ->first();

        return [
            'best_attack' => $bestAttack ? [
                'team_name' => $bestAttack['team_name'],
                'value' => (int) $bestAttack['goals_for'],
            ] : null,
            'best_defense' => $bestDefense ? [
                'team_name' => $bestDefense['team_name'],
                'value' => (int) $bestDefense['goals_against'],
            ] : null,
            'most_disciplined' => $mostDisciplined ? [
                'team_name' => $mostDisciplined['team_name'],
                'value' => (int) $mostDisciplined['totals']['yellow'] + (int) $mostDisciplined['totals']['red'],
            ] : null,
            'biggest_win' => $biggestWin ? [
                'label' => ($biggestWin->equipolocal?->name ?? $biggestWin->placeholder_h ?? 'Local')
                    .' '.((int) $biggestWin->score_h).' - '.((int) $biggestWin->score_a).' '
                    .($biggestWin->equipovisitante?->name ?? $biggestWin->placeholder_a ?? 'Visitante'),
                'difference' => abs((int) $biggestWin->score_h - (int) $biggestWin->score_a),
                'date' => $biggestWin->match_date?->format('d/m/Y'),
                'phase' => TournamentPhaseLabels::label($biggestWin->phase),
            ] : null,
            'top_scorer' => $scorers[0] ?? null,
            'mvp_leader' => $mvpLeader ? [
                'name' => $mvpLeader['player']?->person?->full_name ?? 'Jugador',
                'team_name' => $mvpLeader['player']?->team?->name,
                'value' => (int) $mvpLeader['count'],
            ] : null,
        ];
    }

    private function isPlayed(EventEditionMatch $match): bool
    {
        return in_array((string) $match->status, self::PLAYED_STATUSES, true)
            && $match->score_h !== null
            && $match->score_a !== null;
    }

    private function statusLabel(?string $status): string
    {
        return [
            'pending' => 'Próximamente',
            'in_progress' => 'En juego',
            'finished' => 'Finalizada',
        ][$status] ?? ($status ?: 'Sin estado');
    }

    private function formatLabel(?string $format): string
    {
        return [
            'round_robin' => 'Todos contra todos',
            'group_stage_and_playoffs' => 'Grupos y eliminatorias',
            'single_elimination' => 'Eliminación simple',
            'round_robin_playoff' => 'Liga y eliminatorias',
            'relampago' => 'Relámpago',
        ][$format] ?? ($format ?: 'Formato por definir');
    }
}
