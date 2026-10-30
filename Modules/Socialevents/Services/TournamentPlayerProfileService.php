<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Support\TournamentDateLabels;
use Modules\Socialevents\Support\TournamentMatchResultPresenter;
use Modules\Socialevents\Support\TournamentMedia;
use Modules\Socialevents\Support\TournamentPhaseLabels;

/**
 * Ficha pública de un jugador de la edición.
 *
 * No guarda ni recalcula estadísticas por su cuenta: lee los mismos registros
 * que alimentan los rankings (acta del partido, participaciones y sanciones) y
 * usa el presentador de resultados para no contradecir el fixture. Devuelve
 * solo arreglos de escalares para poder cachearse como el resto de la landing.
 */
class TournamentPlayerProfileService
{
    private const SANCTION_LABELS = [
        'yellow' => 'Amarilla',
        'red' => 'Roja',
        'double_yellow' => 'Doble amarilla',
    ];

    public function __construct(private TournamentRankingsService $rankingsService)
    {
    }

    /**
     * @return array<string, mixed>|null null si el jugador no pertenece a la edición
     */
    public function build(int $editionId, int $playerId): ?array
    {
        $teamPlayer = EventEditionTeamPlayer::with(['person', 'team'])
            ->where('edition_id', $editionId)
            ->where('person_id', $playerId)
            ->first();

        $participations = EventEditionMatchParticipation::with([
            'match.equipolocal',
            'match.equipovisitante',
        ])
            ->where('player_id', $playerId)
            ->whereHas('match', fn ($query) => $query->where('edition_id', $editionId))
            ->get();

        $stats = EventEditionMatchPlayerStat::with([
            'match.equipolocal',
            'match.equipovisitante',
        ])
            ->where('player_id', $playerId)
            ->whereHas('match', fn ($query) => $query->where('edition_id', $editionId))
            ->get()
            ->keyBy('match_id');

        if (! $teamPlayer && $participations->isEmpty() && $stats->isEmpty()) {
            return null;
        }

        // Partidos con acta o con participación. Se unen ambos orígenes para no
        // perder encuentros antiguos registrados solo en una de las dos tablas.
        $matches = $participations
            ->map(fn (EventEditionMatchParticipation $participation) => $participation->match)
            ->filter()
            ->keyBy('id');

        foreach ($stats as $stat) {
            if ($stat->match && ! $matches->has($stat->match_id)) {
                $matches->put($stat->match_id, $stat->match);
            }
        }

        $matches = $matches->sortBy(fn (EventEditionMatch $match) => sprintf(
            '%s-%08d',
            $match->match_date?->format('Y-m-d H:i') ?? '9999-12-31 23:59',
            $match->id
        ));

        $sanctionsByMatch = EventEditionMatchSanction::query()
            ->where('player_id', $playerId)
            ->whereHas('match', fn ($query) => $query->where('edition_id', $editionId))
            ->get(['match_id', 'type'])
            ->groupBy('match_id');

        $rows = [];
        $totals = ['goals' => 0, 'assists' => 0, 'mvp' => 0, 'sanctions' => 0];

        foreach ($matches as $match) {
            /** @var EventEditionMatchPlayerStat|null $stat */
            $stat = $stats->get($match->id);
            $sanctions = $sanctionsByMatch->get($match->id, collect())
                ->map(fn (EventEditionMatchSanction $sanction) => [
                    'type' => $sanction->type,
                    'label' => self::SANCTION_LABELS[$sanction->type] ?? Str::headline((string) $sanction->type),
                ])
                ->values()
                ->all();

            $goals = (int) ($stat->goals ?? 0);
            $assists = (int) ($stat->assists ?? 0);
            $isMvp = (bool) ($stat->is_mvp ?? false);

            $totals['goals'] += $goals;
            $totals['assists'] += $assists;
            $totals['mvp'] += $isMvp ? 1 : 0;
            $totals['sanctions'] += count($sanctions);

            $home = $match->equipolocal?->name ?? $match->placeholder_h ?? 'Por definir';
            $away = $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Por definir';
            $played = $match->score_h !== null && $match->score_a !== null;
            $presented = TournamentMatchResultPresenter::forMatch($match);

            // Lado del jugador: el equipo de su estadística manda; si el acta no
            // la tiene, se usa el equipo con el que está inscrito en la edición.
            $playerTeamId = (int) ($stat->team_id ?? $teamPlayer?->team_id ?? 0);
            $side = $playerTeamId === (int) $match->team_h_id ? 'home' : 'away';

            $rows[] = [
                'match_id' => (int) $match->id,
                'date' => $match->match_date
                    ? TournamentDateLabels::shortDate($match->match_date)
                    : null,
                'time' => $match->match_date
                    ? TournamentDateLabels::time($match->match_date)
                    : null,
                'home' => $home,
                'away' => $away,
                'score' => $played ? $presented['final_label'] : null,
                'shootout' => $presented['shootout'] !== null,
                'phase' => TournamentPhaseLabels::label($match->phase),
                'round' => $match->round_number,
                'venue' => $match->location,
                'side' => $side,
                'goals' => $goals,
                'assists' => $assists,
                'is_mvp' => $isMvp,
                'sanctions' => $sanctions,
                'sanction_count' => count($sanctions),
            ];
        }

        return [
            'player' => $this->playerPayload($teamPlayer, $playerId, $stats, $totals, count($rows)),
            'scorers' => $this->scorersPayload($editionId, $playerId, $totals, count($rows)),
            'awards' => $this->awardsPayload($editionId, $playerId),
            'matches' => $rows,
            'scoring_matches' => array_values(array_filter($rows, fn (array $row) => $row['goals'] > 0)),
            'mvp_matches' => array_values(array_filter($rows, fn (array $row) => $row['is_mvp'])),
        ];
    }

