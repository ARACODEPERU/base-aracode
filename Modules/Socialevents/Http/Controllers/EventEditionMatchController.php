<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EvenLocal;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchGoal;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionMatchSave;
use Modules\Socialevents\Entities\EventEditionMatchSubstitution;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Services\MatchPerformanceService;
use Modules\Socialevents\Services\TournamentService;
use Modules\Socialevents\Services\PositionTableService;
use Modules\Socialevents\Services\PlayerSuspensionService;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Services\SportsOrganizationService;


class EventEditionMatchController extends Controller
{
    protected $positionService;

    public function __construct()
    {
        $this->positionService = new PositionTableService();
    }

    public function editionFixtures($id)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $id);
        $edition = EventEdition::findOrFail($id);
        $teams = EventEditionTeam::with('equipo')->where('edition_id', $id)->get();
        $locales = EvenLocal::where('status', true)->get();

        // 1. Obtenemos los partidos base (sin cargar jugadores aún para no saturar)
        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $id)
            ->orderByRaw("CASE
                WHEN match_date >= NOW() THEN 1
                WHEN match_date < NOW() THEN 2
                ELSE 3 END ASC")
            ->orderBy('match_date', 'asc')
            ->get();

        // 2. Agrupamos primero
        $grouped = $matches->groupBy(['phase', 'round_number']);

        // 3. Creamos la variable final que irá al frontend
        $finalFixture = [];

        // Orden de fases
        $phaseOrder = [
            'league' => 1,
            'quarterfinals' => 2,
            'semifinals' => 3,
            'final' => 4,
            'third_place' => 5,
        ];

        foreach ($grouped as $phase => $rounds) {
            foreach ($rounds as $roundNumber => $matchList) {
                foreach ($matchList as $match) {
                    // Cada partido se arma en un solo lugar: así la lista y la vista
                    // dedicada del acta muestran exactamente los mismos datos.
                    $finalFixture[$phase][$roundNumber][] = $this->buildMatchPayload($match, $edition);
                }
            }
        }

        // Ordenar fases
        uksort($finalFixture, function ($a, $b) use ($phaseOrder) {
            return ($phaseOrder[$a] ?? 999) <=> ($phaseOrder[$b] ?? 999);
        });

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id',['6'])->get();
        //dd($finalFixture);
        return Inertia::render('Socialevents::Editions/Fixtures', [
            'teams' => $teams,
            'edition' => $edition,
            'fixture' => $finalFixture,
            'locales' => $locales,
            'ubigeo' => $ubigeo,
            'documentTypes' => $documentTypes,
        ]);
    }

    /**
     * Arma un partido con todo lo que necesita el acta: alineaciones con sus
     * estadísticas, sanciones y participación, cambios, goles con su tiempo y
     * los tiempos efectivos del encuentro.
     */
    private function buildMatchPayload(EventEditionMatch $match, EventEdition $edition): array
    {
        $performance = app(MatchPerformanceService::class);

        $matchData = $match->toArray(); // Convertimos el partido a array base

        // La edición ya está cargada: los tiempos se resuelven sin otra consulta.
        $match->setRelation('edicion', $edition);

        foreach (['equipolocal', 'equipovisitante'] as $teamType) {
            $team = $match->$teamType;

            if ($team) {
                // Buscamos los jugadores del equipo en esta edición
                $players = EventEditionTeamPlayer::with('person')
                    ->where('edition_id', $match->edition_id)
                    ->where('team_id', $team->id)
                    ->get();

                // Suspensiones vigentes para este partido (badges en alineaciones)
                $suspendedMap = app(PlayerSuspensionService::class)
                    ->getActiveSuspensionsMap((int) $match->edition_id, $match);

                // Formateamos cada jugador con sus datos específicos de este partido
                $formattedPlayers = $players->map(function ($player) use ($match, $suspendedMap) {
                    $pArray = $player->toArray();

                    $suspension = $suspendedMap[$player->person_id] ?? null;
                    $pArray['is_suspended'] = $suspension !== null;
                    $pArray['suspension'] = $suspension ? [
                        'type' => $suspension->type,
                        'type_label' => $suspension->type_label,
                        'reason' => $suspension->reason,
                    ] : null;

                    // Buscamos Stats
                    $pArray['stats'] = EventEditionMatchPlayerStat::where('match_id', $match->id)
                        ->where('player_id', $player->person_id)
                        ->get()->toArray();

                    // Buscamos Sanciones
                    $pArray['sanctions'] = EventEditionMatchSanction::where('match_id', $match->id)
                        ->where('player_id', $player->person_id)
                        ->get()->toArray();

                    // Buscamos Participación
                    $participation = EventEditionMatchParticipation::where('match_id', $match->id)
                        ->where('player_id', $player->person_id)
                        ->first();

                    $pArray['participations'] = $participation ? $participation->toArray() : null;
                    $pArray['match_role'] = $participation ? $participation->role : null;

                    return $pArray;
                });

                $matchData[$teamType]['players'] = $formattedPlayers;
            }
        }

        // Cambios del partido (salen / entran / minuto) y tiempos efectivos
        // para que el acta se abra con el reloj correcto.
        $matchData['substitutions'] = $performance->substitutionsForMatch($match->id);
        // Goles con su tiempo, minuto, tipo y asistente: el acta los abre cargados.
        $matchData['goals'] = $performance->goalsForMatch($match->id);
        // Atajadas con su tipo, pateador y tiempo: el acta las abre cargadas.
        $matchData['saves'] = $performance->savesForMatch($match->id);
        $matchData['timing'] = $performance->resolvedTiming($match);

        return $matchData;
    }

    /**
     * Vista dedicada del acta de un partido.
     *
     * El registro de resultados dejó de vivir en un modal del fixture: aquí hay
     * espacio para el detalle (goles con su tiempo y tipo, asistencias, cambios,
     * minutos) y se puede volver al fixture en cualquier momento.
     */
    public function editionMatchScore($id, $matchId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $id);
        $sports->assertOwnedMatch(request()->user(), (int) $matchId);
        $edition = EventEdition::findOrFail($id);
        $match = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $id)
            ->findOrFail($matchId);

        return Inertia::render('Socialevents::Editions/MatchScore', [
            'edition' => $edition,
            'match' => $this->buildMatchPayload($match, $edition),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function editionFixturesCreate($id)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $id);
        $edition = EventEdition::findOrFail($id);
        $teams = EventEditionTeam::with('equipo')
            ->where('edition_id', $id)
            ->whereHas('equipo', fn ($q) => $q->where('status', true))
            ->get()
            ->pluck('equipo')
            ->values();
        $locales = EvenLocal::where('status', true)->get();

        return Inertia::render('Socialevents::Fixtures/Create',[
            'edition' => $edition,
            'teams' => $teams,
            'locales' => $locales
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function editionFixturesGenerate(Request $request, $id)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $sports->assertOwnedEdition($request->user(), (int) $id);
        $request->validate([
            'teams' => 'required|array|min:2',
            'format' => 'required|string',
        ], [
            'teams.required' => 'No se han seleccionado equipos para generar el torneo.',
            'teams.min' => 'Necesitas al menos 2 equipos.'
        ]);
        $editionId = (int) $id;
        foreach ((array) $request->get('teams') as $teamId) {
            $sports->assertOwnedEditionTeam($request->user(), $editionId, (int) $teamId);
        }
        $teamIds = collect((array) $request->get('teams'))->map(fn ($teamId) => (int) $teamId);
        abort_unless($teamIds->unique()->count() === $teamIds->count(), 422, 'No se puede inscribir el mismo equipo varias veces.');
        $registeredTeamIds = EventEditionTeam::where('edition_id', $editionId)->whereIn('team_id', $teamIds)->pluck('team_id')->map(fn ($teamId) => (int) $teamId)->all();
        abort_unless(count($registeredTeamIds) === $teamIds->count(), 404);

        $service = new TournamentService();
        $service->generate($editionId, $request->get('format'), $teamIds->all(), function ($matches) use ($sports, $request) {
            $sports->chargeMatches($matches, $request->user());
        });
    }

    /**
     * Show the specified resource.
     */
    public function editionFixturesStore(Request $request)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        // dd($request->all());
        // 1. Validación de campos básicos
        $validated = $request->validate([
            'edition_id'   => 'required|exists:event_editions,id',
            'team_h'    => 'required|exists:event_teams,id|different:team_a',
            'team_a'    => 'required|exists:event_teams,id',
            'phase'        => 'required|string',
            'round_number' => 'nullable|integer',
            'group_name'   => 'nullable|string|max:1',
            'match_date'   => 'nullable|date',
            'location'     => 'nullable|string|max:255',
        ], [
            'team_h.different' => 'Un equipo no puede jugar contra sí mismo.',
        ]);

        $sports->assertOwnedEditionTeam($request->user(), (int) $validated['edition_id'], (int) $validated['team_h']);
        $sports->assertOwnedEditionTeam($request->user(), (int) $validated['edition_id'], (int) $validated['team_a']);
        $sports->assertCanCreateMatch($request->user(), (int) $validated['edition_id'], 2);

        // 1.1 Validación de que ambos equipos estén inscritos en la edición
        $inscritos = EventEditionTeam::where('edition_id', $validated['edition_id'])
            ->whereIn('team_id', [$validated['team_h'], $validated['team_a']])
            ->pluck('team_id')
            ->all();

        if (count($inscritos) !== 2) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'team_h' => 'Ambos equipos deben estar inscritos en la edición para registrar el partido.',
            ]);
        }

        // 2. Validación de duplicados en la misma FASE
        // Verificamos si ya existe un partido entre estos dos equipos en esta edición y fase
        $exists = EventEditionMatch::where('edition_id', $validated['edition_id'])
            ->where('phase', $validated['phase'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('team_h_id', $validated['team_h'])
                    ->where('team_a_id', $validated['team_a']);
                })->orWhere(function ($q) use ($validated) {
                    $q->where('team_h_id', $validated['team_a'])
                    ->where('team_a_id', $validated['team_h']);
                });
            })
            ->exists();

        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'team_h' => "Estos equipos ya tienen un partido registrado en la fase: {$validated['phase']}."
            ]);
        }

        // 3. Registro y débito del prepago en una sola transacción
        DB::transaction(function () use ($request, $validated, $sports) {
            $sports->assertCanCreateMatch($request->user(), (int) $validated['edition_id'], 2);
            $match = EventEditionMatch::create([
            'edition_id'   => $validated['edition_id'],
            'team_h_id'    => $validated['team_h'],
            'team_a_id'    => $validated['team_a'],
            'phase'        => $validated['phase'],
            'round_number' => $validated['round_number'] ?? 1,
            'group_name'   => $validated['group_name'],
            'match_date'   => $validated['match_date'],
            'location'     => $validated['location'],
            'status'       => 'pending', // Por defecto
            ]);
            $sports->chargeMatch($match, $request->user());
        });

        TournamentLandingCache::forget((int) $validated['edition_id']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function editionFixturesUpdate(Request $request, $id)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $request->validate([
            'round_number' => 'required|max:2',
            //'group_name' => 'required|string',
            'match_date' => 'required|string',
            //'location' => 'required|string',
            'equipo_h_id' => 'nullable|exists:event_teams,id|different:equipo_a_id',
            'equipo_a_id' => 'nullable|exists:event_teams,id|different:equipo_h_id',
        ]);

        // Validación adicional: si ambos equipos están seleccionados, no pueden ser el mismo
        if ($request->equipo_h_id && $request->equipo_a_id && $request->equipo_h_id == $request->equipo_a_id) {
            return back()->withErrors(['equipo_h_id' => 'El equipo local y visitante no pueden ser el mismo.']);
        }

        $match = $sports->assertOwnedMatch($request->user(), (int) $id);
        if ($sports->sportsMode() && ! $sports->isSuperAdmin($request->user())) {
            abort_unless((int) $request->input('round_number') === (int) $match->round_number, 422, 'La edición de una fecha programada no se puede cambiar después de crear el partido.');
            if ($match->team_h_id && $match->team_a_id) {
                abort_unless(! DB::table('socialevent_organization_ledger')->where('match_id', $match->id)->where('entry_type', 'match_charge')->exists(), 422, 'No se pueden modificar los equipos de un partido que ya fue cobrado.');
            }
        }
        $teamH = $request->input('equipo_h_id', $match->team_h_id);
        $teamA = $request->input('equipo_a_id', $match->team_a_id);
        if ($teamH) $sports->assertOwnedEditionTeam($request->user(), (int) $match->edition_id, (int) $teamH);
        if ($teamA) $sports->assertOwnedEditionTeam($request->user(), (int) $match->edition_id, (int) $teamA);
        $teamsChanged = (int) $teamH !== (int) $match->team_h_id || (int) $teamA !== (int) $match->team_a_id;
        if ($teamsChanged && (! $teamH || ! $teamA) && $sports->sportsMode() && ! $sports->isSuperAdmin($request->user())) {
            abort(422, 'No se puede dejar un partido sin ambos equipos después de registrarlo.');
        }
        if ($teamsChanged && $teamH && $teamA) {
            if ($sports->sportsMode() && ! $sports->isSuperAdmin($request->user()) && $match->status === 'pending') {
                abort_unless(! DB::table('socialevent_organization_ledger')->where('match_id', $match->id)->where('entry_type', 'match_charge')->exists(), 422, 'No se pueden cambiar equipos de un partido ya cobrado.');
                $sports->assertCanCreateMatch($request->user(), (int) $match->edition_id, 2);
            }
            DB::transaction(function () use ($match, $teamH, $teamA, $request, $sports) {
                $match->update([
                    'team_h_id' => $teamH,
                    'team_a_id' => $teamA,
                    'round_number' => $request->get('round_number'),
                    'group_name' => $request->get('group_name') ?? null,
                    'match_date' => $request->get('match_date'),
                    'location' => $request->get('location') ?? null,
                    'status' => $request->get('status') ?? 'pending',
                    'placeholder_h' => null,
                    'placeholder_a' => null,
                ]);
                if ($match->status === 'pending') $sports->chargeMatch($match, $request->user());
            });
        } else {
            $match->update([
                'team_h_id' => $teamH,
                'team_a_id' => $teamA,
                'round_number' => $request->get('round_number'),
                'group_name' => $request->get('group_name') ?? null,
                'match_date' => $request->get('match_date'),
                'location' => $request->get('location') ?? null,
                'status' => $request->get('status') ?? 'pending',
                'placeholder_h' => null,
                'placeholder_a' => null,
            ]);
        }
        $score_h = $request->get('score_h') ?? 0;
        $score_a = $request->get('score_a') ?? 0;

        if($request->has('score_h') && $request->has('score_a')) {
            $match->update([
                'score_h' => $score_h,
                'score_a' => $score_a,
                'status' => 'closed',
            ]);
            $this->positionService->updateTablePositions((int) $match->edition_id);
        } else {
            // El cambio pudo ser solo de estado (p. ej. Cancelado -> "Jugado sin
            // puntos"): recalcular la tabla para reflejarlo al instante. El
            // recalculo es idempotente (siempre parte de cero).
            $this->positionService->updateTablePositions((int) $match->edition_id);
            TournamentLandingCache::forget((int) $match->edition_id);
        }

    }

    public function editionMatchScoreStore(Request $request){
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite($request->user());
        $request->validate([
            'substitutions_h' => 'nullable|array',
            'substitutions_a' => 'nullable|array',
            // Goles con su tiempo: el detalle es el que permite separar los goles
            // del tiempo reglamentario de los de la prórroga.
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
            // Atajadas del arquero con su detalle (tipo, pateador y tiempo)
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
        ], [
            'half_minutes_override.min' => 'Los minutos por tiempo deben ser al menos 1.',
            'extra_half_minutes_override.min' => 'Los minutos del tiempo suplementario deben ser al menos 1.',
        ]);

        $match = $sports->assertOwnedMatch($request->user(), (int) $request->get('id'));

        // Bloqueo: jugadores suspendidos no pueden participar en el acta
        $suspendedPlaying = app(PlayerSuspensionService::class)->findSuspendedParticipating(
            $match,
            $request->get('players_h'),
            $request->get('players_a')
        );

        if (! empty($suspendedPlaying)) {
            return back()->withErrors([
                'score' => 'No se puede guardar el acta: ' . implode(', ', $suspendedPlaying)
                    . ' está suspendido y no puede participar en este partido. Quítalo de la alineación o levanta su suspensión.'
            ]);
        }

        $performance = app(MatchPerformanceService::class);

        // Los tiempos del partido (override) se aplican en memoria para validar los
        // cambios ANTES de escribir nada; si algo no cuadra, no se guarda el acta.
        $match->fill([
            'extra_time_played' => $request->boolean('extra_time_played'),
            'half_minutes_override' => $request->get('half_minutes_override'),
            'halves_override' => $request->get('halves_override'),
            'extra_half_minutes_override' => $request->get('extra_half_minutes_override'),
        ]);

        $timing = $performance->resolvedTiming($match);

        $playersH = $request->get('players_h') ?: [];
        $playersA = $request->get('players_a') ?: [];
        $teamHId = (int) $request->get('equipo_h_id');
        $teamAId = (int) $request->get('equipo_a_id');
        $editionId = (int) $match->edition_id;
        if ($sports->sportsMode() && ! $sports->isSuperAdmin($request->user())) {
            abort_unless($teamHId > 0 && $teamAId > 0, 422, 'No se puede guardar el acta sin ambos equipos inscritos.');
            $sports->assertOwnedEditionTeam($request->user(), $editionId, $teamHId);
            $sports->assertOwnedEditionTeam($request->user(), $editionId, $teamAId);
        }

        $subsH = $performance->prepareSubstitutions($request->get('substitutions_h') ?: [], [
            'team_id' => $teamHId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamHId),
            'roles' => $performance->rolesFrom($playersH),
            'timing' => $timing,
            'field' => 'substitutions_h',
        ]);

        $subsA = $performance->prepareSubstitutions($request->get('substitutions_a') ?: [], [
            'team_id' => $teamAId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamAId),
            'roles' => $performance->rolesFrom($playersA),
            'timing' => $timing,
            'field' => 'substitutions_a',
        ]);

        // Los minutos se recalculan desde la alineación y los cambios solo cuando
        // el acta envía ese bloque: igual que con los goles y las atajadas, el
        // detalle manda cuando está presente. La app móvil (que todavía no lo
        // manda) conserva los minutos que el operador escribió a mano.
        $hasSubstitutions = $request->has('substitutions_h') || $request->has('substitutions_a');

        $minutesH = $hasSubstitutions ? $performance->computeMinutes($timing, $performance->rolesFrom($playersH), $subsH) : [];
        $minutesA = $hasSubstitutions ? $performance->computeMinutes($timing, $performance->rolesFrom($playersA), $subsA) : [];

        // Goles con su tiempo. Solo se reemplazan cuando el acta los envía: la app
        // móvil todavía no manda el detalle y su guardado no debe borrar lo que se
        // registró desde la web.
        $hasGoals = $request->has('goals_h') || $request->has('goals_a');

        $goalsH = $performance->prepareGoals($request->get('goals_h') ?: [], [
            'team_id' => $teamHId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamHId),
            'roles' => $performance->rolesFrom($playersH),
            'timing' => $timing,
            'field' => 'goals_h',
        ]);

        $goalsA = $performance->prepareGoals($request->get('goals_a') ?: [], [
            'team_id' => $teamAId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamAId),
            'roles' => $performance->rolesFrom($playersA),
            'timing' => $timing,
            'field' => 'goals_a',
        ]);

        // Atajadas con su detalle. Igual que los goles: solo se reemplazan cuando
        // el acta las envía, para que el guardado de la app móvil (que todavía
        // manda el contador) no borre lo registrado desde la web.
        $hasSaves = $request->has('saves_h') || $request->has('saves_a');

        $savesH = $performance->prepareSaves($request->get('saves_h') ?: [], [
            'team_id' => $teamHId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamHId),
            'roles' => $performance->rolesFrom($playersH),
            'rival_roster' => $performance->roster($editionId, $teamAId),
            'rival_roles' => $performance->rolesFrom($playersA),
            'timing' => $timing,
            'has_penalties' => $request->boolean('has_penalties'),
            'field' => 'saves_h',
        ]);

        $savesA = $performance->prepareSaves($request->get('saves_a') ?: [], [
            'team_id' => $teamAId,
            'edition_id' => $editionId,
            'roster' => $performance->roster($editionId, $teamAId),
            'roles' => $performance->rolesFrom($playersA),
            'rival_roster' => $performance->roster($editionId, $teamHId),
            'rival_roles' => $performance->rolesFrom($playersH),
            'timing' => $timing,
            'has_penalties' => $request->boolean('has_penalties'),
            'field' => 'saves_a',
        ]);

        // Con el detalle presente, el total por jugador sale de él.
        $goalsMapH = $hasGoals ? $performance->goalsMapForRoster($playersH, $goalsH) : [];
        $goalsMapA = $hasGoals ? $performance->goalsMapForRoster($playersA, $goalsA) : [];

        $savesMapH = $hasSaves ? $performance->savesMapForRoster($playersH, $savesH) : [];
        $savesMapA = $hasSaves ? $performance->savesMapForRoster($playersA, $savesA) : [];

        // La asistencia se registra dentro del gol: con el detalle presente, el
        // asistente del gol es la única fuente y el acta ya no la manda aparte.
        $assistsMapH = $hasGoals ? $performance->assistsMapForRoster($playersH, $goalsH) : [];
        $assistsMapA = $hasGoals ? $performance->assistsMapForRoster($playersA, $goalsA) : [];

        DB::transaction(function () use ($request, $match, $performance, $playersH, $playersA, $teamHId, $teamAId, $subsH, $subsA, $minutesH, $minutesA, $editionId, $hasGoals, $goalsH, $goalsA, $goalsMapH, $goalsMapA, $assistsMapH, $assistsMapA, $hasSaves, $savesH, $savesA, $savesMapH, $savesMapA) {
            $matchId = $match->id;
            $price_red = $request->get('price_red');
            $double_yellow = $request->get('double_yellow');
            $yellow = $request->get('yellow');

            $match->update([
                'score_h' => $request->get('score_h'),
                'score_a' => $request->get('score_a'),
                'status' => 'finished',
                'original_score' => $request->get('score_h'). '-'.$request->get('score_a'),
                'penalty_rounds' => $request->get('penalty_rounds'),
                'penalties' => $request->get('penalties'),
                // Prórroga y tiempos del partido
                'extra_time_played' => $request->boolean('extra_time_played'),
                'score_regular_h' => $request->get('score_regular_h'),
                'score_regular_a' => $request->get('score_regular_a'),
                'half_minutes_override' => $request->get('half_minutes_override'),
                'halves_override' => $request->get('halves_override'),
                'extra_half_minutes_override' => $request->get('extra_half_minutes_override'),
            ]);

            // 1. Cambios de jugadores (reemplaza los anteriores)
            $performance->syncSubstitutions($match, $subsH, $subsA);

            // 1b. Goles con su tiempo y minuto (el detalle manda sobre el contador)
            if ($hasGoals) {
                $performance->syncGoals($match, $goalsH, $goalsA);
            }

            // 1c. Atajadas con su detalle (el detalle manda sobre el contador)
            if ($hasSaves) {
                $performance->syncSaves($match, $savesH, $savesA);
            }

            $this->savePlayerCards($matchId, $playersH, $price_red, $double_yellow, $yellow);
            $this->savePlayerCards($matchId, $playersA, $price_red, $double_yellow, $yellow);
            // 2. Procesar estadísticas Local
            $this->savePlayerStats($matchId, $playersH, $teamHId, $request->get('score_a') == 0, $minutesH, $goalsMapH, $assistsMapH, $savesMapH);

            // 3. Procesar estadísticas Visitante
            $this->savePlayerStats($matchId, $playersA, $teamAId, $request->get('score_h') == 0, $minutesA, $goalsMapA, $assistsMapA, $savesMapA);

            // 4. registrae lo jugadores partisipantes
            $this->saveMatchPlayerParticipated($playersH, $playersA,  $matchId);

            // Al final de la transacción, recalculamos la tabla
            // se suman al crear el acta
            $this->positionService->updateTablePositions($editionId);
        });
    }

    /**
     * Minuto que se guarda para un jugador: el manual si lo marcó así el operador
     * y, si no, el calculado a partir de la alineación y los cambios.
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

    private function savePlayerCards($matchId, $players, $price_red = 0, $double_yellow = 0, $yellow = 0)
    {
        $players = $players ?: [];

        foreach ($players as $p) {
            // Limpiamos registros previos de este partido para evitar duplicados si corrigen el marcador
            EventEditionMatchSanction::where('match_id', $matchId)->where('player_id', $p['person_id'])->delete();

            // CASO 1: Roja Directa
            if ($p['red_cards'] > 0 && !$p['is_double_yellow']) {
                EventEditionMatchSanction::create([
                    'match_id' => $matchId,
                    'player_id' => $p['person_id'],
                    'type' => 'red',
                    'amount_fine' => $price_red, // Ejemplo de cobro
                ]);
            }

            // CASO 2: Doble Amarilla (Se registra la expulsión por acumulación)
            if ($p['yellow_cards'] === 2) {
                EventEditionMatchSanction::create([
                    'match_id' => $matchId,
                    'player_id' => $p['person_id'],
                    'type' => 'double_yellow',
                    'amount_fine' => $double_yellow
                ]);

                // Opcional: Registrar las 2 amarillas individualmente para el historial
                for ($i = 1; $i <= 2; $i++) {
                    EventEditionMatchSanction::create([
                        'match_id' => $matchId,
                        'player_id' => $p['person_id'],
                        'type' => 'yellow',
                        'amount_fine' => $yellow
                    ]);
                }
            }
            // CASO 3: Una sola amarilla
            elseif ($p['yellow_cards'] === 1) {
                EventEditionMatchSanction::create([
                    'match_id' => $matchId,
                    'player_id' => $p['person_id'],
                    'type' => 'yellow',
                    'amount_fine' => $yellow
                ]);
            }
        }
    }

    public function savePlayerStats($matchId, $players, $teamId, $isCleanSheet, array $minutesMap = [], array $goalsMap = [], array $assistsMap = [], array $savesMap = [])
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

            if (isset($p['match_role']) && $p['match_role'] !== null) {
                EventEditionMatchPlayerStat::updateOrCreate(
                    [
                        'match_id'  => $matchId,
                        'player_id' => $p['person_id'], // Usamos el ID de la persona
                    ],
                    [
                        'team_id'     => $teamId,
                        // El detalle de goles manda cuando el acta lo envió.
                        'goals'       => array_key_exists((int) $p['person_id'], $goalsMap)
                            ? (int) $goalsMap[(int) $p['person_id']]
                            : ($p['goals'] ?? 0),
                        'assists'     => array_key_exists((int) $p['person_id'], $assistsMap)
                            ? (int) $assistsMap[(int) $p['person_id']]
                            : ($p['assists'] ?? 0),
                        // El detalle de atajadas manda cuando el acta lo envió.
                        'saves'       => array_key_exists((int) $p['person_id'], $savesMap)
                            ? (int) $savesMap[(int) $p['person_id']]
                            : ($p['saves'] ?? 0),
                        'clean_sheet' => $isCleanSheet ? 1 : 0,
                        // El valor calculado desde los cambios es el que manda; solo se
                        // respeta el número escrito a mano cuando viene marcado como manual.
                        'minutes_played' => $this->resolveMinutes($p, $minutesMap),
                        'is_mvp'         => $p['is_mvp'] ?? false,
                    ]
                );
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanWrite(request()->user());
        $sports->assertOwnedMatch(request()->user(), (int) $id);
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = EventEditionMatch::findOrFail($id);

            // Eliminamos los registros asociados al partido para evitar errores de integridad referencial.
            EventEditionMatchSanction::where('match_id', $item->id)->delete();
            EventEditionMatchPlayerStat::where('match_id', $item->id)->delete();
            EventEditionMatchGoal::where('match_id', $item->id)->delete();
            EventEditionMatchSave::where('match_id', $item->id)->delete();
            EventEditionMatchReport::where('match_id', $item->id)->delete();
            EventEditionMatchParticipation::where('match_id', $item->id)->delete();
            EventEditionMatchSubstitution::where('match_id', $item->id)->delete();

            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

            TournamentLandingCache::forget((int) $item->edition_id);

            $message =  'Eliminado correctamente';
            $success = true;
        } catch (\Exception $e) {
            // Si ocurre alguna excepción durante la transacción, hacemos rollback para deshacer cualquier cambio.
            DB::rollback();
            $success = false;
            $message = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'message' => $message
        ]);
    }


    public function saveMatchPlayerParticipated($playersH, $playersA,  $matchId)
    {

            EventEditionMatchParticipation::where('match_id', $matchId)->delete();

            // 2. Recorrer todos los jugadores enviados (locales y visitantes)
            $allPlayers = array_merge($playersH, $playersA);

            foreach ($allPlayers as $playerData) {
                // SOLO registramos si se marcó como Titular o Suplente
                if (isset($playerData['match_role']) && $playerData['match_role'] !== null) {
                    // Guardar Participación
                    EventEditionMatchParticipation::create([
                        'match_id' => $matchId,
                        'player_id' => $playerData['person_id'],
                        'role' => $playerData['match_role']
                    ]);
                }
            }

    }

    public function storeMatchReport(Request $request)
    {
        app(SportsOrganizationService::class)->assertCanWrite($request->user());
        $request->validate([
            'match_id' => 'required|exists:event_edition_matches,id',
            'equipo_h_id' => 'required',
            'equipo_a_id' => 'required',
        ]);

        $match = app(SportsOrganizationService::class)->assertOwnedMatch($request->user(), (int) $request->match_id);
        DB::transaction(function () use ($request, $match) {
            // 1. Creamos o actualizamos el acta (MatchReport)
            EventEditionMatchReport::updateOrCreate(
                ['match_id' => $request->match_id],
                [
                    'closed_at' => now(),
                    'closed_by' => Auth::id(),
                    'local_score' => $request->get('score_h'),
                    'visitor_score' => $request->get('score_a'),
                    'observations' => $request->get('observations'),
                    'has_protest' => $request->get('has_protest') ? true : false,
                    'protest_details' => $request->get('has_protest') ? $request->get('protest_details') : null ,
                    'incident_report' => $request->incident_report,
                    'final_status' => $request->status,
                    'referees' => $request->get('referees'),
                    'protest_status' => $request->get('has_protest') ? 'pending' : 'none',
                ]
            );
            if(!$request->get('has_protest')){
                // 2. Actualizamos el estado del partido
                $match = EventEditionMatch::find($request->match_id);
                $match->status = 'closed'; // O el estado que manejes para bloquear edición
                // 3. Opcional: Aquí podrías disparar el recalculo de la tabla de posiciones
                $this->positionService->updateTablePositions($match->edition_id);

                $match->save();
            }
        });
    }

    public function printMatchSheet($editionId, $matchId)
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead(request()->user());
        $sports->assertOwnedEdition(request()->user(), (int) $editionId);
        $match = $sports->assertOwnedMatch(request()->user(), (int) $matchId);
        abort_unless((int) $match->edition_id === (int) $editionId, 404);
        $edition = EventEdition::with('evento')->findOrFail($editionId);
        $match = EventEditionMatch::with(['equipolocal.manager', 'equipovisitante.manager'])->findOrFail($matchId);

        $data = [
            'event_name'      => $edition->evento->title,
            'edition_name'    => $edition->name,
            'local_team'      => $match->equipolocal?->name ?? 'TBD',
            'visitor_team'    => $match->equipovisitante?->name ?? 'TBD',
            'local_manager'   => $match->equipolocal?->manager?->formatted_name ?? 'Sin asignar',
            'visitor_manager'  => $match->equipovisitante?->manager?->formatted_name ?? 'Sin asignar',
            'match_date'      => $match->match_date ? $match->match_date->format('d/m/Y H:i') : null,
            'location'        => $match->location,
        ];

        $pdf = Pdf::loadView('socialevents::teams.pdf.match_sheet', [
            'data' => $data
        ]);

        Storage::disk('public')->deleteDirectory('temp_pdfs');
        Storage::disk('public')->makeDirectory('temp_pdfs');

        $fileName = 'ficha_partido_' . $match->id . '_' . date('Ymd') . '.pdf';
        $filePath = 'temp_pdfs/' . $fileName;

        Storage::disk('public')->put($filePath, $pdf->output());

        return response()->json([
            'url' => asset('storage/' . $filePath)
        ]);
    }
}
