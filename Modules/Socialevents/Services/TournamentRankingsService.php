<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;

class TournamentRankingsService
{
    /**
     * Ranking de jugadores de campo (misma fórmula que la landing).
     */
    public function topPlayers(int $editionId, ?int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('socialevents.rankings.top_limit', 5);
        $weights = config('socialevents.rankings.players', []);

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
}
