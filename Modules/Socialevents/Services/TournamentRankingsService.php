<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Support\TournamentMedia;
use Modules\Socialevents\Support\TournamentPhaseLabels;

class TournamentRankingsService
{
    public function __construct(private \Modules\Socialevents\Services\PlayerSuspensionService $suspensionService)
    {
    }

    /**
     * IDs de jugadores ocultos de los rankings en la edición:
     * excluidos definitivos + suspendidos con suspensión vigente.
     *
     * @return array<int>
     */
    private function getExcludedPlayerIds(int $editionId): array
    {
        return $this->suspensionService->getPlayerIdsHiddenFromRankings($editionId);
    }
    /**
     * Ranking de jugadores de campo (misma fórmula que la landing).
     */
    public function topPlayers(int $editionId, ?int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('socialevents.rankings.top_limit', 5);
        $weights = config('socialevents.rankings.players', []);

        $excludedPlayers = $this->getExcludedPlayerIds($editionId);

        $playerStats = EventEditionMatchPlayerStat::with(['player.person', 'match'])
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->get();

        $sanctions = EventEditionMatchSanction::query()
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->get()
            ->groupBy('player_id');

        $players = [];

        foreach ($playerStats as $stat) {
            if ($stat->saves > 0) {
                continue;
            }

            if (in_array($stat->player_id, $excludedPlayers)) {
                continue;
            }

            $playerId = $stat->player_id;
            $points = ($stat->goals * ($weights['goal'] ?? 3))
                + ($stat->assists * ($weights['assist'] ?? 2))
                + ($stat->is_mvp ? ($weights['mvp'] ?? 5) : 0)
                + ($stat->clean_sheet ? ($weights['clean_sheet'] ?? 1) : 0);

            $playerSanctions = $sanctions->get($playerId, collect());
            $points -= $playerSanctions->count() * ($weights['sanction_penalty'] ?? 1);

            if (! isset($players[$playerId])) {
                $players[$playerId] = [
                    'player' => $stat->player,
                    'points' => 0,
                    'stats' => ['goals' => 0, 'assists' => 0, 'mvp' => 0, 'clean_sheet' => 0],
                ];
            }

            $players[$playerId]['points'] += $points;
            $players[$playerId]['stats']['goals'] += $stat->goals;
            $players[$playerId]['stats']['assists'] += $stat->assists;
            $players[$playerId]['stats']['mvp'] += $stat->is_mvp ? 1 : 0;
            $players[$playerId]['stats']['clean_sheet'] += $stat->clean_sheet ? 1 : 0;
        }

        return collect($players)->sortByDesc('points')->take($limit)->values();
    }

    /**
     * Ranking de arqueros (misma fórmula que la landing).
     */
    public function topGoalkeepers(int $editionId, ?int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('socialevents.rankings.top_limit', 5);
        $weights = config('socialevents.rankings.goalkeepers', []);

        $excludedPlayers = $this->getExcludedPlayerIds($editionId);

        $playerStats = EventEditionMatchPlayerStat::with(['player.person', 'match'])
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->get();

        $sanctions = EventEditionMatchSanction::query()
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->get()
            ->groupBy('player_id');

        $goalkeepers = [];

        foreach ($playerStats as $stat) {
            if ($stat->saves == 0) {
                continue;
            }

            if (in_array($stat->player_id, $excludedPlayers)) {
                continue;
            }

            $playerId = $stat->player_id;
            $points = ($stat->saves * ($weights['save'] ?? 0.5))
                + ($stat->is_mvp ? ($weights['mvp'] ?? 5) : 0)
                + ($stat->clean_sheet ? ($weights['clean_sheet'] ?? 5) : 0);

            $playerSanctions = $sanctions->get($playerId, collect());
            $points -= $playerSanctions->count() * ($weights['sanction_penalty'] ?? 1);

            if (! isset($goalkeepers[$playerId])) {
                $goalkeepers[$playerId] = [
                    'player' => $stat->player,
                    'points' => 0,
                    'stats' => ['saves' => 0, 'mvp' => 0, 'clean_sheet' => 0],
                ];
            }

            $goalkeepers[$playerId]['points'] += $points;
            $goalkeepers[$playerId]['stats']['saves'] += $stat->saves;
            $goalkeepers[$playerId]['stats']['mvp'] += $stat->is_mvp ? 1 : 0;
            $goalkeepers[$playerId]['stats']['clean_sheet'] += $stat->clean_sheet ? 1 : 0;
        }

        return collect($goalkeepers)->sortByDesc('points')->take($limit)->values();
    }

