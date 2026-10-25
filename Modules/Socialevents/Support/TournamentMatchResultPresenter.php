<?php

namespace Modules\Socialevents\Support;

use Modules\Socialevents\Entities\EventEditionMatch;

/**
 * Lee el resultado guardado de un partido y lo prepara para la landing.
 *
 * Convención real del sistema (ver migración add_extra_time_to_event_edition_matches):
 * - `score_regular_h/a` es el marcador al terminar el tiempo reglamentario.
 * - `score_h/a` es el marcador final, acumulado con la prórroga si se jugó.
 * - `extra_time_played` indica si existió prórroga.
 * - `penalties` es la tanda; sus goles no se suman al marcador del partido.
 *
 * El presentador no altera datos ni inventa etapas: cuando falta un marcador,
 * simplemente no lo expone.
 */
class TournamentMatchResultPresenter
{
    /** Estados en los que la landing muestra el resultado del partido. */
    public const PLAYED_STATUSES = ['finished', 'closed', 'no_points'];

    /** Resultados válidos de un remate en la tanda. */
    private const PENALTY_RESULTS = ['goal', 'miss', 'saved'];

    /**
     * @return array{
     *     final_h: int,
     *     final_a: int,
     *     extra_time_played: bool,
     *     details: array<int, array{key: string, label: string, value: string}>,
     *     final_label: string,
     *     shootout: array{h: int, a: int, winner_side: string|null, winner_name: string|null}|null,
     *     winner_side: string|null,
     *     has_details: bool
     * }
     */
    public static function forMatch(EventEditionMatch $match): array
    {
        $finalH = (int) ($match->score_h ?? 0);
        $finalA = (int) ($match->score_a ?? 0);
        $extraPlayed = (bool) $match->extra_time_played;
        $shootout = self::shootout($match);

        $details = [];
        $finalLabel = $finalH . ' - ' . $finalA;

        if ($shootout !== null) {
            // Los penales definen al ganador, pero no se suman a los goles.
            $finalLabel .= ' (' . $shootout['h'] . ' - ' . $shootout['a'] . ' PEN.)';
        }

        // Un partido normal conserva su tarjeta sin zona de detalles. La zona
        // solo existe cuando el partido tuvo prórroga o tanda de penales.
        $hasDetails = $extraPlayed || $shootout !== null;

        if ($hasDetails) {
            $regulation = self::regulation($match, $finalH, $finalA, $extraPlayed);

            if ($regulation !== null) {
                $details[] = [
                    'key' => 'regulation',
                    'label' => 'Tiempo reglamentario · 90′',
                    'value' => $regulation['h'] . ' - ' . $regulation['a'],
                ];
            }

            // El marcador final ya incluye la prórroga: se etiqueta como acumulado
            // para no sumar dos veces los goles del alargue.
            if ($extraPlayed) {
                $details[] = [
                    'key' => 'extra',
                    'label' => 'Tiempo extra · acumulado',
                    'value' => $finalH . ' - ' . $finalA,
                ];
            }

            if ($shootout !== null) {
                $details[] = [
                    'key' => 'penalties',
                    'label' => 'Tanda de penales',
                    'value' => $shootout['h'] . ' - ' . $shootout['a'],
                ];
            }

            $details[] = [
                'key' => 'final',
                'label' => 'Resultado final',
                'value' => $finalLabel,
            ];
        }

        $winnerSide = $shootout['winner_side'] ?? null;

        return [
            'final_h' => $finalH,
            'final_a' => $finalA,
            'extra_time_played' => $extraPlayed,
            'details' => $details,
            'final_label' => $finalLabel,
            'shootout' => $shootout,
            'winner_side' => $winnerSide,
            'has_details' => $hasDetails,
        ];
    }

    /**
     * Marcador al final del tiempo reglamentario.
     *
     * Sin prórroga, el marcador final ES el reglamentario. Con prórroga solo se
     * usa `score_regular_*` cuando fue registrado; si falta, se omite la etapa.
     *
     * @return array{h: int, a: int}|null
     */
    private static function regulation(
        EventEditionMatch $match,
        int $finalH,
        int $finalA,
        bool $extraPlayed
    ): ?array {
        if (! $extraPlayed) {
            return ['h' => $finalH, 'a' => $finalA];
        }

        if ($match->score_regular_h === null || $match->score_regular_a === null) {
            return null;
        }

        return [
            'h' => (int) $match->score_regular_h,
            'a' => (int) $match->score_regular_a,
        ];
    }

    /**
     * Tanda de penales traducida al orden local-visitante de la tarjeta.
     *
     * Se exige que cada remate tenga equipo y resultado conocidos. Si el
     * registro está incompleto se prefiere no mostrar una tanda inventada.
     *
     * @return array{h: int, a: int, winner_side: string|null, winner_name: string|null}|null
     */
    private static function shootout(EventEditionMatch $match): ?array
    {
        $penalties = $match->penalties;

        if (! is_array($penalties) || $penalties === []) {
            return null;
        }

        $home = 0;
        $away = 0;

        foreach ($penalties as $penalty) {
            $team = $penalty['team'] ?? null;
            $result = $penalty['result'] ?? null;

            if (! in_array($team, ['local', 'visitor'], true) || ! in_array($result, self::PENALTY_RESULTS, true)) {
                return null;
            }

            if ($result !== 'goal') {
                continue;
            }

            if ($team === 'local') {
                $home++;
            } else {
                $away++;
            }
        }

        $winnerSide = match (true) {
            $home > $away => 'home',
            $away > $home => 'away',
            default => null,
        };

        return [
            'h' => $home,
            'a' => $away,
            'winner_side' => $winnerSide,
            'winner_name' => match ($winnerSide) {
                'home' => $match->equipolocal?->name ?? $match->placeholder_h,
                'away' => $match->equipovisitante?->name ?? $match->placeholder_a,
                default => null,
            },
        ];
    }
}
