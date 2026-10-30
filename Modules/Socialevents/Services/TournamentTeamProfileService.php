<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionPlayerSuspension;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Support\TournamentDateLabels;
use Modules\Socialevents\Support\TournamentMatchResultPresenter;
use Modules\Socialevents\Support\TournamentMedia;
use Modules\Socialevents\Support\TournamentPhaseLabels;

/**
 * Ficha pública de un equipo (promoción) en una edición.
 *
 * La clasificación y los totales salen de `event_edition_teams`, que es la
 * tabla oficial que alimenta la landing. Los goles, asistencias, MVP y tarjetas
 * se leen de las actas y sanciones del partido, sin recalcular ni duplicar
 * eventos. La cronología se arma solo con partidos confirmados.
 */
class TournamentTeamProfileService
{
    public function __construct(private TournamentStandingsService $standingsService)
    {
    }

    private const SANCTION_LABELS = [
        'yellow' => 'Amarilla',
        'red' => 'Roja',
        'double_yellow' => 'Doble amarilla',
    ];

    /**
     * @return array<string, mixed>|null null si el equipo no participa de la edición
     */
    public function build(EventEdition $edition, int $teamId): ?array
    {
        $editionId = (int) $edition->id;

        $editionTeam = EventEditionTeam::with('equipo')
            ->where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->first();

        if (! $editionTeam) {
            return null;
        }

        $team = $editionTeam->equipo;
        $teamName = $team?->name ?: 'Equipo';

        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId)
            ->where(function ($query) use ($teamId) {
                $query->where('team_h_id', $teamId)->orWhere('team_a_id', $teamId);
            })
            ->orderByRaw('match_date IS NULL')
            ->orderBy('match_date')
            ->orderBy('id')
            ->get();

        $teamMatchIds = $matches->pluck('id')->map(fn ($id) => (int) $id)->all();

        $squad = EventEditionTeamPlayer::with('person')
            ->where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->get();

        $playerIds = $squad->pluck('person_id')->map(fn ($id) => (int) $id)->all();

        $stats = EventEditionMatchPlayerStat::query()
            ->where('team_id', $teamId)
            ->whereHas('match', fn ($query) => $query->where('edition_id', $editionId))
            ->get(['match_id', 'player_id', 'goals', 'assists', 'is_mvp']);

        $participations = EventEditionMatchParticipation::query()
            ->whereIn('match_id', $teamMatchIds)
            ->whereIn('player_id', $playerIds)
            ->get(['match_id', 'player_id']);

        $sanctions = EventEditionMatchSanction::with(['match.equipolocal', 'match.equipovisitante'])
            ->whereIn('player_id', $playerIds)
            ->whereIn('match_id', $teamMatchIds)
            ->get();

        $suspensions = EventEditionPlayerSuspension::query()
            ->where('edition_id', $editionId)
            ->whereIn('player_id', $playerIds)
            ->orderByDesc('suspended_at')
            ->orderByDesc('id')
            ->get();

        // Solo se enlaza a fichas de equipos que realmente juegan esta edición.
        $editionTeamIds = EventEditionTeam::query()
            ->where('edition_id', $editionId)
            ->pluck('team_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $squadPayload = $this->squadPayload($squad, $stats, $participations, $sanctions, $edition);
        $matchRows = $this->matchRows($matches, $teamId, $editionTeamIds, $edition);
        $discipline = $this->disciplinePayload($sanctions, $suspensions, $squad);

        $finalized = $edition->status === 'finished';
        $isChampion = (bool) $editionTeam->is_champion;

        // El puesto se toma del mismo ordenamiento que pinta la tabla pública,
        // no de un `rank` que puede haber quedado sin recalcular.
        $standings = $this->standingsService->sortedTeams($editionId);
        $position = $standings->search(fn (EventEditionTeam $row) => (int) $row->team_id === $teamId);
        $rank = $position !== false ? $position + 1 : (int) ($editionTeam->rank ?? 0);

        return [
            'team' => [
                'id' => $teamId,
                'name' => $teamName,
                'short_name' => $team?->short_name,
                'logo' => TournamentMedia::url($team?->logo_path),
                'initials' => $this->initials($teamName),
                'is_champion' => $isChampion,
                'rank' => $rank > 0 ? $rank : null,
                'rank_label' => $rank > 0 ? $this->ordinal($rank) : null,
                'position_label' => $finalized ? 'Puesto final' : 'Posición actual',
                'finalized' => $finalized,
                'honour' => $this->honourLabel($isChampion, $finalized, $rank),
            ],
            'record' => [
                'played' => (int) $editionTeam->matches_played,
                'won' => (int) $editionTeam->matches_won,
                'drawn' => (int) $editionTeam->matches_drawn,
                'lost' => (int) $editionTeam->matches_lost,
                'goals_for' => (int) $editionTeam->goals_for,
                'goals_against' => (int) $editionTeam->goals_against,
                'goal_difference' => (int) $editionTeam->goal_difference,
                'points' => $editionTeam->totalPoints(),
                'bonus_points' => (int) $editionTeam->bonus_points,
                'sanctions' => $discipline['cards'],
            ],
            'squad' => $squadPayload,
            'squad_summary' => [
                'registered' => count($squadPayload),
                'played' => count(array_filter($squadPayload, fn (array $player) => $player['played'] > 0)),
            ],
            'leaders' => $this->leaders($squadPayload),
            'honours' => $this->honours($editionId, $teamId, $teamName, $isChampion, $finalized, $rank),
            'discipline' => $discipline,
            'matches' => $matchRows,
            'timeline' => $this->timeline($matchRows, $isChampion, $finalized),
        ];
    }

