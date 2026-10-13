<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchGoal;
use Modules\Socialevents\Entities\EventEditionMatchSave;
use Modules\Socialevents\Entities\EventEditionMatchSubstitution;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;

/**
 * Desempeño individual del partido: tiempos configurados, cambios y minutos.
 *
 * Es el único lugar donde se resuelve la duración de un partido y donde se
 * calculan los minutos jugados. Web y móvil comparten estos métodos para que
 * no existan dos criterios distintos.
 *
 * Nada de minutos está fijo en el código: el partido pisa a la edición y la
 * edición tiene sus propios valores; los DEFAULTS son solo un respaldo.
 */
class MatchPerformanceService
{
    public const DEFAULT_HALF_MINUTES = 20;
    public const DEFAULT_HALVES = 2;
    public const DEFAULT_EXTRA_HALF_MINUTES = 10;
    public const DEFAULT_EXTRA_HALVES = 2;

    public const PERIODS = ['first', 'second', 'extra_first', 'extra_second'];
    public const EXTRA_PERIODS = ['extra_first', 'extra_second'];
    public const REASONS = ['tactical', 'injury', 'red_card', 'other'];
    public const REGULATION_PERIODS = ['first', 'second'];

    /** Tipos de gol que se pueden registrar en el detalle. */
    public const GOAL_TYPES = ['normal', 'penalty', 'header', 'free_kick', 'volley', 'other'];

    public const GOAL_TYPE_LABELS = [
        'normal' => 'Jugada',
        'penalty' => 'Penal',
        'header' => 'Cabeza',
        'free_kick' => 'Tiro libre',
        'volley' => 'Volea',
        'other' => 'Otro',
    ];

    /** Cómo llegó el remate que terminó en atajada. */
    public const SAVE_TYPES = ['shot', 'one_on_one', 'header', 'olympic', 'other'];

    public const SAVE_TYPE_LABELS = [
        'shot' => 'Tiro / remate',
        'one_on_one' => 'Uno contra uno',
        'header' => 'Cabeza',
        'olympic' => 'Tiro olímpico',
        'other' => 'Otro',
    ];

    /**
     * Tiempo especial de la tanda de penales: no es tiempo de juego (no tiene
     * minuto) pero sí hay atajadas que se quieren registrar.
     */
    public const PENALTY_SHOOTOUT_PERIOD = 'penalty_shootout';

    /** Tiempos que acepta el detalle de atajadas (incluye la tanda). */
    public const SAVE_PERIODS = ['first', 'second', 'extra_first', 'extra_second', 'penalty_shootout'];

    /**
     * Tiempos efectivos del partido: override del partido → edición → default.
     *
     * @return array{half_minutes:int,halves:int,extra_half_minutes:int,extra_halves:int,regular_total:int,extra_total:int,extra_time_played:bool,duration:int}
     */
    public function resolvedTiming(EventEditionMatch $match): array
    {
        $edition = $match->edicion ?: EventEdition::find($match->edition_id);

        $halfMinutes = (int) ($match->half_minutes_override
            ?: ($edition->regular_half_minutes ?? null)
            ?: self::DEFAULT_HALF_MINUTES);

        $halves = (int) ($match->halves_override
            ?: ($edition->regular_halves ?? null)
            ?: self::DEFAULT_HALVES);

        $extraHalf = (int) ($match->extra_half_minutes_override
            ?: ($edition->extra_half_minutes ?? null)
            ?: self::DEFAULT_EXTRA_HALF_MINUTES);

        $extraHalves = (int) (($edition->extra_halves ?? null) ?: self::DEFAULT_EXTRA_HALVES);

        $regularTotal = $halfMinutes * $halves;
        $extraTotal = $extraHalf * $extraHalves;
        $extraTimePlayed = (bool) $match->extra_time_played;

        return [
            'half_minutes' => $halfMinutes,
            'halves' => $halves,
            'extra_half_minutes' => $extraHalf,
            'extra_halves' => $extraHalves,
            'regular_total' => $regularTotal,
            'extra_total' => $extraTotal,
            'extra_time_played' => $extraTimePlayed,
            'duration' => $regularTotal + ($extraTimePlayed ? $extraTotal : 0),
        ];
    }

