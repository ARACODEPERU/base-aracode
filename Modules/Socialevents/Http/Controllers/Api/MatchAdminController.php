<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EvenLocal;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSave;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Services\MatchPerformanceService;
use Modules\Socialevents\Services\PlayerSuspensionService;
use Modules\Socialevents\Services\PositionTableService;
use Illuminate\Validation\Rule;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Support\TournamentPhaseLabels;
use Modules\Socialevents\Services\SportsOrganizationService;
use Modules\Socialevents\Services\TournamentTieService;

class MatchAdminController extends Controller
{
    private $positionService;

    public function __construct()
    {
        $this->positionService = new PositionTableService();
    }

    public function index(Request $request, int $editionId): JsonResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertOwnedApiEdition($request->user(), $editionId);

        // Accept both query param and body param
        $status = $request->get('status') ?? $request->input('status', 'all');

        $query = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $matches = $query->orderByRaw("CASE
            WHEN status = 'pending' THEN 1
            WHEN status = 'live' THEN 2
            WHEN status = 'finished' THEN 3
            WHEN status = 'closed' THEN 4
            WHEN status = 'walk_over' THEN 5
            WHEN status = 'cancelled' THEN 6
            WHEN status = 'no_points' THEN 7
            END")
            ->orderBy('match_date', 'asc')
            ->get();

        $matchesData = $matches->map(function ($match) {
            return $this->formatMatch($match);
        });

        return response()->json([
            'success' => true,
            'message' => 'Partidos obtenidos correctamente',
            'data' => $matchesData
        ]);
    }

    public function store(Request $request, int $editionId): JsonResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $sports->assertOwnedApiEdition($request->user(), $editionId);
        $validated = $request->validate([
            'status' => 'required|in:pending,live,finished,closed,walk_over,cancelled,no_points',
            'match_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'team_h_id' => 'nullable|integer',
            'team_a_id' => 'nullable|integer',
            // Claves canónicas y alias históricos (quarter, semi) por compatibilidad.
            'phase' => ['required', 'string', Rule::in(array_keys(TournamentPhaseLabels::labels()))],
            'placeholder_h' => 'nullable|string|max:255',
            'placeholder_a' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'round_number' => 'nullable|integer|min:1',
            'score_h' => 'nullable|integer|min:0',
            'score_a' => 'nullable|integer|min:0',
        ]);
        if (! empty($validated['team_h_id']) xor ! empty($validated['team_a_id'])) {
            return response()->json(['success' => false, 'message' => 'Se requieren dos equipos para registrar un partido cobrable.'], 422);
        }

        if (! empty($validated['team_h_id'])) $sports->assertOwnedApiTeam($request->user(), $editionId, (int) $validated['team_h_id']);
        if (! empty($validated['team_a_id'])) $sports->assertOwnedApiTeam($request->user(), $editionId, (int) $validated['team_a_id']);
        if (! empty($validated['team_h_id']) && ! empty($validated['team_a_id'])) {
            abort_unless(EventEditionTeam::where('edition_id', $editionId)->whereIn('team_id', [$validated['team_h_id'], $validated['team_a_id']])->count() === 2, 404);
            $sports->assertCanCreateMatch($request->user(), $editionId);
        }

        if ($request->team_h_id && $request->team_a_id && $request->team_h_id == $request->team_a_id) {
            return response()->json([
                'success' => false,
                'message' => 'El equipo local y visitante no pueden ser el mismo'
            ], 422);
        }

        $match = DB::transaction(function () use ($editionId, $validated, $request, $sports) {
            $match = EventEditionMatch::create([
            'edition_id' => $editionId,
            'status' => $validated['status'],
            'match_date' => $validated['match_date'] ?? null,
            'location' => $validated['location'] ?? null,
            'team_h_id' => $validated['team_h_id'] ?? null,
            'team_a_id' => $validated['team_a_id'] ?? null,
            // Se guarda la clave canónica: los alias históricos (quarter, semi) se normalizan.
            'phase' => TournamentPhaseLabels::canonical($validated['phase']) ?? $validated['phase'],
            'group_name' => $validated['group_name'] ?? null,
            'round_number' => $validated['round_number'] ?? null,
            'score_h' => $validated['score_h'] ?? null,
            'score_a' => $validated['score_a'] ?? null,
            'placeholder_h' => $validated['placeholder_h'] ?? null,
            'placeholder_a' => $validated['placeholder_a'] ?? null,
            ]);
            if (! empty($match->team_h_id) && ! empty($match->team_a_id)) $sports->chargeMatch($match, $request->user());
            return $match;
        });