    /**
     * Ranking de goleadores (goles a favor).
     * Desempate: 1) menos partidos jugados, 2) más asistencias.
     */
    public function topScorers(int $editionId, ?int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('socialevents.rankings.top_limit', 5);

        $excludedPlayers = $this->getExcludedPlayerIds($editionId);

        $playerStats = EventEditionMatchPlayerStat::with(['player.person'])
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->get();

        $matchesPlayed = EventEditionMatchParticipation::query()
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->selectRaw('player_id, COUNT(*) as total')
            ->groupBy('player_id')
            ->pluck('total', 'player_id');

        $scorers = [];

        foreach ($playerStats as $stat) {
            if ((int) $stat->goals <= 0) {
                continue;
            }

            if (in_array($stat->player_id, $excludedPlayers)) {
                continue;
            }

            $playerId = $stat->player_id;

            if (! isset($scorers[$playerId])) {
                $scorers[$playerId] = [
                    'player' => $stat->player,
                    'goals' => 0,
                    'assists' => 0,
                    'matches_played' => 0,
                ];
            }

            $scorers[$playerId]['goals'] += (int) $stat->goals;
            $scorers[$playerId]['assists'] += (int) $stat->assists;
        }

        foreach ($scorers as $playerId => $row) {
            $scorers[$playerId]['matches_played'] = (int) ($matchesPlayed[$playerId] ?? 0);
        }

        return collect($scorers)
            ->sort(function (array $a, array $b) {
                if ($a['goals'] !== $b['goals']) {
                    return $b['goals'] <=> $a['goals'];
                }

                if ($a['matches_played'] !== $b['matches_played']) {
                    return $a['matches_played'] <=> $b['matches_played'];
                }

                return $b['assists'] <=> $a['assists'];
            })
            ->take($limit)
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topPlayersPayload(int $editionId, ?int $limit = null): array
    {
        return $this->topPlayers($editionId, $limit)
            ->map(fn (array $row) => $this->serializePlayerRow($row))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topGoalkeepersPayload(int $editionId, ?int $limit = null): array
    {
        return $this->topGoalkeepers($editionId, $limit)
            ->map(fn (array $row) => $this->serializeGoalkeeperRow($row))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function topScorersPayload(int $editionId, ?int $limit = null): array
    {
        return $this->topScorers($editionId, $limit)
            ->map(fn (array $row) => $this->serializeScorerRow($row))
            ->all();
    }

    /**
     * Categorías soportadas por el detalle público de un jugador.
     *
     * @var array<int, string>
     */
    public const DETAIL_CATEGORIES = ['player', 'scorer', 'goalkeeper'];

    /**
     * Detalle partido a partido de un jugador para el modal de la landing.
     *
     * Devuelve el desglose de cada partido (goles, asistencias, atajadas, MVP,
     * valla invicta y sanciones), los totales y las razones por las que ocupa
     * su lugar en el ranking correspondiente.
     *
     * @return array<string, mixed>|null
     */
    public function playerDetail(int $editionId, int $playerId, string $category = 'player'): ?array
    {
        $category = in_array($category, self::DETAIL_CATEGORIES, true) ? $category : 'player';

        $teamPlayer = EventEditionTeamPlayer::with(['person', 'team'])
            ->where('edition_id', $editionId)
            ->where('person_id', $playerId)
            ->first();

        $stats = EventEditionMatchPlayerStat::with(['match.equipolocal', 'match.equipovisitante'])
            ->where('player_id', $playerId)
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->get();

        if (! $teamPlayer && $stats->isEmpty()) {
            return null;
        }

        // Misma regla que los rankings: los partidos jugados como arquero no
        // entran al ranking de jugadores de campo, y viceversa. Así el detalle
        // explica exactamente el puntaje que muestra la tarjeta.
        $stats = $stats->filter(function (EventEditionMatchPlayerStat $stat) use ($category) {
            if ($category === 'goalkeeper') {
                return (int) $stat->saves > 0;
            }

            if ($category === 'player') {
                return (int) $stat->saves === 0;
            }

            return true;
        })->values();

        $sanctionsByMatch = EventEditionMatchSanction::query()
            ->where('player_id', $playerId)
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->get()
            ->groupBy('match_id');

        $matchesPlayed = (int) EventEditionMatchParticipation::query()
            ->where('player_id', $playerId)
            ->whereHas('match', fn ($q) => $q->where('edition_id', $editionId))
            ->count();

        $weights = (array) config("socialevents.rankings.{$this->weightsKey($category)}", []);

        // El ranking descuenta la penalización por sanción en cada partido con
        // estadísticas registradas. El detalle replica esa fórmula para que el
        // puntaje del modal coincida con el de la tarjeta y con la app móvil.
        $sanctionsTotal = $sanctionsByMatch->flatten()->count();
        $statRows = $stats->count();
        $sanctionApplications = $sanctionsTotal * $statRows;

        $totals = [
            'matches_played' => $matchesPlayed,
            'goals' => 0,
            'assists' => 0,
            'saves' => 0,
            'mvp' => 0,
            'clean_sheet' => 0,
            'sanctions' => 0,
            'minutes' => 0,
            'points' => 0.0,
        ];

        $breakdown = [];
        $mvpMatches = [];
        $scoringMatches = [];
        $cleanSheetMatches = [];

        foreach ($this->sortStatsChronologically($stats) as $stat) {
            $match = $stat->match;
            $sanctions = (int) $sanctionsByMatch->get($stat->match_id, collect())->count();
            $points = $this->statPoints($category, $stat, $sanctionsTotal, $weights);

            $totals['goals'] += (int) $stat->goals;
            $totals['assists'] += (int) $stat->assists;
            $totals['saves'] += (int) $stat->saves;
            $totals['minutes'] += (int) $stat->minutes_played;
            $totals['sanctions'] += $sanctions;

            if ($stat->is_mvp) {
                $totals['mvp']++;
            }

            if ($stat->clean_sheet) {
                $totals['clean_sheet']++;
            }

            if ($category !== 'scorer') {
                $totals['points'] += $points;
            }

            $label = $this->matchLabel($match);

            if ($stat->is_mvp) {
                $mvpMatches[] = $label;
            }

            if ((int) $stat->goals > 0) {
                $scoringMatches[] = [
                    'label' => $label,
                    'goals' => (int) $stat->goals,
                ];
            }

            if ($stat->clean_sheet) {
                $cleanSheetMatches[] = $label;
            }

            $breakdown[] = [
                'match_id' => (int) $stat->match_id,
                'label' => $label,
                'date' => $match?->match_date?->format('d/m/Y H:i'),
                'phase_label' => $match ? TournamentPhaseLabels::label($match->phase) : null,
                'round' => $match?->round_number,
                'score' => $match && $match->score_h !== null && $match->score_a !== null
                    ? ((int) $match->score_h).' - '.((int) $match->score_a)
                    : null,
                'status' => $match?->status,
                'goals' => (int) $stat->goals,
                'assists' => (int) $stat->assists,
                'saves' => (int) $stat->saves,
                'minutes_played' => (int) $stat->minutes_played,
                'is_mvp' => (bool) $stat->is_mvp,
                'clean_sheet' => (bool) $stat->clean_sheet,
                'sanctions' => $sanctions,
                'points' => $category === 'scorer' ? null : round($points, 2),
            ];
        }

        return [
            'category' => $category,
            'player' => [
                'id' => $playerId,
                'name' => $teamPlayer?->person?->full_name
                    ?? $stats->first()?->player?->person?->full_name
                    ?? 'Jugador',
                'photo' => TournamentMedia::url($teamPlayer?->person?->image),
                'position' => filled($teamPlayer?->position) ? (string) $teamPlayer->position : null,
                'jersey_number' => $teamPlayer?->jersey_number,
                'team_name' => $teamPlayer?->team?->name,
                'team_logo' => TournamentMedia::url($teamPlayer?->team?->logo_path),
            ],
            'summary' => [
                'matches_played' => $matchesPlayed,
                'goals' => $totals['goals'],
                'assists' => $totals['assists'],
                'saves' => $totals['saves'],
                'mvp' => $totals['mvp'],
                'clean_sheet' => $totals['clean_sheet'],
                'sanctions' => $totals['sanctions'],
                'minutes' => $totals['minutes'],
                'points' => $category === 'scorer' ? null : round($totals['points'], 2),
            ],
            'reasons' => $this->detailReasons($category, $totals, $weights, $sanctionApplications),
            'highlights' => [
                'mvp_matches' => $mvpMatches,
                'scoring_matches' => $scoringMatches,
                'clean_sheet_matches' => $cleanSheetMatches,
            ],
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Clave de configuración de pesos según la categoría.
     */
    private function weightsKey(string $category): string
    {
        return $category === 'goalkeeper' ? 'goalkeepers' : 'players';
    }

    /**
     * Puntos que aporta un partido según la categoría y las sanciones.
     */
    private function statPoints(string $category, EventEditionMatchPlayerStat $stat, int $sanctions, array $weights): float
    {
        if ($category === 'goalkeeper') {
            $points = ($stat->saves * ($weights['save'] ?? 0.5))
                + ($stat->is_mvp ? ($weights['mvp'] ?? 5) : 0)
                + ($stat->clean_sheet ? ($weights['clean_sheet'] ?? 5) : 0);
        } else {
            $points = ($stat->goals * ($weights['goal'] ?? 3))
                + ($stat->assists * ($weights['assist'] ?? 2))
                + ($stat->is_mvp ? ($weights['mvp'] ?? 5) : 0)
                + ($stat->clean_sheet ? ($weights['clean_sheet'] ?? 1) : 0);
        }

        return (float) $points - ($sanctions * ($weights['sanction_penalty'] ?? 1));
    }

    /**
     * Ordena los partidos de un jugador del más antiguo al más reciente.
     *
     * @param  Collection<int, EventEditionMatchPlayerStat>  $stats
     * @return Collection<int, EventEditionMatchPlayerStat>
     */
    private function sortStatsChronologically(Collection $stats): Collection
    {
        return $stats->sortBy(fn (EventEditionMatchPlayerStat $stat) => sprintf(
            '%s-%08d',
            $stat->match?->match_date?->format('Y-m-d H:i') ?? '9999-12-31 23:59',
            (int) $stat->match_id
        ))->values();
    }

    /**
     * Explicación legible de la posición del jugador en su ranking.
     *
     * @param  array<string, int|float>  $totals
     * @param  array<string, int|float>  $weights
     * @return array<int, array<string, mixed>>
     */
    private function detailReasons(string $category, array $totals, array $weights, int $sanctionApplications = 0): array
    {
        if ($category === 'scorer') {
            $rows = [
                [
                    'concept' => 'Goles',
                    'count' => (int) $totals['goals'],
                ],
                [
                    'concept' => 'Partidos jugados (desempate)',
                    'count' => (int) $totals['matches_played'],
                ],
                [
                    'concept' => 'Asistencias (desempate)',
                    'count' => (int) $totals['assists'],
                ],
            ];

            return array_values(array_map(
                fn (array $row) => $row + ['unit' => null, 'points' => null],
                array_filter($rows, fn (array $row) => $row['count'] > 0)
            ));
        }

        if ($category === 'goalkeeper') {
            $saveUnit = (float) ($weights['save'] ?? 0.5);
            $mvpUnit = (float) ($weights['mvp'] ?? 5);
            $cleanSheetUnit = (float) ($weights['clean_sheet'] ?? 5);
        } else {
            $saveUnit = null;
            $mvpUnit = (float) ($weights['mvp'] ?? 5);
            $cleanSheetUnit = (float) ($weights['clean_sheet'] ?? 1);
        }

        $penaltyUnit = (float) ($weights['sanction_penalty'] ?? 1);
        $reasons = [];

        // Solo se listan los conceptos con aporte real: evita filas "0 × n = 0 pts".
        if ($category === 'goalkeeper') {
            if ((int) $totals['saves'] > 0) {
                $reasons[] = [
                    'concept' => 'Atajadas',
                    'count' => (int) $totals['saves'],
                    'unit' => $saveUnit,
                    'points' => round($totals['saves'] * $saveUnit, 2),
                ];
            }
        } else {
            $goalUnit = (float) ($weights['goal'] ?? 3);
            $assistUnit = (float) ($weights['assist'] ?? 2);

            if ((int) $totals['goals'] > 0) {
                $reasons[] = [
                    'concept' => 'Goles',
                    'count' => (int) $totals['goals'],
                    'unit' => $goalUnit,
                    'points' => round($totals['goals'] * $goalUnit, 2),
                ];
            }

            if ((int) $totals['assists'] > 0) {
                $reasons[] = [
                    'concept' => 'Asistencias',
                    'count' => (int) $totals['assists'],
                    'unit' => $assistUnit,
                    'points' => round($totals['assists'] * $assistUnit, 2),
                ];
            }
        }

        if ((int) $totals['mvp'] > 0) {
            $reasons[] = [
                'concept' => 'MVP',
                'count' => (int) $totals['mvp'],
                'unit' => $mvpUnit,
                'points' => round($totals['mvp'] * $mvpUnit, 2),
            ];
        }

        if ((int) $totals['clean_sheet'] > 0) {
            $reasons[] = [
                'concept' => 'Valla invicta',
                'count' => (int) $totals['clean_sheet'],
                'unit' => $cleanSheetUnit,
                'points' => round($totals['clean_sheet'] * $cleanSheetUnit, 2),
            ];
        }

        if ($sanctionApplications > 0) {
            $reasons[] = [
                'concept' => 'Sanciones (por partido)',
                'count' => $sanctionApplications,
                'unit' => -$penaltyUnit,
                'points' => round(-1 * $sanctionApplications * $penaltyUnit, 2),
            ];
        }

        return $reasons;
    }

    /**
     * Etiqueta corta de un partido para el detalle del jugador.
     */
    private function matchLabel(?EventEditionMatch $match): string
    {
        if (! $match) {
            return 'Partido sin datos';
        }

        $home = $match->equipolocal?->name ?? $match->placeholder_h ?? 'Por definir';
        $away = $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Por definir';

        $score = ($match->score_h !== null && $match->score_a !== null)
            ? ((int) $match->score_h).' - '.((int) $match->score_a)
            : 'vs';

        return "{$home} {$score} {$away}";
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function serializePlayerRow(array $row): array
    {
        $player = $row['player'] ?? null;

        return [
            'player_id' => $player?->person_id,
            'player_name' => $player?->person?->full_name ?? 'Jugador',
            'points' => round((float) $row['points'], 2),
            'stats' => $row['stats'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function serializeGoalkeeperRow(array $row): array
    {
        $player = $row['player'] ?? null;

        return [
            'player_id' => $player?->person_id,
            'player_name' => $player?->person?->full_name ?? 'Arquero',
            'points' => round((float) $row['points'], 2),
            'stats' => $row['stats'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function serializeScorerRow(array $row): array
    {
        $player = $row['player'] ?? null;

        return [
            'player_id' => $player?->person_id,
            'player_name' => $player?->person?->full_name ?? 'Jugador',
            'goals' => (int) $row['goals'],
            'assists' => (int) ($row['assists'] ?? 0),
            'matches_played' => (int) ($row['matches_played'] ?? 0),
        ];
    }
}
