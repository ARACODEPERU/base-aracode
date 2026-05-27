<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Support\TournamentMedia;

class TournamentFixtureService
{
    /**
     * Fixture agrupado por fase y fecha (landing).
     */
    public function groupedFixture(int $editionId): Collection
    {
        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId)
            ->orderByRaw("CASE
                WHEN match_date >= NOW() THEN 1
                WHEN match_date < NOW() THEN 2
                ELSE 3 END ASC")
            ->orderBy('match_date', 'asc')
            ->get();

        return $matches->groupBy(['phase', 'round_number'])->map(
            fn ($rounds) => $rounds->sortKeys()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function upcomingPayload(int $editionId, int $limit = 20): array
    {
        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId)
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('match_date')
                        ->where('match_date', '>=', now());
                })->orWhere(function ($q) {
                    $q->whereNull('match_date')
                        ->where(function ($inner) {
                            $inner->whereNotNull('placeholder_h')
                                ->orWhereNotNull('placeholder_a');
                        });
                });
            })
            ->orderByRaw('CASE WHEN match_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('match_date', 'asc')
            ->limit($limit)
            ->get();

        return $matches->map(fn (EventEditionMatch $match) => $this->serializeMatch($match))->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentResultsPayload(int $editionId): array
    {
        $lastRoundWithClosed = EventEditionMatch::query()
            ->where('edition_id', $editionId)
            ->where('status', 'closed')
            ->max('round_number');

        if (! $lastRoundWithClosed) {
            return [];
        }

        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId)
            ->where('round_number', $lastRoundWithClosed)
            ->where('status', 'closed')
            ->orderBy('match_date', 'desc')
            ->get();

        return $matches->map(function (EventEditionMatch $match) {
            $payload = $this->serializeMatch($match);
            $payload['score_h'] = $match->score_h;
            $payload['score_a'] = $match->score_a;
            $payload['match_date_formatted'] = $match->match_date
                ? $match->match_date->format('d/m/Y')
                : null;

            return $payload;
        })->all();
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, int|float>}
     */
    public function paginatedPayload(
        int $editionId,
        string $filter = 'all',
        int $page = 1,
        int $perPage = 10
    ): array {
        $query = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId);

        if ($filter === 'played') {
            $query->where('status', 'closed');
        } elseif ($filter === 'pending') {
            $query->where('status', 'pending');
        }

        $query->orderByRaw('CASE WHEN match_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('match_date', 'asc');

        $total = (clone $query)->count();
        $matches = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
        $lastPage = (int) max(1, ceil($total / $perPage));

        return [
            'data' => $matches->map(fn (EventEditionMatch $match) => $this->serializeMatchDetailed($match))->all(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMatch(EventEditionMatch $match): array
    {
        $hasLocalTeam = $match->equipolocal !== null;
        $hasVisitorTeam = $match->equipovisitante !== null;

        return [
            'id' => $match->id,
            'team_h_id' => $match->team_h_id,
            'team_h_name' => $hasLocalTeam
                ? $match->equipolocal->name
                : ($match->placeholder_h ?? 'Equipo Local'),
            'team_h_short_name' => $hasLocalTeam
                ? $match->equipolocal->short_name
                : ($match->placeholder_h ?? 'LOC'),
            'team_h_logo' => $hasLocalTeam
                ? TournamentMedia::url($match->equipolocal->logo_path)
                : null,
            'team_h_has_placeholder' => ! $hasLocalTeam && $match->placeholder_h !== null,
            'team_a_id' => $match->team_a_id,
            'team_a_name' => $hasVisitorTeam
                ? $match->equipovisitante->name
                : ($match->placeholder_a ?? 'Equipo Visitante'),
            'team_a_short_name' => $hasVisitorTeam
                ? $match->equipovisitante->short_name
                : ($match->placeholder_a ?? 'VIS'),
            'team_a_logo' => $hasVisitorTeam
                ? TournamentMedia::url($match->equipovisitante->logo_path)
                : null,
            'team_a_has_placeholder' => ! $hasVisitorTeam && $match->placeholder_a !== null,
            'match_date' => $match->match_date?->format('Y-m-d H:i:s'),
            'match_date_formatted' => $match->match_date
                ? $match->match_date->format('d MMM - HH:mm')
                : 'Horario por definir',
            'location' => $match->location,
            'phase' => $match->phase,
            'round' => $match->round_number,
            'status' => $match->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMatchDetailed(EventEditionMatch $match): array
    {
        $payload = $this->serializeMatch($match);
        $payload['score_h'] = $match->score_h;
        $payload['score_a'] = $match->score_a;
        $payload['match_date_formatted'] = $match->match_date
            ? $match->match_date->format('d/m/Y H:i')
            : 'Sin fecha';

        return $payload;
    }
}