        return response()->json([
            'success' => true,
            'message' => 'Partido creado correctamente',
            'data' => $this->formatMatch($match->fresh(['equipolocal', 'equipovisitante']))
        ], 201);
    }

    public function update(Request $request, int $match): JsonResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $match = $sports->assertOwnedApiMatch($request->user(), $match);
        $match->load(['equipolocal', 'equipovisitante']);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,live,finished,closed,walk_over,cancelled,no_points',
            'match_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'team_h_id' => 'nullable|integer',
            'team_a_id' => 'nullable|integer',
            'phase' => ['sometimes', 'string', Rule::in(array_keys(TournamentPhaseLabels::labels()))],
            'placeholder_h' => 'nullable|string|max:255',
            'placeholder_a' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'round_number' => 'nullable|integer|min:1',
            'score_h' => 'nullable|integer|min:0',
            'score_a' => 'nullable|integer|min:0',
        ]);

        if (! empty($validated['team_h_id'])) $sports->assertOwnedApiTeam($request->user(), (int) $match->edition_id, (int) $validated['team_h_id']);
        if (! empty($validated['team_a_id'])) $sports->assertOwnedApiTeam($request->user(), (int) $match->edition_id, (int) $validated['team_a_id']);
        $teamH = $validated['team_h_id'] ?? $match->team_h_id;
        $teamA = $validated['team_a_id'] ?? $match->team_a_id;
        if (! empty($teamH) && ! empty($teamA)) {
            abort_unless(EventEditionTeam::where('edition_id', $match->edition_id)->whereIn('team_id', [$teamH, $teamA])->count() === 2, 404);
        }
        if (isset($validated['team_h_id']) && isset($validated['team_a_id']) &&
            $validated['team_h_id'] == $validated['team_a_id']) {
            return response()->json([
                'success' => false,
                'message' => 'El equipo local y visitante no pueden ser el mismo'
            ], 422);
        }

        $teamAssignmentChanged = ($teamH !== $match->team_h_id || $teamA !== $match->team_a_id);
        $hasCompleteTeams = $teamH !== null && $teamA !== null;
        if ($teamAssignmentChanged && ! $hasCompleteTeams && $sports->sportsMode() && ! $sports->isSuperAdmin($request->user())) {
            abort(422, 'No se puede dejar un partido sin ambos equipos después de registrarlo.');
        }
        if ($teamAssignmentChanged && $hasCompleteTeams && $sports->sportsMode() && ! $sports->isSuperAdmin($request->user())) {
            abort_unless(! DB::table('socialevent_organization_ledger')->where('match_id', $match->id)->where('entry_type', 'match_charge')->exists(), 422, 'No se pueden cambiar equipos de un partido ya cobrado.');
            $sports->assertCanCreateMatch($request->user(), (int) $match->edition_id);
        }

        // La fase se guarda siempre canonizada, venga como alias o como clave nueva.
        if (isset($validated['phase'])) {
            $validated['phase'] = TournamentPhaseLabels::canonical($validated['phase']) ?? $validated['phase'];
        }

        DB::transaction(function () use ($match, $validated, $sports, $request, $teamAssignmentChanged, $hasCompleteTeams) {
            $match->update($validated);
            if ($teamAssignmentChanged && $hasCompleteTeams) {
                $sports->chargeMatch($match, $request->user());
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Partido actualizado correctamente',
            'data' => $this->formatMatch($match->fresh(['equipolocal', 'equipovisitante']))
        ]);
    }

    public function destroy(Request $request, int $match): JsonResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $match = $sports->assertOwnedApiMatch($request->user(), $match);

        if ($match->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden eliminar partidos en estado pendiente'
            ], 422);
        }

        DB::transaction(function () use ($match) {
            EventEditionMatchSanction::where('match_id', $match->id)->delete();
            EventEditionMatchPlayerStat::where('match_id', $match->id)->delete();
            EventEditionMatchParticipation::where('match_id', $match->id)->delete();
            EventEditionMatchReport::where('match_id', $match->id)->delete();
            $match->goals()->delete();
            $match->substitutions()->delete();
            $match->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Partido eliminado correctamente'
        ]);
    }

    public function teams(Request $request, int $editionId): JsonResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertOwnedApiEdition($request->user(), $editionId);
        $teams = EventEditionTeam::with('equipo')
            ->where('edition_id', $editionId)
            ->get()
            ->sortBy(function($team) {
                return $team->equipo->name;
            });

        $teamsData = $teams->map(function ($team) {
            return [
                'id' => $team->equipo->id,
                'name' => $team->equipo->name,
                'short_name' => $team->equipo->short_name,
                'logo_url' => $team->equipo->logo_path ? asset('storage/' . $team->equipo->logo_path) : null,
                'points' => $team->points ?? 0,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Equipos obtenidos correctamente',
            'data' => $teamsData
        ]);
    }

    public function locations(Request $request): JsonResponse
    {
        app(SportsOrganizationService::class)->assertCanRead($request->user());
        $locations = EvenLocal::orderBy('names', 'asc')
            ->get(['id', 'names']);

        return response()->json([
            'success' => true,
            'message' => 'Locaciones obtenidas correctamente',
            'data' => $locations
        ]);
    }

    private function formatMatch($match): array
    {
        $hasLocalTeam = $match->equipolocal != null;
        $hasVisitorTeam = $match->equipovisitante != null;

        return [
            'id' => $match->id,
            'status' => $match->status,
            'match_date' => $match->match_date ? $match->match_date->format('Y-m-d H:i:s') : null,
            'match_date_formatted' => $match->match_date
                ? $match->match_date->format('d/m/Y H:i')
                : 'Sin fecha',
            'location' => $match->location,
            'team_h_id' => $match->team_h_id,
            'team_h_name' => $hasLocalTeam ? $match->equipolocal->name : ($match->placeholder_h ?? 'Equipo Local'),
            'team_h_short_name' => $hasLocalTeam ? $match->equipolocal->short_name : ($match->placeholder_h ?? 'LOC'),
            'team_h_logo' => $hasLocalTeam && $match->equipolocal->logo_path
                ? asset('storage/' . $match->equipolocal->logo_path)
                : null,
            'team_h_has_placeholder' => !$hasLocalTeam && $match->placeholder_h != null,
            'team_a_id' => $match->team_a_id,
            'team_a_name' => $hasVisitorTeam ? $match->equipovisitante->name : ($match->placeholder_a ?? 'Equipo Visitante'),
            'team_a_short_name' => $hasVisitorTeam ? $match->equipovisitante->short_name : ($match->placeholder_a ?? 'VIS'),
            'team_a_logo' => $hasVisitorTeam && $match->equipovisitante->logo_path
                ? asset('storage/' . $match->equipovisitante->logo_path)
                : null,
            'team_a_has_placeholder' => !$hasVisitorTeam && $match->placeholder_a != null,
            'score_h' => $match->score_h,
            'score_a' => $match->score_a,
            'phase' => $match->phase,
            'phase_label' => TournamentPhaseLabels::label($match->phase),
            // Cruce de ida y vuelta (la app los muestra cuando existen).
            'tie_format' => $match->tie_format ?? TournamentTieService::FORMAT_SINGLE,
            'decider' => $match->decider ?? TournamentTieService::DECIDER_NORMAL,
            'tie_id' => $match->tie_id,
            'leg' => $match->leg,
            'tie' => ($match->tie_id || (int) $match->leg === 1)
                ? app(TournamentTieService::class)->summary($match)
                : null,
            'group_name' => $match->group_name,
            'round_number' => $match->round_number,
            'is_editable' => in_array($match->status, ['pending']),
            'edition_id' => $match->edition_id,
            'penalty_rounds' => $match->penalty_rounds,
            'penalties' => $match->penalties ?? [],
            // Prórroga y tiempos del partido
            'extra_time_played' => (bool) $match->extra_time_played,
            'score_regular_h' => $match->score_regular_h,
            'score_regular_a' => $match->score_regular_a,
            'half_minutes_override' => $match->half_minutes_override,
            'halves_override' => $match->halves_override,
            'extra_half_minutes_override' => $match->extra_half_minutes_override,
            'timing' => app(MatchPerformanceService::class)->resolvedTiming($match),
        ];
    }

    public function getMatchPlayers(Request $request, int $matchId): JsonResponse
    {
        $match = app(SportsOrganizationService::class)->assertOwnedApiMatch($request->user(), $matchId);
        $match->load(['equipolocal', 'equipovisitante']);
        $players = ['home' => [], 'away' => []];

        foreach (['equipolocal', 'equipovisitante'] as $teamType) {
            $team = $match->$teamType;
            $teamId = $teamType === 'equipolocal' ? $match->team_h_id : $match->team_a_id;

            if ($team && $teamId) {
                $teamPlayers = EventEditionTeamPlayer::with('person')
                    ->where('edition_id', $match->edition_id)
                    ->where('team_id', $teamId)
                    ->orderBy('jersey_number', 'asc')
                    ->get();

                // Suspensiones vigentes para este partido (mismo criterio que la web)
                $suspendedMap = app(PlayerSuspensionService::class)
                    ->getActiveSuspensionsMap((int) $match->edition_id, $match);

                $players[$teamType === 'equipolocal' ? 'home' : 'away'] = $teamPlayers->map(function ($player) use ($match, $suspendedMap) {
                    $suspension = $suspendedMap[$player->person_id] ?? null;

                    $p = [
                        'id' => $player->id,
                        'person_id' => $player->person_id,
                        'team_id' => $player->team_id,
                        'jersey_number' => $player->jersey_number,
                        'position' => $player->position,
                        'role_in_team' => $player->role_in_team,
                        'name' => $player->person ? $player->person->full_name : 'Jugador',
                        'stats' => [],
                        'sanctions' => [],
                        'participation' => null,
                        'is_suspended' => $suspension !== null,
                        'suspension' => $suspension ? [
                            'type' => $suspension->type,
                            'type_label' => $suspension->type_label,
                            'reason' => $suspension->reason,
                            'matches_count' => $suspension->matches_count,
                            'starts_at' => optional($suspension->starts_at)->format('Y-m-d'),
                            'ends_at' => optional($suspension->ends_at)->format('Y-m-d'),
                        ] : null,
                    ];

                    $stats = EventEditionMatchPlayerStat::where('match_id', $match->id)
                        ->where('player_id', $player->person_id)->first();
                    if ($stats) {
                        $p['stats'] = [
                            'goals' => $stats->goals,
                            'assists' => $stats->assists,
                            'saves' => $stats->saves,
                            'minutes_played' => $stats->minutes_played,
                            'is_mvp' => $stats->is_mvp,
                            'clean_sheet' => $stats->clean_sheet,
                        ];
                    }

                    $sanctions = EventEditionMatchSanction::where('match_id', $match->id)
                        ->where('player_id', $player->person_id)->get();
                    $p['sanctions'] = $sanctions->map(function ($s) {
                        return ['type' => $s->type, 'minute' => $s->minute, 'amount_fine' => $s->amount_fine];
                    })->toArray();

                    $participation = EventEditionMatchParticipation::where('match_id', $match->id)
                        ->where('player_id', $player->person_id)->first();
                    if ($participation) {
                        $p['participation'] = ['role' => $participation->role];
                    }

                    return $p;
                })->toArray();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Jugadores obtenidos correctamente',
            'data' => [
                'match' => $this->formatMatch($match),
                'home_players' => $players['home'],
                'away_players' => $players['away'],
                'substitutions' => app(MatchPerformanceService::class)->substitutionsForMatch($match->id),
                // Goles con su tiempo y minuto (uno por fila).
                'goals' => app(MatchPerformanceService::class)->goalsForMatch($match->id),
                // Atajadas con su tipo, pateador y tiempo (una por fila).
                'saves' => app(MatchPerformanceService::class)->savesForMatch($match->id),
            ]
        ]);
    }

    public function saveMatchResult(Request $request, int $matchId): JsonResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $match = $sports->assertOwnedApiMatch($request->user(), $matchId);

        $validated = $request->validate([
            'score_h' => 'required|integer|min:0',
            'score_a' => 'required|integer|min:0',
            'edition_id' => 'required|integer',
            'players_h' => 'nullable|array',
            'players_a' => 'nullable|array',
            'penalty_rounds' => 'nullable|integer|min:1|max:10',
            'penalties' => 'nullable|array',
            'penalties.*.team' => 'nullable|in:local,visitor',
            'penalties.*.result' => 'nullable|in:goal,miss,saved',
            // Quién pateó y qué arquero atajó: sin estas reglas el valor se
            // perdía al validar y la tanda quedaba sin autores.
            'penalties.*.kicker' => 'nullable|integer',
            'penalties.*.goalkeeper' => 'nullable|integer',
            'price_red' => 'nullable|numeric',
            'double_yellow' => 'nullable|numeric',
            'yellow' => 'nullable|numeric',
            // Desempeño individual: cambios, minutos y prórroga
            'substitutions_h' => 'nullable|array',
            'substitutions_a' => 'nullable|array',
            // Goles con su tiempo (mismo formato que la web)
            'goals_h' => 'nullable|array',
            'goals_h.*.player_id' => 'nullable|integer',
            'goals_h.*.period' => 'nullable|string|max:20',
            'goals_h.*.minute_in_period' => 'nullable|integer|min:0',
            'goals_h.*.goal_type' => 'nullable|string|max:20',
            'goals_h.*.assist_player_id' => 'nullable|integer',
            'goals_h.*.is_own_goal' => 'nullable|boolean',
            'goals_a' => 'nullable|array',
            'goals_a.*.player_id' => 'nullable|integer',
            'goals_a.*.period' => 'nullable|string|max:20',
            'goals_a.*.minute_in_period' => 'nullable|integer|min:0',
            'goals_a.*.goal_type' => 'nullable|string|max:20',
            'goals_a.*.assist_player_id' => 'nullable|integer',
            'goals_a.*.is_own_goal' => 'nullable|boolean',
            // Atajadas con su detalle (tipo, pateador y tiempo)
            'saves_h' => 'nullable|array',
            'saves_h.*.player_id' => 'nullable|integer',
            'saves_h.*.period' => 'nullable|string|max:20',
            'saves_h.*.minute_in_period' => 'nullable|integer|min:0',
            'saves_h.*.save_type' => 'nullable|string|max:20',
            'saves_h.*.is_penalty' => 'nullable|boolean',
            'saves_h.*.shooter_player_id' => 'nullable|integer',
            'saves_a' => 'nullable|array',
            'saves_a.*.player_id' => 'nullable|integer',
            'saves_a.*.period' => 'nullable|string|max:20',
            'saves_a.*.minute_in_period' => 'nullable|integer|min:0',
            'saves_a.*.save_type' => 'nullable|string|max:20',
            'saves_a.*.is_penalty' => 'nullable|boolean',
            'saves_a.*.shooter_player_id' => 'nullable|integer',
            'extra_time_played' => 'nullable|boolean',
            'score_regular_h' => 'nullable|integer|min:0',
            'score_regular_a' => 'nullable|integer|min:0',
            'half_minutes_override' => 'nullable|integer|min:1|max:200',
            'halves_override' => 'nullable|integer|min:1|max:4',
            'extra_half_minutes_override' => 'nullable|integer|min:1|max:60',
        ]);

        abort_unless((int) $validated['edition_id'] === (int) $match->edition_id, 404);

        // Bloqueo: jugadores suspendidos no pueden participar en el acta
        $suspendedPlaying = app(\Modules\Socialevents\Services\PlayerSuspensionService::class)
            ->findSuspendedParticipating($match, $validated['players_h'] ?? null, $validated['players_a'] ?? null);

        if (! empty($suspendedPlaying)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede guardar el acta: ' . implode(', ', $suspendedPlaying)
                    . ' está suspendido y no puede participar en este partido.',
                'suspended_players' => $suspendedPlaying,
            ], 422);
        }

        $hasPenalties = ! empty($validated['penalties']);
        $priceRed = $validated['price_red'] ?? 0;
        $priceDoubleYellow = $validated['double_yellow'] ?? 0;
        $priceYellow = $validated['yellow'] ?? 0;

        $performance = app(MatchPerformanceService::class);

        // Los tiempos del partido se aplican en memoria para validar los cambios
        // antes de escribir: web y móvil comparten el mismo criterio.
        $match->fill([
            'extra_time_played' => filter_var($validated['extra_time_played'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'half_minutes_override' => $validated['half_minutes_override'] ?? null,
            'halves_override' => $validated['halves_override'] ?? null,
            'extra_half_minutes_override' => $validated['extra_half_minutes_override'] ?? null,
        ]);

        $timing = $performance->resolvedTiming($match);

        $playersH = $validated['players_h'] ?? [];
        $playersA = $validated['players_a'] ?? [];
        $teamHId = (int) $match->team_h_id;
        $teamAId = (int) $match->team_a_id;
        $editionId = (int) $match->edition_id;

        $subsH = $performance->prepareSubstitutions($validated['substitutions_h'] ?? [], [
            'team_id' => $teamHId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamHId),
            'roles' => $performance->rolesFrom($playersH),
            'timing' => $timing,
            'field' => 'substitutions_h',
        ]);

        $subsA = $performance->prepareSubstitutions($validated['substitutions_a'] ?? [], [
            'team_id' => $teamAId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamAId),
            'roles' => $performance->rolesFrom($playersA),
            'timing' => $timing,
            'field' => 'substitutions_a',
        ]);

        // Los minutos se recalculan desde la alineación y los cambios solo cuando
        // el acta envía ese bloque (mismo criterio que los goles y las atajadas):
        // la app que todavía no lo manda conserva los minutos de su formulario.
        $hasSubstitutions = array_key_exists('substitutions_h', $validated)
            || array_key_exists('substitutions_a', $validated);

        $minutesH = $hasSubstitutions ? $performance->computeMinutes($timing, $performance->rolesFrom($playersH), $subsH) : [];
        $minutesA = $hasSubstitutions ? $performance->computeMinutes($timing, $performance->rolesFrom($playersA), $subsA) : [];

        // Goles con su tiempo: solo se reemplazan cuando el acta los envía.
        $hasGoals = array_key_exists('goals_h', $validated) || array_key_exists('goals_a', $validated);

        $goalsH = $performance->prepareGoals($validated['goals_h'] ?? [], [
            'team_id' => $teamHId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamHId),
            'roles' => $performance->rolesFrom($playersH),
            'timing' => $timing,
            'field' => 'goals_h',
        ]);

        $goalsA = $performance->prepareGoals($validated['goals_a'] ?? [], [
            'team_id' => $teamAId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamAId),
            'roles' => $performance->rolesFrom($playersA),
            'timing' => $timing,
            'field' => 'goals_a',
        ]);

        // Atajadas con su detalle: solo se reemplazan cuando el acta las envía.
        $hasSaves = array_key_exists('saves_h', $validated) || array_key_exists('saves_a', $validated);

        $savesH = $performance->prepareSaves($validated['saves_h'] ?? [], [
            'team_id' => $teamHId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamHId),
            'roles' => $performance->rolesFrom($playersH),
            'rival_roster' => $performance->roster($editionId, $teamAId),
            'rival_roles' => $performance->rolesFrom($playersA),
            'timing' => $timing,
            'has_penalties' => $hasPenalties,
            'field' => 'saves_h',
        ]);

        $savesA = $performance->prepareSaves($validated['saves_a'] ?? [], [
            'team_id' => $teamAId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamAId),
            'roles' => $performance->rolesFrom($playersA),
            'rival_roster' => $performance->roster($editionId, $teamHId),
            'rival_roles' => $performance->rolesFrom($playersH),
            'timing' => $timing,
            'has_penalties' => $hasPenalties,
            'field' => 'saves_a',
        ]);

        // Con el detalle presente, el total por jugador sale de él.
        $goalsMapH = $hasGoals ? $performance->goalsMapForRoster($playersH, $goalsH) : [];
        $goalsMapA = $hasGoals ? $performance->goalsMapForRoster($playersA, $goalsA) : [];

        // La asistencia se registra dentro del gol: con el detalle presente, el
        // asistente del gol es la única fuente y el acta ya no la manda aparte.
        $assistsMapH = $hasGoals ? $performance->assistsMapForRoster($playersH, $goalsH) : [];
        $assistsMapA = $hasGoals ? $performance->assistsMapForRoster($playersA, $goalsA) : [];

        $savesMapH = $hasSaves ? $performance->savesMapForRoster($playersH, $savesH) : [];
        $savesMapA = $hasSaves ? $performance->savesMapForRoster($playersA, $savesA) : [];

        DB::transaction(function () use ($match, $validated, $hasPenalties, $priceRed, $priceDoubleYellow, $priceYellow, $performance, $subsH, $subsA, $minutesH, $minutesA, $playersH, $playersA, $teamHId, $teamAId, $hasGoals, $goalsH, $goalsA, $goalsMapH, $goalsMapA, $assistsMapH, $assistsMapA, $hasSaves, $savesH, $savesA, $savesMapH, $savesMapA) {
            $match->update([
                'score_h' => $validated['score_h'],
                'score_a' => $validated['score_a'],
                'status' => 'finished',
                'original_score' => $validated['score_h'] . '-' . $validated['score_a'],
                'penalty_rounds' => $hasPenalties ? ($validated['penalty_rounds'] ?? null) : null,
                'penalties' => $hasPenalties ? $validated['penalties'] : null,
                'extra_time_played' => filter_var($validated['extra_time_played'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'score_regular_h' => $validated['score_regular_h'] ?? null,
                'score_regular_a' => $validated['score_regular_a'] ?? null,
                'half_minutes_override' => $validated['half_minutes_override'] ?? null,
                'halves_override' => $validated['halves_override'] ?? null,
                'extra_half_minutes_override' => $validated['extra_half_minutes_override'] ?? null,
            ]);

            $performance->syncSubstitutions($match, $subsH, $subsA);

            // Goles con su tiempo y minuto (el detalle manda sobre el contador)
            if ($hasGoals) {
                $performance->syncGoals($match, $goalsH, $goalsA);
            }

            // Atajadas con su detalle (el detalle manda sobre el contador)
            if ($hasSaves) {
                $performance->syncSaves($match, $savesH, $savesA);
            }

            if (isset($validated['players_h'])) {
                $this->savePlayerCards($match->id, $playersH, $priceRed, $priceDoubleYellow, $priceYellow);
                $this->savePlayerStats($match->id, $playersH, $teamHId, $validated['score_a'] == 0, $minutesH, $goalsMapH, $assistsMapH, $savesMapH);
                $this->saveMatchPlayerParticipated($playersH, $match->id);
            }

            if (isset($validated['players_a'])) {
                $this->savePlayerCards($match->id, $playersA, $priceRed, $priceDoubleYellow, $priceYellow);
                $this->savePlayerStats($match->id, $playersA, $teamAId, $validated['score_h'] == 0, $minutesA, $goalsMapA, $assistsMapA, $savesMapA);
                $this->saveMatchPlayerParticipated($playersA, $match->id);
            }

            // Recalcular tabla de posiciones
            $this->positionService->updateTablePositions($validated['edition_id']);
        });

        // La landing cachea el torneo (tabla y rankings): al guardar desde la app
        // hay que soltarlo para que la web muestre las estadísticas corregidas y
        // no el ranking viejo durante los próximos minutos.
        TournamentLandingCache::forget((int) $match->edition_id);

        return response()->json([
            'success' => true,
            'message' => 'Resultado guardado correctamente',
            'data' => $this->formatMatch($match->fresh(['equipolocal', 'equipovisitante']))
        ]);
    }

    private function savePlayerCards($matchId, $players, $priceRed = 0, $priceDoubleYellow = 0, $priceYellow = 0)
    {
        foreach ($players as $p) {
            if (!isset($p['person_id'])) continue;

            EventEditionMatchSanction::where('match_id', $matchId)
                ->where('player_id', $p['person_id'])->delete();

            $yellowCount = $p['yellow_cards'] ?? 0;
            $redCount = $p['red_cards'] ?? 0;
            $isDoubleYellow = $p['is_double_yellow'] ?? false;

            if ($redCount > 0 && !$isDoubleYellow) {
                EventEditionMatchSanction::create([
                    'match_id' => $matchId,
                    'player_id' => $p['person_id'],
                    'type' => 'red',
                    'minute' => $p['red_card_minute'] ?? null,
                    'amount_fine' => $priceRed,
                ]);
            }

            if ($yellowCount === 2 || $isDoubleYellow) {
                EventEditionMatchSanction::create([
                    'match_id' => $matchId,
                    'player_id' => $p['person_id'],
                    'type' => 'double_yellow',
                    'amount_fine' => $priceDoubleYellow,
                ]);
                for ($i = 1; $i <= 2; $i++) {
                    EventEditionMatchSanction::create([
                        'match_id' => $matchId,
                        'player_id' => $p['person_id'],
                        'type' => 'yellow',
                        'amount_fine' => $priceYellow,
                    ]);
                }
            } elseif ($yellowCount === 1) {
                EventEditionMatchSanction::create([
                    'match_id' => $matchId,
                    'player_id' => $p['person_id'],
                    'type' => 'yellow',
                    'amount_fine' => $priceYellow,
                ]);
            }
        }
    }

    /**
     * Minuto que se guarda: el manual si el operador lo marcó así y, si no, el
     * calculado desde la alineación y los cambios.
     */
    private function resolveMinutes(array $player, array $minutesMap): int
    {
        $personId = (int) ($player['person_id'] ?? 0);

        if (($player['minutes_manual'] ?? false) && $personId) {
            return max(0, (int) ($player['minutes_played'] ?? 0));
        }

        if (array_key_exists($personId, $minutesMap)) {
            return max(0, (int) $minutesMap[$personId]);
        }

        return max(0, (int) ($player['minutes_played'] ?? 0));
    }

    private function savePlayerStats($matchId, $players, $teamId, $isCleanSheet, array $minutesMap = [], array $goalsMap = [], array $assistsMap = [], array $savesMap = [])
    {
        if (!$players) return;

        foreach ($players as $p) {
            if (!isset($p['person_id'])) continue;

            // Corrección de acta: si un jugador se quita de la alineación, su
            // estadística de este partido se elimina para que no queden goles,
            // asistencias o MVP fantasma en los rankings.
            if (!isset($p['match_role']) || $p['match_role'] === null || $p['match_role'] === '') {
                EventEditionMatchPlayerStat::where('match_id', $matchId)
                    ->where('player_id', $p['person_id'])
                    ->delete();
                continue;
            }

            EventEditionMatchPlayerStat::updateOrCreate(
                ['match_id' => $matchId, 'player_id' => $p['person_id']],
                [
                    'team_id' => $teamId,
                    // El detalle de goles manda cuando el acta lo envió.
                    'goals' => array_key_exists((int) $p['person_id'], $goalsMap)
                        ? (int) $goalsMap[(int) $p['person_id']]
                        : ($p['goals'] ?? 0),
                    'assists' => array_key_exists((int) $p['person_id'], $assistsMap)
                        ? (int) $assistsMap[(int) $p['person_id']]
                        : ($p['assists'] ?? 0),
                    // El detalle de atajadas manda cuando el acta lo envió.
                    'saves' => array_key_exists((int) $p['person_id'], $savesMap)
                        ? (int) $savesMap[(int) $p['person_id']]
                        : ($p['saves'] ?? 0),
                    'clean_sheet' => $isCleanSheet ? 1 : 0,
                    'minutes_played' => $this->resolveMinutes($p, $minutesMap),
                    'is_mvp' => $p['is_mvp'] ?? false,
                ]
            );
        }
    }

    private function saveMatchPlayerParticipated($players, $matchId)
    {
        foreach ($players as $p) {
            if (!isset($p['person_id'])) continue;

            // Corrección de acta: un jugador retirado de la alineación también
            // sale del acta, para que no cuente como partido jugado.
            if (!isset($p['match_role']) || $p['match_role'] === null || $p['match_role'] === '') {
                EventEditionMatchParticipation::where('match_id', $matchId)
                    ->where('player_id', $p['person_id'])
                    ->delete();
                continue;
            }

            EventEditionMatchParticipation::updateOrCreate(
                ['match_id' => $matchId, 'player_id' => $p['person_id']],
                ['role' => $p['match_role']]
            );
        }
    }

    public function closeMatchReport(Request $request, int $matchId): JsonResponse
    {
        app(SportsOrganizationService::class)->assertCanWrite($request->user());
        $request->validate([
            'observations' => 'nullable|string|max:5000',
            'local_has_protest' => 'nullable|boolean',
            'local_protest_details' => 'nullable|string|max:5000',
            'visitor_has_protest' => 'nullable|boolean',
            'visitor_protest_details' => 'nullable|string|max:5000',
        ]);

        $match = app(SportsOrganizationService::class)->assertOwnedApiMatch($request->user(), $matchId);
        $match->load(['equipolocal', 'equipovisitante']);

        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => 'Partido no encontrado',
            ], 404);
        }

        if ($match->status !== 'finished') {
            return response()->json([
                'success' => false,
                'message' => 'El partido debe estar en estado finalizado para cerrar el acta',
            ], 400);
        }

        $localHasProtest = $request->boolean('local_has_protest', false);
        $visitorHasProtest = $request->boolean('visitor_has_protest', false);
        $hasProtest = $localHasProtest || $visitorHasProtest;

        $protestDetails = [
            'local' => [
                'has_protest' => $localHasProtest,
                'details' => $localHasProtest ? $request->input('local_protest_details') : null,
            ],
            'visitor' => [
                'has_protest' => $visitorHasProtest,
                'details' => $visitorHasProtest ? $request->input('visitor_protest_details') : null,
            ],
        ];

        try {
            DB::transaction(function () use ($match, $request, $hasProtest, $protestDetails) {
                EventEditionMatchReport::updateOrCreate(
                    ['match_id' => $match->id],
                    [
                        'local_score' => $match->score_h,
                        'visitor_score' => $match->score_a,
                        'observations' => $request->input('observations'),
                        'has_protest' => $hasProtest,
                        'protest_details' => $protestDetails,
                        'protest_status' => $hasProtest ? 'pending' : 'none',
                        'closed_at' => now(),
                        'closed_by' => auth()->id(),
                    ]
                );

                if (!$hasProtest) {
                    $match->status = 'closed';
                    $match->save();
                    $this->positionService->updateTablePositions($match->edition_id);
                }
            });

            $match->refresh();

            return response()->json([
                'success' => true,
                'message' => $hasProtest 
                    ? 'Acta registrada. El partido permanece abierto debido a reclamos.' 
                    : 'Acta cerrada exitosamente',
                'match' => [
                    'id' => $match->id,
                    'status' => $match->status,
                    'score_h' => $match->score_h,
                    'score_a' => $match->score_a,
                    'team_h_name' => $match->equipolocal?->name,
                    'team_a_name' => $match->equipovisitante?->name,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar el acta: ' . $e->getMessage(),
            ], 500);
        }
    }
}