    /**
     * Jugadores inscritos en la edición para este equipo, con sus totales.
     *
     * @param  Collection<int, EventEditionTeamPlayer>  $squad
     * @param  Collection<int, EventEditionMatchPlayerStat>  $stats
     * @param  Collection<int, EventEditionMatchParticipation>  $participations
     * @param  Collection<int, EventEditionMatchSanction>  $sanctions
     * @return array<int, array<string, mixed>>
     */
    private function squadPayload(
        Collection $squad,
        Collection $stats,
        Collection $participations,
        Collection $sanctions,
        EventEdition $edition
    ): array {
        $statsByPlayer = $stats->groupBy('player_id');
        $playedByPlayer = $participations->groupBy('player_id')
            ->map(fn (Collection $rows) => $rows->pluck('match_id')->unique()->count())
            ->all();
        $sanctionsByPlayer = $sanctions->groupBy('player_id');

        return $squad
            ->map(function (EventEditionTeamPlayer $player) use ($statsByPlayer, $playedByPlayer, $sanctionsByPlayer, $edition) {
                $person = $player->person;
                $name = $person?->full_name ?: 'Jugador';
                $playerId = (int) $player->person_id;
                $rows = $statsByPlayer->get($playerId, collect());
                $statMatches = $rows->pluck('match_id')->unique()->count();
                $played = max((int) ($playedByPlayer[$playerId] ?? 0), $statMatches, $rows->count());

                return [
                    'id' => $playerId,
                    'name' => $name,
                    'initials' => $this->initials($name),
                    'photo' => TournamentMedia::url($person?->image),
                    'jersey_number' => filled($player->jersey_number) ? (string) $player->jersey_number : null,
                    'position' => filled($player->position) ? (string) $player->position : null,
                    'played' => $played,
                    'goals' => (int) $rows->sum('goals'),
                    'assists' => (int) $rows->sum('assists'),
                    'mvp' => $rows->filter(fn ($row) => (bool) $row->is_mvp)->count(),
                    'sanctions' => (int) ($sanctionsByPlayer->get($playerId)?->count() ?? 0),
                    'profile_url' => $played > 0
                        ? route('socialevents_torneos_player_profile', [
                            'slug' => $edition->landingSlug(),
                            'playerId' => $playerId,
                        ])
                        : null,
                ];
            })
            ->sort(function (array $a, array $b) {
                $aNumber = $a['jersey_number'] !== null ? (int) $a['jersey_number'] : 9999;
                $bNumber = $b['jersey_number'] !== null ? (int) $b['jersey_number'] : 9999;

                return $aNumber === $bNumber
                    ? strcasecmp($a['name'], $b['name'])
                    : $aNumber <=> $bNumber;
            })
            ->values()
            ->all();
    }