    /**
     * Minutos que dura un tiempo concreto (reglamentario o suplementario).
     */
    public function periodLength(array $timing, ?string $period): int
    {
        if (in_array($period, self::EXTRA_PERIODS, true)) {
            return $timing['extra_half_minutes'];
        }

        return $timing['half_minutes'];
    }

    /**
     * Minuto absoluto del partido a partir del tiempo y el minuto dentro de él.
     */
    public function absoluteMinute(array $timing, ?string $period, ?int $minuteInPeriod): int
    {
        $minute = max(0, (int) ($minuteInPeriod ?? 0));

        switch ($period) {
            case 'second':
                return $timing['half_minutes'] + $minute;
            case 'extra_first':
                return $timing['regular_total'] + $minute;
            case 'extra_second':
                return $timing['regular_total'] + $timing['extra_half_minutes'] + $minute;
            case 'first':
            default:
                return $minute;
        }
    }

    /**
     * Tiempos que se pueden elegir en el acta según lo que se jugó.
     *
     * @return string[]
     */
    public function availablePeriods(array $timing): array
    {
        $periods = $timing['halves'] > 1 ? ['first', 'second'] : ['first'];

        if ($timing['extra_time_played']) {
            $periods = array_merge($periods, self::EXTRA_PERIODS);
        }

        return $periods;
    }

    /**
     * Roles declarados en el acta para un equipo: person_id => starter|substitute.
     */
    public function rolesFrom(?array $players): array
    {
        $roles = [];

        foreach ($players ?: [] as $player) {
            $personId = (int) ($player['person_id'] ?? 0);
            $role = $player['match_role'] ?? null;

            if ($personId && in_array($role, ['starter', 'substitute'], true)) {
                $roles[$personId] = $role;
            }
        }

        return $roles;
    }

    /**
     * Valida y normaliza los cambios de un equipo antes de guardarlos.
     *
     * Puesto que las participaciones se escriben en la misma operación, los
     * roles se leen del acta enviada (no de la base de datos).
     *
     * @param  array<int,array<string,mixed>>  $substitutions
     * @param  array{team_id:int,edition_id:int,roster:int[],roles:array<int,string>,timing:array,field:string}  $context
     * @return array<int,array<string,mixed>>
     */
    public function prepareSubstitutions(array $substitutions, array $context): array
    {
        if (empty($substitutions)) {
            return [];
        }

        $timing = $context['timing'];
        $roster = $context['roster'];
        $roles = $context['roles'];
        $field = $context['field'] ?? 'substitutions';

        $alreadyOut = [];
        $alreadyIn = [];
        $prepared = [];

        foreach (array_values($substitutions) as $index => $sub) {
            $key = $field . '.' . $index;
            $outId = (int) ($sub['player_out_id'] ?? 0);
            $inId = (int) ($sub['player_in_id'] ?? 0);
            $period = $sub['period'] ?? 'first';
            $minuteInPeriod = (int) ($sub['minute_in_period'] ?? 0);

            if ($outId === 0 || $inId === 0) {
                throw ValidationException::withMessages([
                    $key => 'Cada cambio necesita el jugador que sale y el que entra.',
                ]);
            }

            if ($outId === $inId) {
                throw ValidationException::withMessages([
                    $key => 'El jugador que sale y el que entra no pueden ser el mismo.',
                ]);
            }

            if (! in_array($outId, $roster, true) || ! in_array($inId, $roster, true)) {
                throw ValidationException::withMessages([
                    $key => 'Los jugadores del cambio deben pertenecer al equipo.',
                ]);
            }

            // Si alguno ya no está en el acta (se quitó de la alineación), el
            // cambio se descarta solo: mismo criterio que sus goles o su MVP.
            if (! isset($roles[$outId]) || ! isset($roles[$inId])) {
                continue;
            }

            if (in_array($outId, $alreadyOut, true)) {
                throw ValidationException::withMessages([
                    $key => 'Ese jugador ya salió antes; no puede salir dos veces.',
                ]);
            }

            if (in_array($inId, $alreadyOut, true)) {
                throw ValidationException::withMessages([
                    $key => 'Un jugador que ya salió no puede volver a entrar.',
                ]);
            }

            if (in_array($inId, $alreadyIn, true)) {
                throw ValidationException::withMessages([
                    $key => 'Ese jugador ya entró antes.',
                ]);
            }

            if (! in_array($period, self::PERIODS, true)) {
                throw ValidationException::withMessages([
                    $key => 'Tiempo de juego no válido.',
                ]);
            }

            if (in_array($period, self::EXTRA_PERIODS, true) && ! $timing['extra_time_played']) {
                throw ValidationException::withMessages([
                    $key => 'No se pueden registrar cambios de la prórroga: este partido no la jugó.',
                ]);
            }

            $length = $this->periodLength($timing, $period);

            if ($minuteInPeriod < 0 || $minuteInPeriod > $length) {
                throw ValidationException::withMessages([
                    $key => "El minuto debe estar entre 0 y {$length} para el tiempo elegido.",
                ]);
            }

            $alreadyOut[] = $outId;
            $alreadyIn[] = $inId;

            $prepared[] = [
                'team_id' => (int) $context['team_id'],
                'player_out_id' => $outId,
                'player_in_id' => $inId,
                'minute' => $this->absoluteMinute($timing, $period, $minuteInPeriod),
                'minute_in_period' => $minuteInPeriod,
                'period' => $period,
                'reason' => in_array($sub['reason'] ?? null, self::REASONS, true) ? $sub['reason'] : null,
            ];
        }

        return $prepared;
    }

