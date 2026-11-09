<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Support\TournamentMatchResultPresenter;
use Modules\Socialevents\Support\TournamentMedia;
use Modules\Socialevents\Support\TournamentPhaseLabels;

/**
 * Campeón de la edición a partir de la final ya terminada.
 *
 * No usa la tabla de posiciones: el campeón es el equipo que ganó la final,
 * sea a partido único o a ida y vuelta (con penales si el global empata).
 * Mientras alguna pierna de la final no esté terminada no hay campeón, así
 * que la sección pública permanece oculta.
 */
class TournamentChampionService
{
    /** Estados que cuentan como partido terminado en el resto del módulo. */
    private const PLAYED_STATUSES = ['finished', 'closed', 'walk_over', 'no_points'];

    public function __construct(private TournamentTieService $tieService) {}

    /**
     * @return array<string, mixed>|null
     */
    public function forEdition(EventEdition $edition): ?array
    {
        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $edition->id)
            ->get()
            ->filter(fn (EventEditionMatch $match) => TournamentPhaseLabels::canonical($match->phase) === 'final')
            ->values();

        if ($matches->isEmpty()) {
            return null;
        }

        // Más reciente primero: si una edición conserva una final antigua
        // reprogramada, se corona la última que sí se terminó.
        foreach ($this->tieGroups($matches) as $legs) {
            $champion = $this->resolveChampion($legs, $edition);

            if ($champion !== null) {
                return $champion;
            }
        }

