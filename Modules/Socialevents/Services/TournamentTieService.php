<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Collection;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Support\TournamentPhaseLabels;

/**
 * Reglas de los cruces de ida y vuelta y de la definición del ganador.
 *
 * Un cruce son dos partidos de la misma fase: la ida (`leg = 1`) y la vuelta
 * (`leg = 2`, con `tie_id` apuntando a la ida). El ganador sale del marcador
 * global o, si el global empata, de los penales de la vuelta.
 */
class TournamentTieService
{
    public const FORMAT_SINGLE = 'single';

    public const FORMAT_HOME_AND_AWAY = 'home_and_away';

    public const DECIDER_NORMAL = 'normal';

    public const DECIDER_SUDDEN_DEATH = 'sudden_death';

    /**
     * Formatos de cruce que ofrece el formulario.
     *
     * @return array<int, array{value: string, label: string, description: string}>
     */
    public static function tieFormats(): array
    {
        return [
            [
                'value' => self::FORMAT_SINGLE,
                'label' => 'Partido único (normal)',
                'description' => 'Un solo partido, definido con prórroga y penales si termina empatado.',
            ],
            [
                'value' => self::FORMAT_SINGLE . '_' . self::DECIDER_SUDDEN_DEATH,
                'label' => 'Partido único (muerte súbita)',
                'description' => 'Un solo partido a gol de oro: el primero que anota gana. No hay penales.',
            ],
            [
                'value' => self::FORMAT_HOME_AND_AWAY,
                'label' => 'Ida y vuelta',
                'description' => 'Dos partidos con equipos invertidos. Gana el marcador global; si empata, penales en la vuelta.',
            ],
        ];
    }

    /**
     * Traduce el valor del selector a los campos que se guardan.
     *
     * @return array{tie_format: string, decider: string}
     */
    public static function resolveFormat(?string $value): array
    {
        if ($value === self::FORMAT_HOME_AND_AWAY) {
            return ['tie_format' => self::FORMAT_HOME_AND_AWAY, 'decider' => self::DECIDER_NORMAL];
        }

        if ($value === self::FORMAT_SINGLE . '_' . self::DECIDER_SUDDEN_DEATH) {
            return ['tie_format' => self::FORMAT_SINGLE, 'decider' => self::DECIDER_SUDDEN_DEATH];
        }

        return ['tie_format' => self::FORMAT_SINGLE, 'decider' => self::DECIDER_NORMAL];
    }

    /**
     * Valor del selector a partir de los campos guardados.
     */
    public static function formatValue(EventEditionMatch $match): string
    {
        if ($match->tie_format === self::FORMAT_HOME_AND_AWAY) {
            return self::FORMAT_HOME_AND_AWAY;
        }

        return $match->decider === self::DECIDER_SUDDEN_DEATH
            ? self::FORMAT_SINGLE . '_' . self::DECIDER_SUDDEN_DEATH
            : self::FORMAT_SINGLE;
    }

    /**
     * Jornada de la vuelta cuando el organizador no la indica: la siguiente a la
     * ida, sin pasar del máximo que admite el campo de jornada (99).
     */
    public static function defaultReturnRound(?int $idaRound): int
    {
        return min(99, max(1, (int) $idaRound) + 1);
    }

    /**
     * Regla de negocio: ida y vuelta y muerte súbita solo existen en eliminación.
     */
    public static function formatAllowedForPhase(string $formatValue, ?string $phase): bool
    {
        if ($formatValue === self::FORMAT_SINGLE) {
            return true;
        }

        return TournamentPhaseLabels::isKnockout($phase);
    }

    /**
     * Todos los partidos del cruce al que pertenece el partido indicado,
     * incluida la ida. Un partido suelto devuelve solo ese partido.
     *
     * @return Collection<int, EventEditionMatch>
     */
    public function legsOf(EventEditionMatch $match): Collection
    {
        $root = $this->rootOf($match);

        $legs = collect([$root])->merge(
            EventEditionMatch::where('tie_id', $root->id)->orderBy('leg')->get()
        );

        return $legs->filter()->values();
    }

