<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;

class TeamApiController extends Controller
{
    /**
     * Obtiene el equipo del usuario actual (si es delegado)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyTeam(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $personId = $user->person_id;

        if (!$personId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        $team = EventTeam::where('manager_id', $personId)->first();

        if (!$team) {
            return response()->json([
                'message' => 'No tienes un equipo asignado como delegado',
                'has_team' => false,
            ], 200);
        }

        // Obtener edición específica o usar la actual
        $editionId = $request->input('edition_id');
        $currentEdition = null;

        if ($editionId) {
            $currentEdition = EventEdition::find($editionId);
        }

        if (!$currentEdition) {
            $currentEdition = EventEdition::whereNotIn('status', ['CA'])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $editionTeam = null;
        if ($currentEdition) {
            $editionTeam = EventEditionTeam::where('edition_id', $currentEdition->id)
                ->where('team_id', $team->id)
                ->first();
        }

        $logoUrl = null;
        if ($team->logo_path) {
            $logoUrl = asset('storage/' . $team->logo_path);
        }

        return response()->json([
            'has_team' => true,
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'ubigeo' => $team->ubigeo,
                'ubigeo_description' => $team->ubigeo_description,
                'logo_path' => $team->logo_path,
                'logo_url' => $logoUrl,
                'manager_id' => $team->manager_id,
                'status' => $team->status,
            ],
            'edition_team' => $editionTeam ? [
                'edition_id' => $editionTeam->edition_id,
                'edition_name' => $currentEdition->name ?? null,
                'edition_year' => $currentEdition->year ?? null,
                'rank' => $editionTeam->rank,
                'points' => $editionTeam->points,
                'matches_played' => $editionTeam->matches_played,
                'matches_won' => $editionTeam->matches_won,
                'matches_drawn' => $editionTeam->matches_drawn,
                'matches_lost' => $editionTeam->matches_lost,
                'goals_for' => $editionTeam->goals_for,
                'goals_against' => $editionTeam->goals_against,
                'goal_difference' => $editionTeam->goal_difference,
            ] : null,
            'edition' => $currentEdition ? [
                'id' => $currentEdition->id,
                'name' => $currentEdition->name,
                'year' => $currentEdition->year,
            ] : null,
        ], 200);
    }

    /**
     * Actualiza la información del equipo del usuario
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMyTeam(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $personId = $user->person_id;

        if (!$personId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        $team = EventTeam::where('manager_id', $personId)->first();

        if (!$team) {
            return response()->json([
                'error' => 'No tienes un equipo asignado como delegado'
            ], 400);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'short_name' => 'sometimes|string|max:10',
            'ubigeo_description' => 'sometimes|string|max:255',
        ]);

        $team->update($request->only([
            'name',
            'short_name',
            'ubigeo_description',
        ]));

        $logoUrl = null;
        if ($team->logo_path) {
            $logoUrl = asset('storage/' . $team->logo_path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Equipo actualizado correctamente',
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'ubigeo' => $team->ubigeo,
                'ubigeo_description' => $team->ubigeo_description,
                'logo_path' => $team->logo_path,
                'logo_url' => $logoUrl,
                'manager_id' => $team->manager_id,
                'status' => $team->status,
            ],
        ], 200);
    }

    /**
     * Verifica si el usuario tiene un equipo asignado
     *
     * @return JsonResponse
     */
    public function checkMyTeam(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'has_team' => false,
                'is_delegate' => false,
            ], 200);
        }

        $personId = $user->person_id;

        if (!$personId) {
            return response()->json([
                'has_team' => false,
                'is_delegate' => false,
            ], 200);
        }

        $team = EventTeam::where('manager_id', $personId)->first();

        return response()->json([
            'has_team' => $team !== null,
            'is_delegate' => $team !== null,
            'team_id' => $team?->id,
        ], 200);
    }

    /**
     * Sube la imagen del equipo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadTeamLogo(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $personId = $user->person_id;

        if (!$personId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        $team = EventTeam::where('manager_id', $personId)->first();

        if (!$team) {
            return response()->json([
                'error' => 'No tienes un equipo asignado como delegado'
            ], 400);
        }

        $logoPath = $team->logo_path;
        $base64Image = $request->input('logo_path');

        if ($base64Image) {
            $destination = 'uploads/eventos/equipos';

            try {
                if (!Storage::exists($destination)) {
                    Storage::makeDirectory($destination, 0755, true);
                }

                $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));

                $tempFile = tempnam(sys_get_temp_dir(), 'img');
                file_put_contents($tempFile, $fileData);
                $mime = mime_content_type($tempFile);

                $extension = str_replace('image/', '', $mime);
                $fileName = $team->id . '.' . $extension;

                $logoPath = Storage::disk('public')->putFileAs($destination, new \Illuminate\Http\File($tempFile), $fileName);

                unlink($tempFile);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Error al subir la imagen: ' . $e->getMessage()
                ], 500);
            }
        }

        $team->logo_path = $logoPath;
        $team->save();

        $logoUrl = $team->logo_path ? asset('storage/' . $team->logo_path) : null;

        return response()->json([
            'success' => true,
            'message' => 'Logo actualizado correctamente',
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'logo_path' => $team->logo_path,
                'logo_url' => $logoUrl,
            ],
        ], 200);
    }

    /**
     * Obtiene el historial de partidos del equipo del usuario
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeamMatches(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $personId = $user->person_id;

        if (!$personId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        $team = EventTeam::where('manager_id', $personId)->first();

        if (!$team) {
            return response()->json([
                'message' => 'No tienes un equipo asignado como delegado',
                'has_team' => false,
                'matches' => [],
            ], 200);
        }

        $editionId = $request->input('edition_id');

        $currentEdition = null;
        $editions = collect([]);

        if ($editionId) {
            $currentEdition = EventEdition::find($editionId);
        }

        if (!$currentEdition) {
            $currentEdition = EventEdition::whereNotIn('status', ['CA'])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // Obtener todas las ediciones disponibles para el equipo
        $teamEditions = EventEditionTeam::where('team_id', $team->id)
            ->with('edition')
            ->get()
            ->pluck('edition')
            ->filter()
            ->unique('id')
            ->values();

        foreach ($teamEditions as $edition) {
            $editions->push([
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
            ]);
        }

        if (!$currentEdition) {
            return response()->json([
                'team_id' => $team->id,
                'team_name' => $team->name,
                'edition_id' => null,
                'edition_name' => null,
                'editions' => $editions,
                'matches' => [],
            ], 200);
        }

        // Obtener partidos donde el equipo participó como local o visitante
        $matches = EventEditionMatch::where('edition_id', $currentEdition->id)
            ->where(function ($query) use ($team) {
                $query->where('team_h_id', $team->id)
                    ->orWhere('team_a_id', $team->id);
            })
            ->where('status', 'closed')
            ->orderBy('match_date', 'desc')
            ->get()
            ->map(function ($match) use ($team) {
                $isHome = $match->team_h_id == $team->id;
                $opponentId = $isHome ? $match->team_a_id : $match->team_h_id;

                $opponent = EventTeam::find($opponentId);
                $opponentLogo = $opponent && $opponent->logo_path
                    ? asset('storage/' . $opponent->logo_path)
                    : null;

                $goalsFor = $isHome ? $match->score_h : $match->score_a;
                $goalsAgainst = $isHome ? $match->score_a : $match->score_h;

                $outcome = $goalsFor > $goalsAgainst ? 'win' : ($goalsFor == $goalsAgainst ? 'draw' : 'loss');

                // Obtener estadísticas de jugadores del equipo en este partido
                $stats = EventEditionMatchPlayerStat::where('match_id', $match->id)
                    ->where('team_id', $team->id)
                    ->get()
                    ->map(function ($stat) {
                        $person = Person::find($stat->player_id);
                        return [
                            'player_id' => $stat->player_id,
                            'player_name' => $person ? ($person->father_lastname . ' ' . $person->mother_lastname . ' ' . $person->names) : 'Jugador',
                            'goals' => $stat->goals,
                            'assists' => $stat->assists,
                            'is_mvp' => (bool) $stat->is_mvp,
                        ];
                    })
                    ->filter(function ($stat) {
                        return $stat['goals'] > 0 || $stat['assists'] > 0 || $stat['is_mvp'];
                    })
                    ->values();

                return [
                    'id' => $match->id,
                    'match_date' => $match->match_date ? $match->match_date->format('Y-m-d H:i:s') : null,
                    'phase' => $match->phase,
                    'round' => $match->round_number,
                    'location' => $match->location,
                    'is_home' => $isHome,
                    'opponent' => [
                        'id' => $opponent->id ?? $opponentId,
                        'name' => $opponent->name ?? 'Equipo',
                        'short_name' => $opponent->short_name ?? '',
                        'logo_url' => $opponentLogo,
                    ],
                    'result' => [
                        'goals_for' => $goalsFor,
                        'goals_against' => $goalsAgainst,
                        'outcome' => $outcome,
                    ],
                    'status' => $match->status,
                    'highlights' => $stats,
                ];
            });

        return response()->json([
            'team_id' => $team->id,
            'team_name' => $team->name,
            'edition_id' => $currentEdition->id,
            'edition_name' => $currentEdition->name . ' - ' . $currentEdition->year,
            'editions' => $editions,
            'matches' => $matches,
        ], 200);
    }

    /**
     * Obtiene las sanciones de los jugadores del equipo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeamSanctions(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $personId = $user->person_id;

        if (!$personId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        $team = EventTeam::where('manager_id', $personId)->first();

        if (!$team) {
            return response()->json([
                'message' => 'No tienes un equipo asignado como delegado',
                'has_team' => false,
                'sanctions' => [],
            ], 200);
        }

        $editionId = $request->input('edition_id');

        $currentEdition = null;
        $editions = collect([]);

        if ($editionId) {
            $currentEdition = EventEdition::find($editionId);
        }

        if (!$currentEdition) {
            $currentEdition = EventEdition::whereNotIn('status', ['CA'])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // Obtener todas las ediciones disponibles para el equipo
        $teamEditions = EventEditionTeam::where('team_id', $team->id)
            ->with('edition')
            ->get()
            ->pluck('edition')
            ->filter()
            ->unique('id')
            ->values();

        foreach ($teamEditions as $edition) {
            $editions->push([
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
            ]);
        }

        if (!$currentEdition) {
            return response()->json([
                'team_id' => $team->id,
                'team_name' => $team->name,
                'edition_id' => null,
                'edition_name' => null,
                'editions' => $editions,
                'sanctions' => [],
                'summary' => [
                    'total_players' => 0,
                    'total_sanctions' => 0,
                    'pending_amount' => 0,
                    'paid_amount' => 0,
                ],
            ], 200);
        }

        // Obtener jugadores del equipo en esta edición
        $teamPlayers = EventEditionTeamPlayer::where('edition_id', $currentEdition->id)
            ->where('team_id', $team->id)
            ->get()
            ->pluck('person_id')
            ->toArray();

        if (empty($teamPlayers)) {
            return response()->json([
                'team_id' => $team->id,
                'team_name' => $team->name,
                'edition_id' => $currentEdition->id,
                'edition_name' => $currentEdition->name . ' - ' . $currentEdition->year,
                'editions' => $editions,
                'sanctions' => [],
                'summary' => [
                    'total_players' => 0,
                    'total_sanctions' => 0,
                    'pending_amount' => 0,
                    'paid_amount' => 0,
                ],
            ], 200);
        }

        // Obtener las sanciones de los jugadores del equipo
        $sanctions = EventEditionMatchSanction::whereIn('player_id', $teamPlayers)
            ->with(['match' => function ($query) use ($currentEdition) {
                $query->where('edition_id', $currentEdition->id);
            }])
            ->get()
            ->filter(function ($sanction) {
                return $sanction->match !== null;
            })
            ->groupBy('player_id')
            ->map(function ($playerSanctions) use ($team) {
                $firstSanction = $playerSanctions->first();
                $person = Person::find($firstSanction->player_id);

                $matchIds = $playerSanctions->pluck('match_id')->unique()->toArray();
                $matches = EventEditionMatch::whereIn('id', $matchIds)->get();

                $sanctionsList = $playerSanctions->map(function ($sanction) use ($matches, $team) {
                    $match = $matches->firstWhere('id', $sanction->match_id);
                    $opponentId = $match->team_h_id == $team->id ? $match->team_a_id : $match->team_h_id;
                    $opponent = EventTeam::find($opponentId);

                    return [
                        'id' => $sanction->id,
                        'type' => $sanction->type,
                        'minute' => $sanction->minute,
                        'amount_fine' => (float) $sanction->amount_fine,
                        'is_paid' => (bool) $sanction->is_paid,
                        'paid_at' => $sanction->paid_at ? $sanction->paid_at->format('Y-m-d H:i:s') : null,
                        'match' => [
                            'id' => $match->id,
                            'date' => $match->match_date ? $match->match_date->format('Y-m-d') : null,
                            'opponent' => $opponent ? $opponent->name : 'Equipo',
                        ],
                    ];
                });

                // Calcular total pendiente y pagado
                $pendingAmount = $playerSanctions->where('is_paid', false)->sum('amount_fine');
                $paidAmount = $playerSanctions->where('is_paid', true)->sum('amount_fine');

                return [
                    'player' => [
                        'id' => $person->id ?? $firstSanction->player_id,
                        'full_name' => $person ? ($person->father_lastname . ' ' . $person->mother_lastname . ' ' . $person->names) : 'Jugador',
                        'dni' => $person->number ?? '',
                        'image_url' => $person && $person->image ? asset('storage/' . $person->image) : null,
                    ],
                    'sanctions' => $sanctionsList,
                    'total_pending' => (float) $pendingAmount,
                    'total_paid' => (float) $paidAmount,
                    'total_amount' => (float) ($pendingAmount + $paidAmount),
                ];
            })
            ->values();

        // Calcular resumen
        $totalPlayers = $sanctions->count();
        $totalSanctions = $sanctions->sum(function ($player) {
            return count($player['sanctions']);
        });
        $pendingAmount = $sanctions->sum('total_pending');
        $paidAmount = $sanctions->sum('total_paid');

        return response()->json([
            'team_id' => $team->id,
            'team_name' => $team->name,
            'edition_id' => $currentEdition->id,
            'edition_name' => $currentEdition->name . ' - ' . $currentEdition->year,
            'editions' => $editions,
            'sanctions' => $sanctions,
            'summary' => [
                'total_players' => $totalPlayers,
                'total_sanctions' => $totalSanctions,
                'pending_amount' => (float) $pendingAmount,
                'paid_amount' => (float) $paidAmount,
            ],
        ], 200);
    }
}