        return null;
    }

    /**
     * Agrupa las piernas de cada final. Un partido suelto es su propio cruce.
     *
     * @param  Collection<int, EventEditionMatch>  $matches
     * @return array<int, Collection<int, EventEditionMatch>>
     */
    private function tieGroups(Collection $matches): array
    {
        $byId = $matches->keyBy('id');
        $groups = [];

        foreach ($matches as $match) {
            $rootId = $match->tie_id && $byId->has((int) $match->tie_id)
                ? (int) $match->tie_id
                : (int) $match->id;

            $groups[$rootId][] = $match;
        }

        // La final más reciente se evalúa primero.
        uasort($groups, function (array $a, array $b): int {
            return $this->latestDate($b) <=> $this->latestDate($a);
        });

        return array_map(
            fn (array $legs) => collect($legs)->sortBy('leg')->values(),
            $groups
        );
    }

    /**
     * @param  array<int, EventEditionMatch>  $legs
     */
    private function latestDate(array $legs): string
    {
        return collect($legs)
            ->map(fn (EventEditionMatch $match) => $match->match_date?->format('Y-m-d H:i:s') ?? '')
            ->max() ?? '';
    }

    /**
     * @param  Collection<int, EventEditionMatch>  $legs
     * @return array<string, mixed>|null
     */
    private function resolveChampion(Collection $legs, EventEdition $edition): ?array
    {
        if ($legs->isEmpty() || ! $this->allPlayed($legs)) {
            return null;
        }

        if ($legs->count() > 1) {
            return $this->championFromTie($legs, $edition);
        }

        return $this->championFromSingle($legs->first(), $edition);
    }

    /**
     * @param  Collection<int, EventEditionMatch>  $legs
     */
    private function allPlayed(Collection $legs): bool
    {
        return $legs->every(
            fn (EventEditionMatch $match) => in_array((string) $match->status, self::PLAYED_STATUSES, true)
        );
    }

    /**
     * Final a ida y vuelta: manda el global y, si empata, los penales.
     *
     * @param  Collection<int, EventEditionMatch>  $legs
     * @return array<string, mixed>|null
     */
    private function championFromTie(Collection $legs, EventEdition $edition): ?array
    {
        $root = $legs->first(fn (EventEditionMatch $match) => ! $match->tie_id) ?? $legs->first();
        $summary = $this->tieService->summary($root);

        if (empty($summary['is_tie']) || empty($summary['complete']) || empty($summary['winner_team_id'])) {
            return null;
        }

        $teamId = (int) $summary['winner_team_id'];
        $winnerLeg = $legs->first(
            fn (EventEditionMatch $match) => (int) $match->team_h_id === $teamId
                || (int) $match->team_a_id === $teamId
        );

        if (! $winnerLeg) {
            return null;
        }

        $team = (int) $winnerLeg->team_h_id === $teamId
            ? $winnerLeg->equipolocal
            : $winnerLeg->equipovisitante;
        $rival = (int) $winnerLeg->team_h_id === $teamId
            ? $winnerLeg->equipovisitante
            : $winnerLeg->equipolocal;

        $teamPayload = $this->teamPayload($team, $edition);

        if ($teamPayload === null) {
            return null;
        }

        $championIsRootHome = (int) $root->team_h_id === $teamId;
        $championGoals = (int) ($championIsRootHome ? $summary['aggregate_h'] : $summary['aggregate_a']);
        $rivalGoals = (int) ($championIsRootHome ? $summary['aggregate_a'] : $summary['aggregate_h']);

        $note = 'Global · ida y vuelta';

        if (($summary['decided_by'] ?? null) === 'penalties') {
            $note .= ' · definido por penales';
        }

        return array_merge($teamPayload, [
            'rival_name' => $rival?->name,
            'final_label' => $championGoals.' - '.$rivalGoals,
            'final_note' => $note,
        ]);
    }

    /**
     * Final a partido único: gana el marcador final o la tanda de penales.
     *
     * @return array<string, mixed>|null
     */
    private function championFromSingle(EventEditionMatch $match, EventEdition $edition): ?array
    {
        // Si está configurada a ida y vuelta pero solo existe una pierna,
        // la final todavía no se ha definido.
        if ($match->tie_format === TournamentTieService::FORMAT_HOME_AND_AWAY) {
            return null;
        }

        if ($match->team_h_id === null || $match->team_a_id === null) {
            return null;
        }

        $result = TournamentMatchResultPresenter::forMatch($match);
        $winnerSide = $result['shootout']['winner_side'] ?? null;

        if ($winnerSide === null) {
            if ($result['final_h'] === $result['final_a']) {
                return null;
            }

            $winnerSide = $result['final_h'] > $result['final_a'] ? 'home' : 'away';
        }

        $team = $winnerSide === 'home' ? $match->equipolocal : $match->equipovisitante;
        $rival = $winnerSide === 'home' ? $match->equipovisitante : $match->equipolocal;

        $teamPayload = $this->teamPayload($team, $edition);

        if ($teamPayload === null) {
            return null;
        }

        $championGoals = $winnerSide === 'home' ? $result['final_h'] : $result['final_a'];
        $rivalGoals = $winnerSide === 'home' ? $result['final_a'] : $result['final_h'];

        $note = 'Final';

        if ($result['extra_time_played']) {
            $note .= ' · prórroga';
        }

        if ($result['shootout'] !== null) {
            $championPenalties = $winnerSide === 'home'
                ? $result['shootout']['h']
                : $result['shootout']['a'];
            $rivalPenalties = $winnerSide === 'home'
                ? $result['shootout']['a']
                : $result['shootout']['h'];

            $note .= ' · penales '.$championPenalties.' - '.$rivalPenalties;
        }

        return array_merge($teamPayload, [
            'rival_name' => $rival?->name,
            'final_label' => $championGoals.' - '.$rivalGoals,
            'final_note' => $note,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function teamPayload(?EventTeam $team, EventEdition $edition): ?array
    {
        if (! $team) {
            return null;
        }

        return [
            'team_id' => (int) $team->id,
            'name' => (string) $team->name,
            'short_name' => $team->short_name,
            'logo_url' => TournamentMedia::url($team->logo_path),
            'profile_url' => route('socialevents_torneos_team_profile', [
                'slug' => $edition->landingSlug(),
                'teamId' => $team->id,
            ]),
        ];
    }
}