    /**
     * @param  Collection<int, EventEditionMatchPlayerStat>  $stats
     * @param  array<string, int>  $totals
     * @return array<string, mixed>
     */
    private function playerPayload(
        ?EventEditionTeamPlayer $teamPlayer,
        int $playerId,
        Collection $stats,
        array $totals,
        int $matchesPlayed
    ): array {
        $person = $teamPlayer?->person ?? $stats->first()?->player?->person;
        $name = $person?->full_name ?: 'Jugador';

        return [
            'id' => $playerId,
            'name' => $name,
            'initials' => $this->initials($name),
            'photo' => TournamentMedia::url($person?->image),
            'team_name' => $teamPlayer?->team?->name,
            'team_logo' => TournamentMedia::url($teamPlayer?->team?->logo_path),
            'position' => filled($teamPlayer?->position) ? (string) $teamPlayer->position : null,
            'jersey_number' => filled($teamPlayer?->jersey_number) ? (string) $teamPlayer->jersey_number : null,
            'matches_played' => $matchesPlayed,
            'goals' => $totals['goals'],
            'assists' => $totals['assists'],
            'mvp' => $totals['mvp'],
            'sanctions' => $totals['sanctions'],
        ];
    }

    /**
     * Posición real en la tabla de goleadores y los criterios de desempate que
     * aplica el sistema: goles desc, menos partidos jugados, más asistencias.
     *
     * @param  array<string, int>  $totals
     * @return array<string, mixed>
     */
    private function scorersPayload(int $editionId, int $playerId, array $totals, int $matchesPlayed): array
    {
        $table = $this->rankingsService->scorersTable($editionId);
        $index = $table->search(
            fn (array $row) => (int) ($row['player']?->person_id ?? 0) === $playerId
        );
        $inTable = $index !== false;

        $criteria = [
            [
                'concept' => 'Goles',
                'value' => $totals['goals'],
                'note' => 'Criterio principal del ranking',
            ],
            [
                'concept' => 'Partidos jugados',
                'value' => $matchesPlayed,
                'note' => 'Primer desempate: menos partidos',
            ],
            [
                'concept' => 'Asistencias',
                'value' => $totals['assists'],
                'note' => 'Segundo desempate: más asistencias',
            ],
        ];

        return [
            'in_table' => $inTable,
            'position' => $inTable ? $index + 1 : null,
            'total' => $table->count(),
            'criteria' => $criteria,
            // Solo se muestran los criterios cuando el jugador anotó: si no,
            // la sección cae en su estado vacío y no parece una clasificación.
            'has_goals' => $totals['goals'] > 0,
        ];
    }

    /**
     * Reconocimientos publicados de la edición para este jugador.
     *
     * @return array<int, array<string, mixed>>
     */
    private function awardsPayload(int $editionId, int $playerId): array
    {
        return EventEditionAward::query()
            ->where('edition_id', $editionId)
            ->where('person_id', $playerId)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['award_key', 'label', 'place'])
            ->map(fn (EventEditionAward $award) => [
                'key' => $award->award_key,
                'label' => $award->label,
                'place' => $award->place,
            ])
            ->all();
    }

    private function initials(string $name): string
    {
        $initials = Str::of($name)
            ->squish()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'J';
    }
}