    /**
     * Partido de ida del cruce (el propio partido si no es una vuelta).
     */
    public function rootOf(EventEditionMatch $match): EventEditionMatch
    {
        if (! $match->tie_id) {
            return $match;
        }

        return EventEditionMatch::find($match->tie_id) ?? $match;
    }

    /**
     * Resumen del cruce desde la perspectiva de los equipos del partido recibido:
     * `aggregate_h` son los goles del local de ESTE partido en las dos piernas.
     *
     * @return array<string, mixed>
     */
    public function summary(EventEditionMatch $match): array
    {
        $legs = $this->legsOf($match);
        $isTie = $legs->count() > 1;

        $payload = [
            'leg' => $match->leg !== null ? (int) $match->leg : null,
            'tie_id' => $match->tie_id !== null ? (int) $match->tie_id : null,
            'tie_format' => $match->tie_format ?? self::FORMAT_SINGLE,
            'decider' => $match->decider ?? self::DECIDER_NORMAL,
            'is_tie' => $isTie,
            'legs_count' => $legs->count(),
            'aggregate_h' => null,
            'aggregate_a' => null,
            'decided_by' => null,
            'winner_team_id' => null,
            'complete' => false,
        ];

        if (! $isTie) {
            return $payload;
        }

        $homeId = $match->team_h_id !== null ? (int) $match->team_h_id : null;
        $awayId = $match->team_a_id !== null ? (int) $match->team_a_id : null;

        if (! $homeId || ! $awayId) {
            return $payload;
        }

        $aggregateH = 0;
        $aggregateA = 0;
        $played = 0;

        foreach ($legs as $leg) {
            if (! in_array($leg->status, ['finished', 'closed', 'walk_over', 'no_points'], true)) {
                continue;
            }

            $played++;

            // Los equipos se invierten entre piernas: se suman los goles de cada
            // equipo sin importar de qué lado jugó en cada partido.
            if ((int) $leg->team_h_id === $homeId) {
                $aggregateH += (int) $leg->score_h;
                $aggregateA += (int) $leg->score_a;
            } else {
                $aggregateH += (int) $leg->score_a;
                $aggregateA += (int) $leg->score_h;
            }
        }

        $payload['aggregate_h'] = $aggregateH;
        $payload['aggregate_a'] = $aggregateA;
        $payload['complete'] = $played === $legs->count();

        if ($aggregateH > $aggregateA) {
            $payload['decided_by'] = 'aggregate';
            $payload['winner_team_id'] = $homeId;

            return $payload;
        }

        if ($aggregateA > $aggregateH) {
            $payload['decided_by'] = 'aggregate';
            $payload['winner_team_id'] = $awayId;

            return $payload;
        }

        // Global empatado: manda la tanda de penales de la vuelta.
        $shootout = $this->penaltyShootout($legs);

        if ($shootout !== null) {
            $payload['decided_by'] = 'penalties';
            $payload['winner_team_id'] = $shootout['winner_team_id'];

            return $payload;
        }

        $payload['decided_by'] = $payload['complete'] ? 'pending' : 'pending';

        return $payload;
    }

    /**
     * Resultado de la tanda de penales del cruce, ya traducido a ids de equipo.
     *
     * @param  Collection<int, EventEditionMatch>  $legs
     * @return array{winner_team_id: int|null, local: int, visitor: int}|null
     */
    private function penaltyShootout(Collection $legs): ?array
    {
        foreach ($legs->reverse() as $leg) {
            $penalties = $leg->penalties;

            if (! is_array($penalties) || $penalties === []) {
                continue;
            }

            $local = 0;
            $visitor = 0;

            foreach ($penalties as $penalty) {
                $isGoal = ($penalty['result'] ?? null) === 'goal';

                if (! $isGoal) {
                    continue;
                }

                if (($penalty['team'] ?? null) === 'local') {
                    $local++;
                } elseif (($penalty['team'] ?? null) === 'visitor') {
                    $visitor++;
                }
            }

            if ($local === $visitor) {
                continue;
            }

            return [
                'winner_team_id' => $local > $visitor
                    ? ($leg->team_h_id !== null ? (int) $leg->team_h_id : null)
                    : ($leg->team_a_id !== null ? (int) $leg->team_a_id : null),
                'local' => $local,
                'visitor' => $visitor,
            ];
        }

        return null;
    }
}