    /**
     * Historial cronológico con el resultado real (incluida la tanda).
     *
     * @param  Collection<int, EventEditionMatch>  $matches
     * @param  array<int, int>  $editionTeamIds
     * @return array<int, array<string, mixed>>
     */
    private function matchRows(Collection $matches, int $teamId, array $editionTeamIds, EventEdition $edition): array
    {
        $rows = [];

        foreach ($matches as $match) {
            $isHome = (int) $match->team_h_id === $teamId;
            $rival = $isHome ? $match->equipovisitante : $match->equipolocal;
            $rivalId = $isHome ? (int) $match->team_a_id : (int) $match->team_h_id;
            $rivalName = $rival?->name
                ?? ($isHome ? $match->placeholder_a : $match->placeholder_h)
                ?? 'Rival por definir';

            $presented = TournamentMatchResultPresenter::forMatch($match);
            $played = in_array($match->status, TournamentMatchResultPresenter::PLAYED_STATUSES, true)
                && $match->score_h !== null
                && $match->score_a !== null;

            $outcome = 'pending';

            if ($played) {
                if ($match->status === 'no_points') {
                    $outcome = 'no_points';
                } else {
                    $winnerSide = $presented['shootout']['winner_side'] ?? null;

                    if ($winnerSide !== null) {
                        $outcome = ($winnerSide === 'home') === $isHome ? 'win' : 'loss';
                    } elseif ($presented['final_h'] === $presented['final_a']) {
                        $outcome = 'draw';
                    } else {
                        $teamScore = $isHome ? $presented['final_h'] : $presented['final_a'];
                        $rivalScore = $isHome ? $presented['final_a'] : $presented['final_h'];
                        $outcome = $teamScore > $rivalScore ? 'win' : 'loss';
                    }
                }
            }

            $phase = TournamentPhaseLabels::label($match->phase);

            $rows[] = [
                'id' => (int) $match->id,
                'date' => $match->match_date ? TournamentDateLabels::shortDate($match->match_date) : null,
                'time' => $match->match_date ? TournamentDateLabels::time($match->match_date) : null,
                'phase' => $phase,
                'phase_key' => TournamentPhaseLabels::canonical($match->phase),
                'round' => $match->round_number,
                'group' => $match->group_name,
                'is_home' => $isHome,
                'side_label' => $isHome ? 'Local' : 'Visitante',
                'rival' => $rivalName,
                'rival_id' => $rivalId > 0 ? $rivalId : null,
                'rival_logo' => TournamentMedia::url($rival?->logo_path),
                'rival_url' => $rivalId > 0 && in_array($rivalId, $editionTeamIds, true)
                    ? route('socialevents_torneos_team_profile', [
                        'slug' => $edition->landingSlug(),
                        'teamId' => $rivalId,
                    ])
                    : null,
                'home' => $match->equipolocal?->name ?? $match->placeholder_h ?? 'Por definir',
                'away' => $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Por definir',
                'score' => $played ? $presented['final_label'] : null,
                'team_score' => $played ? ($isHome ? $presented['final_h'] : $presented['final_a']) : null,
                'rival_score' => $played ? ($isHome ? $presented['final_a'] : $presented['final_h']) : null,
                'shootout' => $presented['shootout'] !== null,
                'played' => $played,
                'outcome' => $outcome,
                'outcome_label' => $this->outcomeLabel($outcome),
                'venue' => $match->location,
            ];
        }

        return $rows;
    }

    /**
     * Máximos goleadores, asistidores y MVP del plantel.
     *
     * @param  array<int, array<string, mixed>>  $squad
     * @return array<int, array<string, mixed>>
     */
    private function leaders(array $squad): array
    {
        $definitions = [
            ['key' => 'goals', 'label' => 'Máximo goleador', 'icon' => 'fa-futbol', 'tone' => 'green'],
            ['key' => 'assists', 'label' => 'Más asistencias', 'icon' => 'fa-handshake', 'tone' => 'teal'],
            ['key' => 'mvp', 'label' => 'Más premios MVP', 'icon' => 'fa-star', 'tone' => 'gold'],
        ];

        $leaders = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $max = (int) collect($squad)->max($key);

            if ($max <= 0) {
                continue;
            }

            $players = collect($squad)
                ->filter(fn (array $player) => (int) $player[$key] === $max)
                ->map(fn (array $player) => [
                    'id' => $player['id'],
                    'name' => $player['name'],
                    'photo' => $player['photo'],
                    'initials' => $player['initials'],
                    'jersey_number' => $player['jersey_number'],
                    'profile_url' => $player['profile_url'],
                    'value' => $max,
                ])
                ->sortBy('name')
                ->values()
                ->all();

            $leaders[] = [
                'key' => $key,
                'label' => $definition['label'],
                'icon' => $definition['icon'],
                'tone' => $definition['tone'],
                'value' => $max,
                'players' => $players,
            ];
        }

