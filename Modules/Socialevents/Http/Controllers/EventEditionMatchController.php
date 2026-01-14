<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Socialevents\Entities\EvenLocal;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchParticipation;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Services\TournamentService;
use Modules\SocialEvents\Services\PositionTableService;


class EventEditionMatchController extends Controller
{
    protected $positionService;

    public function __construct()
    {
        $this->positionService = new PositionTableService();
    }

    public function editionFixtures($id)
    {
        $edition = EventEdition::find($id);
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

        foreach ($grouped as $phase => $rounds) {
            foreach ($rounds as $roundNumber => $matchList) {
                foreach ($matchList as $match) {

                    // Procesamos los equipos y sus jugadores para este partido específico
                    $matchData = $match->toArray(); // Convertimos el partido a array base

                    foreach (['equipolocal', 'equipovisitante'] as $teamType) {
                        $team = $match->$teamType;

                        if ($team) {
                            // Buscamos los jugadores del equipo en esta edición
                            $players = EventEditionTeamPlayer::with('person')
                                ->where('edition_id', $match->edition_id)
                                ->where('team_id', $team->id)
                                ->get();

                            // Formateamos cada jugador con sus datos específicos de este partido
                            $formattedPlayers = $players->map(function ($player) use ($match) {
                                $pArray = $player->toArray();

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

                    // Guardamos en la estructura final
                    $finalFixture[$phase][$roundNumber][] = $matchData;
                }
            }
        }

        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->get();

        $documentTypes = DB::table('identity_document_type')->whereNotIn('id',['6'])->get();

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
     * Show the form for creating a new resource.
     */
    public function editionFixturesCreate($id)
    {
        $edition = EventEdition::find($id);
        $teams = EventTeam::where('status', true)->get();
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
        $request->validate([
            'teams' => 'required|array|min:2',
            'format' => 'required|string',
        ], [
            'teams.required' => 'No se han seleccionado equipos para generar el torneo.',
            'teams.min' => 'Necesitas al menos 2 equipos.'
        ]);
        $service = new TournamentService();
        // Si pasa la validación, entonces llamas a tu método
        $service->generate($request->get('edition_id'), $request->get('format'), $request->get('teams'));
    }

    /**
     * Show the specified resource.
     */
    public function editionFixturesStore(Request $request)
    {
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

        // 3. Registro con Eloquent
        EventEditionMatch::create([
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
    }

    /**
     * Update the specified resource in storage.
     */
    public function editionFixturesUpdate(Request $request, $id)
    {
        $request->validate([
            'round_number' => 'required|max:2',
            //'group_name' => 'required|string',
            'match_date' => 'required|string',
            //'location' => 'required|string',
        ]);

        EventEditionMatch::find($id)
            ->update([
                'round_number' => $request->get('round_number'),
                'group_name' => $request->get('group_name') ?? null,
                'match_date' => $request->get('match_date'),
                'location' => $request->get('location') ?? null,
                'status' => $request->get('status') ?? 'pending'
            ]);
    }

    public function editionMatchScoreStore(Request $request){
        //dd($request->all());
        DB::transaction(function () use ($request) {
            $matchId = $request->get('id');
            $edition_id = $request->get('edition_id');
            $price_red = $request->get('price_red');
            $double_yellow = $request->get('double_yellow');
            $yellow = $request->get('yellow');

            EventEditionMatch::find($matchId)->update([
                'score_h' => $request->get('score_h'),
                'score_a' => $request->get('score_a'),
                'status' => 'finished',
                'original_score' => $request->get('score_h'). '-'.$request->get('score_a')
            ]);

            $playersH = $request->get('players_h');
            $playersA = $request->get('players_a');

            $this->savePlayerCards($matchId, $playersH, $price_red, $double_yellow, $yellow);
            $this->savePlayerCards($matchId, $playersA, $price_red, $double_yellow, $yellow);
            // 2. Procesar estadísticas Local
            $this->savePlayerStats($matchId, $playersH, $request->get('equipo_h_id'), $request->get('score_a') == 0);

            // 3. Procesar estadísticas Visitante
            $this->savePlayerStats($matchId, $playersA, $request->get('equipo_a_id'), $request->get('score_h') == 0);

            // 4. registrae lo jugadores partisipantes
            $this->saveMatchPlayerParticipated($playersH, $playersA,  $matchId);

            // Al final de la transacción, recalculamos la tabla
            // se suman al crear el acta
            $this->positionService->updateTablePositions($match->edition_id);
        });
    }

    private function savePlayerCards($matchId, $players, $price_red = 0, $double_yellow = 0, $yellow = 0)
    {

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

    public function savePlayerStats($matchId, $players, $teamId, $isCleanSheet)
    {
        if (!$players) return;

        foreach ($players as $p) {
            if (isset($p['match_role']) && $p['match_role'] !== null) {
                EventEditionMatchPlayerStat::updateOrCreate(
                    [
                        'match_id'  => $matchId,
                        'player_id' => $p['person_id'], // Usamos el ID de la persona
                    ],
                    [
                        'team_id'     => $teamId,
                        'goals'       => $p['goals'] ?? 0,
                        'assists'     => $p['assists'] ?? 0,
                        'saves'       => $p['saves'] ?? 0,
                        'clean_sheet' => $isCleanSheet ? 1 : 0,
                        'minutes_played' => $p['minutes_played'] ?? 0,
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
        $message = null;
        $success = false;
        try {
            // Usamos una transacción para asegurarnos de que la operación se realice de manera segura.
            DB::beginTransaction();

            // Verificamos si existe.
            $item = EventEditionMatch::findOrFail($id);

            // Si no hay detalles asociados, eliminamos.
            $item->delete();

            // Si todo ha sido exitoso, confirmamos la transacción.
            DB::commit();

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
        $request->validate([
            'match_id' => 'required|exists:event_edition_matches,id',
            'equipo_h_id' => 'required',
            'equipo_a_id' => 'required',
        ]);

        DB::transaction(function () use ($request) {
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
}
