<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventTeam;
use RuntimeException;

class EditionTeamService
{
    public function listForEdition(int $editionId): array
    {
        EventEdition::findOrFail($editionId);

        return EventEditionTeam::with('equipo')
            ->where('edition_id', $editionId)
            ->orderByDesc('points')
            ->orderByDesc('goal_difference')
            ->orderByDesc('goals_for')
            ->get()
            ->map(fn (EventEditionTeam $row) => $this->formatEditionTeam($row))
            ->values()
            ->all();
    }

    public function assign(int $editionId, int $teamId): EventEditionTeam
    {
        EventEdition::findOrFail($editionId);
        EventTeam::findOrFail($teamId);

        $exists = EventEditionTeam::where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->exists();

        if ($exists) {
            throw new RuntimeException('El equipo ya está inscrito en esta edición.');
        }

        return EventEditionTeam::create([
            'edition_id' => $editionId,
            'team_id' => $teamId,
        ]);
    }

    public function unassign(int $editionId, int $teamId): void
    {
        DB::transaction(function () use ($editionId, $teamId) {
            $item = EventEditionTeam::where('edition_id', $editionId)
                ->where('team_id', $teamId)
                ->firstOrFail();

            $item->delete();
        });
    }

    public function formatEditionTeam(EventEditionTeam $row): array
    {
        $team = $row->equipo;

        return [
            'edition_id' => $row->edition_id,
            'team_id' => $team->id,
            'id' => $team->id,
            'name' => $team->name,
            'short_name' => $team->short_name,
            'logo_url' => $team->logo_path ? asset('storage/'.$team->logo_path) : null,
            'points' => $row->points ?? 0,
            'matches_played' => $row->matches_played ?? 0,
            'matches_won' => $row->matches_won ?? 0,
            'matches_drawn' => $row->matches_drawn ?? 0,
            'matches_lost' => $row->matches_lost ?? 0,
            'goals_for' => $row->goals_for ?? 0,
            'goals_against' => $row->goals_against ?? 0,
            'goal_difference' => $row->goal_difference ?? 0,
            'rank' => $row->rank,
        ];
    }
}