    /**
     * Reemplaza los cambios del partido por los enviados (idempotente).
     *
     * @param  array<int,array<string,mixed>>  $subsH
     * @param  array<int,array<string,mixed>>  $subsA
     */
    public function syncSubstitutions(EventEditionMatch $match, array $subsH, array $subsA): void
    {
        EventEditionMatchSubstitution::where('match_id', $match->id)->delete();

        $userId = Auth::id();

        foreach (array_merge($subsH, $subsA) as $sub) {
            EventEditionMatchSubstitution::create(array_merge($sub, [
                'match_id' => $match->id,
                'created_by' => $userId,
            ]));
        }
    }

    /**
     * Minutos jugados por jugador a partir de la alineación y los cambios.
     *
     * @param  array<int,string>  $roles  person_id => starter|substitute
     * @param  array<int,array<string,mixed>>  $substitutions  filas ya preparadas
     * @return array<int,int>  person_id => minutos
     */
    public function computeMinutes(array $timing, array $roles, array $substitutions): array
    {
        $duration = $timing['duration'];

        $enteredAt = [];
        $leftAt = [];

        foreach ($substitutions as $sub) {
            $leftAt[(int) $sub['player_out_id']] = (int) $sub['minute'];
            $enteredAt[(int) $sub['player_in_id']] = (int) $sub['minute'];
        }

        $minutes = [];

        foreach ($roles as $personId => $role) {
            $personId = (int) $personId;

            if (array_key_exists($personId, $enteredAt)) {
                $start = $enteredAt[$personId];
            } elseif ($role === 'starter') {
                $start = 0;
            } else {
                // Suplente que nunca entró: no se le inventan minutos.
                $minutes[$personId] = 0;
                continue;
            }

            // Un cambio de la prórroga no puede alargar más allá de lo jugado.
            if ($start > $duration) {
                $start = $duration;
            }

            $end = min($leftAt[$personId] ?? $duration, $duration);

            $minutes[$personId] = max(0, $end - $start);
        }

        return $minutes;
    }

