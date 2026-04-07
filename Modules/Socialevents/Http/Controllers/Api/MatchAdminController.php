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
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Services\PositionTableService;

class MatchAdminController extends Controller
{
    private $positionService;

    public function __construct()
    {
        $this->positionService = new PositionTableService();
    }

    public function index(Request $request, int $editionId): JsonResponse
    {
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
        $validated = $request->validate([
            'status' => 'required|in:pending,live,finished,closed,walk_over,cancelled',
            'match_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'team_h_id' => 'nullable|integer',
            'team_a_id' => 'nullable|integer',
            'phase' => 'required|in:league,groups,round_16,quarter,semi,final,third_place',
            'group_name' => 'nullable|string|max:255',
            'round_number' => 'nullable|integer|min:1',
            'score_h' => 'nullable|integer|min:0',
            'score_a' => 'nullable|integer|min:0',
        ]);

        if ($request->team_h_id && $request->team_a_id && $request->team_h_id == $request->team_a_id) {
            return response()->json([
                'success' => false,
                'message' => 'El equipo local y visitante no pueden ser el mismo'
            ], 422);
        }

        $match = EventEditionMatch::create([
            'edition_id' => $editionId,
            'status' => $validated['status'],
            'match_date' => $validated['match_date'] ?? null,
            'location' => $validated['location'] ?? null,
            'team_h_id' => $validated['team_h_id'] ?? null,
            'team_a_id' => $validated['team_a_id'] ?? null,
            'phase' => $validated['phase'],
            'group_name' => $validated['group_name'] ?? null,
            'round_number' => $validated['round_number'] ?? null,
            'score_h' => $validated['score_h'] ?? null,
            'score_a' => $validated['score_a'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Partido creado correctamente',
            'data' => $this->formatMatch($match->fresh(['equipolocal', 'equipovisitante']))
        ], 201);
    }

    public function update(Request $request, int $match): JsonResponse
    {
        $match = EventEditionMatch::with(['equipolocal', 'equipovisitante'])->findOrFail($match);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,live,finished,closed,walk_over,cancelled',
            'match_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'team_h_id' => 'nullable|integer',
            'team_a_id' => 'nullable|integer',
            'phase' => 'sometimes|in:league,groups,round_16,quarter,semi,final,third_place',
            'group_name' => 'nullable|string|max:255',
            'round_number' => 'nullable|integer|min:1',
            'score_h' => 'nullable|integer|min:0',
            'score_a' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['team_h_id']) && isset($validated['team_a_id']) &&
            $validated['team_h_id'] == $validated['team_a_id']) {
            return response()->json([
                'success' => false,
                'message' => 'El equipo local y visitante no pueden ser el mismo'
            ], 422);
        }

