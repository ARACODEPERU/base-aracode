<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Support\TournamentMedia;

class TournamentStandingsService
{
    public function sortedTeams(int $editionId): Collection
    {
        $teams = EventEditionTeam::with(['equipo'])
            ->where('edition_id', $editionId)
            ->get();

        return $teams->sort(function (EventEditionTeam $a, EventEditionTeam $b) {
            $rankA = (int) ($a->rank ?? 0);
            $rankB = (int) ($b->rank ?? 0);

            if ($rankA > 0 && $rankB > 0 && $rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            if ((int) $a->points !== (int) $b->points) {
                return (int) $b->points <=> (int) $a->points;
            }

            if ((int) $a->goal_difference !== (int) $b->goal_difference) {
                return (int) $b->goal_difference <=> (int) $a->goal_difference;
            }

            if ((int) $a->goals_for !== (int) $b->goals_for) {
                return (int) $b->goals_for <=> (int) $a->goals_for;
            }

            return $a->team_id <=> $b->team_id;
        })->values();
    }

    /**
     * Formato compatible con GET .../standings (Flutter StandingsData).
     *
     * @return array<int, array<string, mixed>>
     */
    public function asStandingsPayload(int $editionId): array
    {
        return $this->sortedTeams($editionId)
            ->values()
            ->map(function (EventEditionTeam $team, int $index) {
                $equipo = $team->equipo;
                $position = (int) ($team->rank ?: ($index + 1));

                return [
                    'position' => $position,
                    'team_id' => $team->team_id,
                    'team_name' => $equipo ? $equipo->name : 'Equipo',
                    'team_short_name' => $equipo ? ($equipo->short_name ?? substr($equipo->name, 0, 3)) : 'EQ',
                    'team_logo' => TournamentMedia::url($equipo?->logo_path),
                    'points' => (int) $team->points,
                    'matches_played' => (int) $team->matches_played,
                    'matches_won' => (int) $team->matches_won,
                    'matches_drawn' => (int) $team->matches_drawn,
                    'matches_lost' => (int) $team->matches_lost,
                    'goals_for' => (int) $team->goals_for,
                    'goals_against' => (int) $team->goals_against,
                    'goal_difference' => (int) $team->goal_difference,
                ];
            })
            ->all();
    }

    /**
     * Formato compatible con event/current rankings (Flutter RankingData).
     *
     * @return array<int, array<string, mixed>>
     */
    public function asRankingsPayload(int $editionId): array
    {
        return $this->sortedTeams($editionId)
            ->values()
            ->map(function (EventEditionTeam $team, int $index) {
                $equipo = $team->equipo;

                return [
                    'rank' => (int) ($team->rank ?: ($index + 1)),
                    'team_id' => $team->team_id,
                    'team_name' => $equipo?->name ?? 'Equipo',
                    'team_short_name' => $equipo?->short_name ?? '',
                    'team_logo' => TournamentMedia::url($equipo?->logo_path),
                    'matches_played' => (int) $team->matches_played,
                    'matches_won' => (int) $team->matches_won,
                    'matches_drawn' => (int) $team->matches_drawn,
                    'matches_lost' => (int) $team->matches_lost,
                    'goals_for' => (int) $team->goals_for,
                    'goals_against' => (int) $team->goals_against,
                    'goal_difference' => (int) $team->goal_difference,
                    'points' => (int) $team->points,
                    'is_champion' => (bool) $team->is_champion,
                ];
            })
            ->all();
    }
}