    /**
     * Plantilla (person_id) de un equipo dentro de la edición.
     *
     * @return int[]
     */
    public function roster(int $editionId, int $teamId): array
    {
        return EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->pluck('person_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Cambios guardados de un partido, listos para el frontend.
     *
     * @return array<int,array<string,mixed>>
     */
    public function substitutionsForMatch(int $matchId): array
    {
        return EventEditionMatchSubstitution::where('match_id', $matchId)
            ->orderBy('minute')
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'team_id' => $sub->team_id,
                'player_out_id' => $sub->player_out_id,
                'player_in_id' => $sub->player_in_id,
                'minute' => $sub->minute,
                'minute_in_period' => $sub->minute_in_period,
                'period' => $sub->period,
                'reason' => $sub->reason,
            ])
            ->all();
    }

    /**
     * Valida y normaliza los goles de un equipo antes de guardarlos.
     *
     * El tiempo y el minuto son opcionales: un acta vieja no los tiene y no se
     * inventan. Cuando se escriben se aplican las mismas reglas que a los
     * cambios (tiempo jugado válido y minuto dentro de ese tiempo), para que el
     * gol y el cambio no usen dos criterios distintos.
     *
     * @param  array<int,array<string,mixed>>  $goals
     * @param  array{team_id:int,edition_id:int,roster:int[],roles:array<int,string>,timing:array,field:string}  $context
     * @return array<int,array<string,mixed>>
     */
    public function prepareGoals(array $goals, array $context): array
    {
        if (empty($goals)) {
            return [];
        }

        $timing = $context['timing'];
        $roster = $context['roster'];
        $roles = $context['roles'];
        $field = $context['field'] ?? 'goals';

        $prepared = [];

        foreach (array_values($goals) as $index => $goal) {
            $key = $field . '.' . $index;
            $playerId = (int) ($goal['player_id'] ?? 0);

            if ($playerId === 0) {
                throw ValidationException::withMessages([
                    $key => 'Cada gol necesita su jugador.',
                ]);
            }

            if (! in_array($playerId, $roster, true)) {
                throw ValidationException::withMessages([
                    $key => 'El jugador del gol debe pertenecer al equipo.',
                ]);
            }

            // Si el jugador ya no está en el acta (se quitó de la alineación),
            // el gol se descarta solo: mismo criterio que sus cambios.
            if (! isset($roles[$playerId])) {
                continue;
            }

            $period = $goal['period'] ?? null;
            $period = ($period === '' || $period === null) ? null : $period;

            $minuteInPeriod = $goal['minute_in_period'] ?? null;
            $minuteInPeriod = ($minuteInPeriod === '' || $minuteInPeriod === null)
                ? null
                : (int) $minuteInPeriod;

            if ($period === null) {
                // Sin tiempo no hay minuto que ubicar: queda "sin registrar".
                $minuteInPeriod = null;
            } else {
                if (! in_array($period, self::PERIODS, true)) {
                    throw ValidationException::withMessages([
                        $key => 'Tiempo de juego no válido.',
                    ]);
                }

                if (in_array($period, self::EXTRA_PERIODS, true) && ! $timing['extra_time_played']) {
                    throw ValidationException::withMessages([
                        $key => 'No se pueden registrar goles de la prórroga: este partido no la jugó.',
                    ]);
                }

                if ($minuteInPeriod !== null) {
                    $length = $this->periodLength($timing, $period);

                    if ($minuteInPeriod < 0 || $minuteInPeriod > $length) {
                        throw ValidationException::withMessages([
                            $key => "El minuto debe estar entre 0 y {$length} para el tiempo elegido.",
                        ]);
                    }
                }
            }

            // Tipo de gol: cómo se hizo (jugada, penal, cabeza, tiro libre...).
            $goalType = $goal['goal_type'] ?? 'normal';
            $goalType = ($goalType === '' || $goalType === null) ? 'normal' : $goalType;

            if (! in_array($goalType, self::GOAL_TYPES, true)) {
                throw ValidationException::withMessages([
                    $key => 'Tipo de gol no válido.',
                ]);
            }

            // Asistente: opcional. Si ya no está en el acta se descarta solo (el
            // gol no pierde a su autor por un dato accesorio); si es un jugador
            // de otro equipo o el mismo autor, es un error del acta.
            $assistId = (int) ($goal['assist_player_id'] ?? 0);

            if ($assistId !== 0) {
                if ($assistId === $playerId) {
                    throw ValidationException::withMessages([
                        $key => 'El asistente no puede ser el mismo jugador que anota.',
                    ]);
                }

                if (! in_array($assistId, $roster, true)) {
                    throw ValidationException::withMessages([
                        $key => 'El asistente del gol debe pertenecer al equipo.',
                    ]);
                }

                if (! isset($roles[$assistId])) {
                    $assistId = 0;
                }
            }

            $prepared[] = [
                'team_id' => (int) $context['team_id'],
                'player_id' => $playerId,
                'minute' => ($period !== null && $minuteInPeriod !== null)
                    ? $this->absoluteMinute($timing, $period, $minuteInPeriod)
                    : null,
                'minute_in_period' => $minuteInPeriod,
                'period' => $period,
                'goal_type' => $goalType,
                'is_own_goal' => filter_var($goal['is_own_goal'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'assist_player_id' => $assistId !== 0 ? $assistId : null,
            ];
        }

        return $prepared;
    }

    /**
     * Reemplaza los goles del partido por los enviados (idempotente).
     *
     * @param  array<int,array<string,mixed>>  $goalsH  goles ya preparados
     * @param  array<int,array<string,mixed>>  $goalsA
     */
    public function syncGoals(EventEditionMatch $match, array $goalsH, array $goalsA): void
    {
        EventEditionMatchGoal::where('match_id', $match->id)->delete();

        $userId = Auth::id();

        foreach (array_merge($goalsH, $goalsA) as $goal) {
            EventEditionMatchGoal::create(array_merge($goal, [
                'match_id' => $match->id,
                'created_by' => $userId,
            ]));
        }
    }

    /**
     * Goles por jugador a partir del detalle preparado.
     *
     * El gol en propia puerta no se acredita a quien lo marcó: cuenta para el
     * rival. Si se sumara aquí, el jugador aparecería en la tabla de goleadores
     * y en el ranking por un gol que no hizo.
     *
     * @param  array<int,array<string,mixed>>  $goals
     * @return array<int,int>  person_id => goles
     */
    public function goalsCountByPlayer(array $goals): array
    {
        $count = [];

        foreach ($goals as $goal) {
            if (filter_var($goal['is_own_goal'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $personId = (int) ($goal['player_id'] ?? 0);

            if ($personId === 0) {
                continue;
            }

            $count[$personId] = ($count[$personId] ?? 0) + 1;
        }

        return $count;
    }

    /**
     * Total de goles del acta cuando el detalle es la fuente.
     *
     * Devuelve todos los jugadores del acta (en cero si no marcaron) para que
     * el detalle mande por completo y no se mezcle con el contador viejo: así
     * el ranking de goleadores y el detalle de goles no pueden contradecirse.
     *
     * @param  array<int,array<string,mixed>>  $players  jugadores tal como llegan del acta
     * @param  array<int,array<string,mixed>>  $goals  goles ya preparados
     * @return array<int,int>  person_id => goles
     */
    public function goalsMapForRoster(array $players, array $goals): array
    {
        $count = $this->goalsCountByPlayer($goals);
        $map = [];

        foreach ($players ?: [] as $player) {
            $personId = (int) ($player['person_id'] ?? 0);

            if ($personId === 0) {
                continue;
            }

            $map[$personId] = $count[$personId] ?? 0;
        }

        return $map;
    }

    /**
     * Asistencias por jugador a partir del detalle preparado.
     *
     * Un gol en propia puerta no tiene asistencia: nadie habilitó a quien la
     * metió en su arco, así que no se le acredita a nadie.
     *
     * @param  array<int,array<string,mixed>>  $goals
     * @return array<int,int>  person_id => asistencias
     */
    public function assistsCountByPlayer(array $goals): array
    {
        $count = [];

        foreach ($goals as $goal) {
            if (filter_var($goal['is_own_goal'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $assistId = (int) ($goal['assist_player_id'] ?? 0);

            if ($assistId === 0) {
                continue;
            }

            $count[$assistId] = ($count[$assistId] ?? 0) + 1;
        }

        return $count;
    }

    /**
     * Asistencias del acta cuando el detalle de goles es la fuente.
     *
     * El asistente se registra dentro del gol ("quién asistió"), así que no hay
     * un contador aparte que pueda contradecirlo: el mapa cubre a todo el acta
     * (en cero si no asistió) igual que goalsMapForRoster() con los goles.
     *
     * @param  array<int,array<string,mixed>>  $players
     * @param  array<int,array<string,mixed>>  $goals  goles ya preparados
     * @return array<int,int>  person_id => asistencias
     */
    public function assistsMapForRoster(array $players, array $goals): array
    {
        $count = $this->assistsCountByPlayer($goals);
        $map = [];

        foreach ($players ?: [] as $player) {
            $personId = (int) ($player['person_id'] ?? 0);

            if ($personId === 0) {
                continue;
            }

            $map[$personId] = $count[$personId] ?? 0;
        }

        return $map;
    }

    /**
     * Goles guardados de un partido, listos para el frontend.
     *
     * @return array<int,array<string,mixed>>
     */
    public function goalsForMatch(int $matchId): array
    {
        return EventEditionMatchGoal::where('match_id', $matchId)
            ->orderBy('minute')
            ->orderBy('id')
            ->get()
            ->map(fn ($goal) => [
                'id' => $goal->id,
                'team_id' => $goal->team_id,
                'player_id' => $goal->player_id,
                'minute' => $goal->minute,
                'minute_in_period' => $goal->minute_in_period,
                'period' => $goal->period,
                'goal_type' => $goal->goal_type ?: 'normal',
                'goal_type_label' => self::GOAL_TYPE_LABELS[$goal->goal_type ?: 'normal'] ?? 'Jugada',
                'is_own_goal' => (bool) $goal->is_own_goal,
                'assist_player_id' => $goal->assist_player_id,
            ])
            ->all();
    }

    /**
     * Tiempos que puede elegir el detalle de atajadas.
     *
     * Es el mismo catálogo que el de goles y cambios (1er y 2do tiempo, y la
     * prórroga solo si se jugó) más la tanda de penales, que no es tiempo de
     * juego pero sí tiene atajadas que registrar.
     *
     * @return string[]
     */
    public function availableSavePeriods(array $timing): array
    {
        return array_merge($this->availablePeriods($timing), [self::PENALTY_SHOOTOUT_PERIOD]);
    }

    /**
     * Valida y normaliza las atajadas de un equipo antes de guardarlas.
     *
     * El arquero sale del acta (no se inventan atajadas de quien no fue
     * alineado) y el pateador es opcional: si no es del equipo rival o ya no
     * está en el acta, se descarta el dato sin perder la atajada.
     *
     * @param  array<int,array<string,mixed>>  $saves
     * @param  array{team_id:int,edition_id:int,roster:int[],roles:array<int,string>,rival_roster:int[],rival_roles:array<int,string>,timing:array,has_penalties?:bool,field:string}  $context
     * @return array<int,array<string,mixed>>
     */
    public function prepareSaves(array $saves, array $context): array
    {
        if (empty($saves)) {
            return [];
        }

        $timing = $context['timing'];
        $roster = $context['roster'];
        $roles = $context['roles'];
        $rivalRoster = $context['rival_roster'] ?? [];
        $rivalRoles = $context['rival_roles'] ?? [];
        $hasPenalties = (bool) ($context['has_penalties'] ?? false);
        $field = $context['field'] ?? 'saves';

        $prepared = [];

        foreach (array_values($saves) as $index => $save) {
            $key = $field . '.' . $index;
            $playerId = (int) ($save['player_id'] ?? 0);

            if ($playerId === 0) {
                throw ValidationException::withMessages([
                    $key => 'Cada atajada necesita su arquero.',
                ]);
            }

            if (! in_array($playerId, $roster, true)) {
                throw ValidationException::withMessages([
                    $key => 'El arquero de la atajada debe pertenecer al equipo.',
                ]);
            }

            // Si el arquero ya no está en el acta (se quitó de la alineación),
            // la atajada se descarta sola: mismo criterio que sus goles.
            if (! isset($roles[$playerId])) {
                continue;
            }

            $period = $save['period'] ?? null;
            $period = ($period === '' || $period === null) ? null : $period;

            $minuteInPeriod = $save['minute_in_period'] ?? null;
            $minuteInPeriod = ($minuteInPeriod === '' || $minuteInPeriod === null)
                ? null
                : (int) $minuteInPeriod;

            if ($period === null) {
                // Sin tiempo no hay minuto que ubicar: queda "sin registrar".
                $minuteInPeriod = null;
            } elseif ($period === self::PENALTY_SHOOTOUT_PERIOD) {
                if (! $hasPenalties) {
                    throw ValidationException::withMessages([
                        $key => 'No se pueden registrar atajadas de la tanda: este partido no la jugó.',
                    ]);
                }

                // La tanda no se mide en minutos de juego.
                $minuteInPeriod = null;
            } else {
                if (! in_array($period, self::PERIODS, true)) {
                    throw ValidationException::withMessages([
                        $key => 'Tiempo de juego no válido.',
                    ]);
                }

                if (in_array($period, self::EXTRA_PERIODS, true) && ! $timing['extra_time_played']) {
                    throw ValidationException::withMessages([
                        $key => 'No se pueden registrar atajadas de la prórroga: este partido no la jugó.',
                    ]);
                }

                if ($minuteInPeriod !== null) {
                    $length = $this->periodLength($timing, $period);

                    if ($minuteInPeriod < 0 || $minuteInPeriod > $length) {
                        throw ValidationException::withMessages([
                            $key => "El minuto debe estar entre 0 y {$length} para el tiempo elegido.",
                        ]);
                    }
                }
            }

            // Cómo llegó el remate: mano a mano, cabeza, tiro olímpico...
            $saveType = $save['save_type'] ?? 'shot';
            $saveType = ($saveType === '' || $saveType === null) ? 'shot' : $saveType;

            if (! in_array($saveType, self::SAVE_TYPES, true)) {
                throw ValidationException::withMessages([
                    $key => 'Tipo de atajada no válido.',
                ]);
            }

            // Quién la pateó: jugador del equipo rival, opcional.
            $shooterId = (int) ($save['shooter_player_id'] ?? 0);

            if ($shooterId !== 0) {
                if ($shooterId === $playerId) {
                    throw ValidationException::withMessages([
                        $key => 'Quien remata no puede ser el mismo arquero que ataja.',
                    ]);
                }

                if (! in_array($shooterId, $rivalRoster, true)) {
                    throw ValidationException::withMessages([
                        $key => 'Quien remata debe pertenecer al equipo rival.',
                    ]);
                }

                if (! isset($rivalRoles[$shooterId])) {
                    $shooterId = 0;
                }
            }

            $prepared[] = [
                'team_id' => (int) $context['team_id'],
                'player_id' => $playerId,
                'minute' => ($period !== null && $minuteInPeriod !== null && $period !== self::PENALTY_SHOOTOUT_PERIOD)
                    ? $this->absoluteMinute($timing, $period, $minuteInPeriod)
                    : null,
                'minute_in_period' => $minuteInPeriod,
                'period' => $period,
                'save_type' => $saveType,
                'is_penalty' => filter_var($save['is_penalty'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'shooter_player_id' => $shooterId !== 0 ? $shooterId : null,
            ];
        }

        return $prepared;
    }

    /**
     * Reemplaza las atajadas del partido por las enviadas (idempotente).
     *
     * @param  array<int,array<string,mixed>>  $savesH  atajadas ya preparadas
     * @param  array<int,array<string,mixed>>  $savesA
     */
    public function syncSaves(EventEditionMatch $match, array $savesH, array $savesA): void
    {
        EventEditionMatchSave::where('match_id', $match->id)->delete();

        $userId = Auth::id();

        foreach (array_merge($savesH, $savesA) as $save) {
            EventEditionMatchSave::create(array_merge($save, [
                'match_id' => $match->id,
                'created_by' => $userId,
            ]));
        }
    }

    /**
     * Atajadas por jugador a partir del detalle preparado.
     *
     * @param  array<int,array<string,mixed>>  $saves
     * @return array<int,int>  person_id => atajadas
     */
    public function savesCountByPlayer(array $saves): array
    {
        $count = [];

        foreach ($saves as $save) {
            $personId = (int) ($save['player_id'] ?? 0);

            if ($personId === 0) {
                continue;
            }

            $count[$personId] = ($count[$personId] ?? 0) + 1;
        }

        return $count;
    }

    /**
     * Total de atajadas del acta cuando el detalle es la fuente.
     *
     * Cubre a todo el acta (en cero si no atajó) igual que goalsMapForRoster()
     * con los goles: así el acumulado del arquero no puede contradecir su
     * detalle de atajadas.
     *
     * @param  array<int,array<string,mixed>>  $players
     * @param  array<int,array<string,mixed>>  $saves  atajadas ya preparadas
     * @return array<int,int>  person_id => atajadas
     */
    public function savesMapForRoster(array $players, array $saves): array
    {
        $count = $this->savesCountByPlayer($saves);
        $map = [];

        foreach ($players ?: [] as $player) {
            $personId = (int) ($player['person_id'] ?? 0);

            if ($personId === 0) {
                continue;
            }

            $map[$personId] = $count[$personId] ?? 0;
        }

        return $map;
    }

    /**
     * Atajadas guardadas de un partido, listas para el frontend.
     *
     * @return array<int,array<string,mixed>>
     */
    public function savesForMatch(int $matchId): array
    {
        return EventEditionMatchSave::where('match_id', $matchId)
            ->orderBy('minute')
            ->orderBy('id')
            ->get()
            ->map(fn ($save) => [
                'id' => $save->id,
                'team_id' => $save->team_id,
                'player_id' => $save->player_id,
                'minute' => $save->minute,
                'minute_in_period' => $save->minute_in_period,
                'period' => $save->period,
                'save_type' => $save->save_type ?: 'shot',
                'save_type_label' => self::SAVE_TYPE_LABELS[$save->save_type ?: 'shot'] ?? 'Tiro / remate',
                'is_penalty' => (bool) $save->is_penalty,
                'shooter_player_id' => $save->shooter_player_id,
            ])
            ->all();
    }

    /**
     * Resumen de goles por tiempo del partido: lo que necesitan los reportes.
     *
     * Separa los goles del tiempo reglamentario de los de la prórroga y cuenta
     * por separado los de la tanda de penales (que no son goles del juego sino
     * definición del empate). Los goles de actas viejas sin tiempo registrado
     * quedan en `unlocated`, nunca se reparten a la fuerza entre los tiempos.
     *
     * @param  array<int,array<string,mixed>>  $goals  filas de goalsForMatch()
     * @param  array<int,array<string,mixed>>|null  $penalties  tanda tal como se guarda en el partido
     * @return array{regulation:int,extra:int,penalties:int,unlocated:int,total:int}
     */
    public function goalBreakdown(array $goals, ?array $penalties = null): array
    {
        $regulation = 0;
        $extra = 0;
        $unlocated = 0;

        foreach ($goals as $goal) {
            $period = $goal['period'] ?? null;

            if ($period !== null && in_array($period, self::EXTRA_PERIODS, true)) {
                $extra++;
            } elseif ($period !== null) {
                $regulation++;
            } else {
                $unlocated++;
            }
        }

        $scoredPenalties = 0;

        foreach ($penalties ?: [] as $penalty) {
            if (($penalty['result'] ?? null) === 'goal') {
                $scoredPenalties++;
            }
        }

        return [
            'regulation' => $regulation,
            'extra' => $extra,
            'penalties' => $scoredPenalties,
            'unlocated' => $unlocated,
            'total' => $regulation + $extra + $unlocated,
        ];
    }
}