        $match->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Partido actualizado correctamente',
            'data' => $this->formatMatch($match->fresh(['equipolocal', 'equipovisitante']))
        ]);
    }

    public function destroy(int $match): JsonResponse
    {
        $match = EventEditionMatch::findOrFail($match);

        if ($match->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden eliminar partidos en estado pendiente'
            ], 422);
        }

        $match->delete();

        return response()->json([
            'success' => true,
            'message' => 'Partido eliminado correctamente'
        ]);
    }

    public function teams(int $editionId): JsonResponse
    {
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

    public function locations(): JsonResponse
    {
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
            'group_name' => $match->group_name,
            'round_number' => $match->round_number,
            'is_editable' => in_array($match->status, ['pending']),
        ];
    }

    public function getMatchPlayers(int $matchId): JsonResponse
    {
        $match = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->findOrFail($matchId);

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

                $players[$teamType === 'equipolocal' ? 'home' : 'away'] = $teamPlayers->map(function ($player) use ($match) {
                    $p = [
                        'id' => $player->id,
                        'person_id' => $player->person_id,
                        'team_id' => $player->team_id,
                        'jersey_number' => $player->jersey_number,
                        'position' => $player->position,
                        'name' => $player->person ? $player->person->full_name : 'Jugador',
                        'stats' => [],
                        'sanctions' => [],
                        'participation' => null,
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
            ]
        ]);
    }

    public function saveMatchResult(Request $request, int $matchId): JsonResponse
    {
        $match = EventEditionMatch::findOrFail($matchId);

        $validated = $request->validate([
            'score_h' => 'required|integer|min:0',
            'score_a' => 'required|integer|min:0',
            'edition_id' => 'required|integer',
            'players_h' => 'nullable|array',
            'players_a' => 'nullable|array',
        ]);

        DB::transaction(function () use ($match, $validated) {
            $match->update([
                'score_h' => $validated['score_h'],
                'score_a' => $validated['score_a'],
                'status' => 'finished',
                'original_score' => $validated['score_h'] . '-' . $validated['score_a'],
            ]);

            $teamHId = $match->team_h_id;
            $teamAId = $match->team_a_id;

            if (isset($validated['players_h'])) {
                $this->savePlayerCards($match->id, $validated['players_h']);
                $this->savePlayerStats($match->id, $validated['players_h'], $teamHId, $validated['score_a'] == 0);
                $this->saveMatchPlayerParticipated($validated['players_h'], $match->id);
            }

            if (isset($validated['players_a'])) {
                $this->savePlayerCards($match->id, $validated['players_a']);
                $this->savePlayerStats($match->id, $validated['players_a'], $teamAId, $validated['score_h'] == 0);
                $this->saveMatchPlayerParticipated($validated['players_a'], $match->id);
            }

            // Recalcular tabla de posiciones
            $this->positionService->updateTablePositions($validated['edition_id']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Resultado guardado correctamente',
            'data' => $this->formatMatch($match->fresh(['equipolocal', 'equipovisitante']))
        ]);
    }

    private function savePlayerCards($matchId, $players)
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
                ]);
            }

            if ($yellowCount === 2 || $isDoubleYellow) {
                EventEditionMatchSanction::create([
                    'match_id' => $matchId,
                    'player_id' => $p['person_id'],
                    'type' => 'double_yellow',
                ]);
                for ($i = 1; $i <= 2; $i++) {
                    EventEditionMatchSanction::create([
                        'match_id' => $matchId,
                        'player_id' => $p['person_id'],
                        'type' => 'yellow',
                    ]);
                }
            } elseif ($yellowCount === 1) {
                EventEditionMatchSanction::create([
                    'match_id' => $matchId,
                    'player_id' => $p['person_id'],
                    'type' => 'yellow',
                ]);
            }
        }
    }

    private function savePlayerStats($matchId, $players, $teamId, $isCleanSheet)
    {
        if (!$players) return;

        foreach ($players as $p) {
            if (!isset($p['person_id']) || !isset($p['match_role'])) continue;

            EventEditionMatchPlayerStat::updateOrCreate(
                ['match_id' => $matchId, 'player_id' => $p['person_id']],
                [
                    'team_id' => $teamId,
                    'goals' => $p['goals'] ?? 0,
                    'assists' => $p['assists'] ?? 0,
                    'saves' => $p['saves'] ?? 0,
                    'clean_sheet' => $isCleanSheet ? 1 : 0,
                    'minutes_played' => $p['minutes_played'] ?? 0,
                    'is_mvp' => $p['is_mvp'] ?? false,
                ]
            );
        }
    }

    private function saveMatchPlayerParticipated($players, $matchId)
    {
        foreach ($players as $p) {
            if (!isset($p['person_id']) || !isset($p['match_role'])) continue;

            EventEditionMatchParticipation::updateOrCreate(
                ['match_id' => $matchId, 'player_id' => $p['person_id']],
                ['role' => $p['match_role']]
            );
        }
    }

    public function closeMatchReport(Request $request, int $matchId): JsonResponse
    {
        $request->validate([
            'observations' => 'nullable|string|max:5000',
            'local_has_protest' => 'nullable|boolean',
            'local_protest_details' => 'nullable|string|max:5000',
            'visitor_has_protest' => 'nullable|boolean',
            'visitor_protest_details' => 'nullable|string|max:5000',
        ]);

        $match = EventEditionMatch::with(['equipolocal', 'equipovisitante'])->find($matchId);

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