        return $leaders;
    }

    /**
     * Reconocimientos oficiales: título, puesto final y premios publicados.
     *
     * @return array<int, array<string, string>>
     */
    private function honours(
        int $editionId,
        int $teamId,
        string $teamName,
        bool $isChampion,
        bool $finalized,
        int $rank
    ): array {
        $honours = [];

        if ($isChampion) {
            $honours[] = [
                'key' => 'champion',
                'label' => 'Campeón',
                'description' => 'Título oficial de la edición.',
                'tone' => 'gold',
                'icon' => 'fa-trophy',
            ];
        } elseif ($finalized && $rank === 2) {
            $honours[] = [
                'key' => 'runner_up',
                'label' => 'Subcampeón',
                'description' => 'Segundo puesto de la clasificación final.',
                'tone' => 'silver',
                'icon' => 'fa-medal',
            ];
        } elseif ($finalized && $rank === 3) {
            $honours[] = [
                'key' => 'third',
                'label' => 'Tercer puesto',
                'description' => 'Tercer lugar de la clasificación final.',
                'tone' => 'bronze',
                'icon' => 'fa-medal',
            ];
        }

        $awards = EventEditionAward::query()
            ->where('edition_id', $editionId)
            ->where('recipient_type', 'team')
            ->where('is_published', true)
            ->where(function ($query) use ($teamId, $teamName) {
                $query->where('team_id', $teamId)->orWhere('team_name', $teamName);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['award_key', 'label', 'place']);

        foreach ($awards as $award) {
            // El campeonato ya se muestra con su propia insignia.
            if ($award->award_key === 'champion' && $isChampion) {
                continue;
            }

            $honours[] = [
                'key' => (string) $award->award_key,
                'label' => (string) $award->label,
                'description' => $award->place ? 'Reconocimiento oficial · '.$this->ordinal((int) $award->place).' lugar' : 'Reconocimiento oficial de la edición.',
                'tone' => 'green',
                'icon' => 'fa-award',
            ];
        }

        return $honours;
    }

    /**
     * Tarjetas, jugadores sancionados y suspensiones, sin duplicar conceptos.
     *
     * @param  Collection<int, EventEditionMatchSanction>  $sanctions
     * @param  Collection<int, EventEditionPlayerSuspension>  $suspensions
     * @param  Collection<int, EventEditionTeamPlayer>  $squad
     * @return array<string, mixed>
     */
    private function disciplinePayload(Collection $sanctions, Collection $suspensions, Collection $squad): array
    {
        $names = $squad->mapWithKeys(fn (EventEditionTeamPlayer $player) => [
            (int) $player->person_id => $player->person?->full_name ?: 'Jugador',
        ]);

        $rows = $sanctions
            ->map(function (EventEditionMatchSanction $sanction) use ($names) {
                $match = $sanction->match;

                return [
                    'player' => $names->get((int) $sanction->player_id, 'Jugador'),
                    'type' => (string) $sanction->type,
                    'label' => self::SANCTION_LABELS[$sanction->type] ?? Str::headline((string) $sanction->type),
                    'minute' => $sanction->minute !== null ? (int) $sanction->minute : null,
                    'date' => $match?->match_date ? TournamentDateLabels::shortDate($match->match_date) : null,
                    'phase' => $match ? TournamentPhaseLabels::label($match->phase) : null,
                    'match' => $match
                        ? (($match->equipolocal?->name ?? $match->placeholder_h ?? 'Local')
                            .' vs '
                            .($match->equipovisitante?->name ?? $match->placeholder_a ?? 'Visitante'))
                        : null,
                    'match_id' => $match?->id,
                ];
            })
            ->sortBy(fn (array $row) => sprintf('%s-%08d', $row['date'] ?? '9999', $row['match_id'] ?? 0))
            ->values()
            ->all();

        $suspensionRows = $suspensions
            ->map(fn (EventEditionPlayerSuspension $suspension) => [
                'player' => $names->get((int) $suspension->player_id, 'Jugador'),
                'type' => (string) $suspension->type,
                'label' => $suspension->type_label,
                'status' => $suspension->status_label,
                'from' => $suspension->starts_at?->format('d/m/Y'),
                'to' => $suspension->ends_at?->format('d/m/Y'),
            ])
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'suspensions' => $suspensionRows,
            'cards' => count($rows),
            'players' => collect($rows)->pluck('player')->unique()->count(),
            'suspension_count' => count($suspensionRows),
            'yellow' => collect($rows)->where('type', 'yellow')->count(),
            'red' => collect($rows)->where('type', 'red')->count(),
            'double_yellow' => collect($rows)->where('type', 'double_yellow')->count(),
        ];
    }

    /**
     * Cronología factual: debuts, primeras victorias, cambios de fase, final y
     * título. No se agregan crónicas ni declaraciones que no existan.
     *
     * @param  array<int, array<string, mixed>>  $matches
     * @return array<int, array<string, mixed>>
     */
    private function timeline(array $matches, bool $isChampion, bool $finalized): array
    {
        $played = array_values(array_filter($matches, fn (array $match) => $match['played']));

        if ($played === []) {
            return [];
        }

        $milestones = [];
        $first = $played[0];

        $milestones[] = [
            'type' => 'debut',
            'title' => 'Debut en el torneo',
            'description' => $this->resultSentence($first, 'primer partido'),
            'date' => $first['date'],
            'phase' => $first['phase'],
            'score' => $first['score'],
            'rival' => $first['rival'],
            'tone' => 'blue',
            'icon' => 'fa-flag',
        ];

        $firstWin = collect($played)->firstWhere('outcome', 'win');

        if ($firstWin && $firstWin['id'] !== $first['id']) {
            $milestones[] = [
                'type' => 'first_win',
                'title' => 'Primera victoria',
                'description' => $this->resultSentence($firstWin, 'primera victoria'),
                'date' => $firstWin['date'],
                'phase' => $firstWin['phase'],
                'score' => $firstWin['score'],
                'rival' => $firstWin['rival'],
                'tone' => 'green',
                'icon' => 'fa-thumbs-up',
            ];
        }

        $previousPhase = $first['phase_key'];
        $seenPhases = [$previousPhase => true];

        foreach (array_slice($played, 1) as $match) {
            $phaseKey = $match['phase_key'];

            if ($phaseKey === null || $phaseKey === $previousPhase || isset($seenPhases[$phaseKey])) {
                $previousPhase = $phaseKey;
                continue;
            }

            $seenPhases[$phaseKey] = true;
            $previousPhase = $phaseKey;

            $milestones[] = [
                'type' => 'phase',
                'title' => 'Llegada a '.$match['phase'],
                'description' => $this->resultSentence($match, 'primer partido de '.$match['phase']),
                'date' => $match['date'],
                'phase' => $match['phase'],
                'score' => $match['score'],
                'rival' => $match['rival'],
                'tone' => 'teal',
                'icon' => 'fa-road',
            ];
        }

        $finalMatch = collect($played)->firstWhere('phase_key', 'final');

        if ($finalMatch && $finalMatch['id'] !== $first['id']) {
            $milestones[] = [
                'type' => 'final',
                'title' => 'Final del torneo',
                'description' => $this->resultSentence($finalMatch, 'final'),
                'date' => $finalMatch['date'],
                'phase' => $finalMatch['phase'],
                'score' => $finalMatch['score'],
                'rival' => $finalMatch['rival'],
                'tone' => 'gold',
                'icon' => 'fa-trophy',
            ];
        }

        if ($isChampion) {
            $titleMatch = $finalMatch ?? end($played);

            $milestones[] = [
                'type' => 'champion',
                'title' => 'Campeón de la edición',
                'description' => $finalized
                    ? 'El equipo terminó en el primer lugar de la clasificación final.'
                    : 'El título figura registrado en la edición.',
                'date' => $titleMatch['date'] ?? null,
                'phase' => $titleMatch['phase'] ?? null,
                'score' => $titleMatch['score'] ?? null,
                'rival' => $titleMatch['rival'] ?? null,
                'tone' => 'gold',
                'icon' => 'fa-crown',
            ];
        }

        return $milestones;
    }

    /**
     * @param  array<string, mixed>  $match
     */
    private function resultSentence(array $match, string $context): string
    {
        $score = $match['score'] ? ' ('.$match['score'].')' : '';

        return match ($match['outcome']) {
            'win' => ucfirst($context).' ante '.$match['rival'].': victoria'.$score.'.',
            'draw' => ucfirst($context).' ante '.$match['rival'].': empate'.$score.'.',
            'loss' => ucfirst($context).' ante '.$match['rival'].': derrota'.$score.'.',
            'no_points' => ucfirst($context).' ante '.$match['rival'].': se jugó sin puntos en juego.',
            default => ucfirst($context).' ante '.$match['rival'].'.',
        };
    }

    private function outcomeLabel(string $outcome): string
    {
        return match ($outcome) {
            'win' => 'Victoria',
            'draw' => 'Empate',
            'loss' => 'Derrota',
            'no_points' => 'Sin puntos',
            default => 'Por jugar',
        };
    }

    private function honourLabel(bool $isChampion, bool $finalized, int $rank): ?string
    {
        if ($isChampion) {
            return 'Campeón';
        }

        if (! $finalized || $rank <= 0) {
            return null;
        }

        return match ($rank) {
            2 => 'Subcampeón',
            3 => 'Tercer puesto',
            default => null,
        };
    }

    private function ordinal(int $rank): string
    {
        return $rank.'.º';
    }

    private function initials(string $name): string
    {
        $initials = Str::of($name)
            ->squish()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'EQ';
    }
}
