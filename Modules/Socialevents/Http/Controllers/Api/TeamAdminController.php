<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Services\EditionTeamPlayerService;
use Modules\Socialevents\Services\EditionTeamService;
use Modules\Socialevents\Services\TeamRegistrationService;
use RuntimeException;

class TeamAdminController extends Controller
{
    public function __construct(
        private TeamRegistrationService $teamRegistration,
        private EditionTeamService $editionTeam,
        private EditionTeamPlayerService $editionTeamPlayer,
    ) {}

    public function catalog(): JsonResponse
    {
        $teams = EventTeam::with('manager')
            ->orderBy('name')
            ->get()
            ->map(fn (EventTeam $team) => $this->teamRegistration->formatTeam($team));

        return response()->json([
            'success' => true,
            'message' => 'Catálogo de equipos obtenido correctamente',
            'data' => $teams,
        ]);
    }

    public function storeTeam(Request $request): JsonResponse
    {
        try {
            $result = $this->teamRegistration->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Equipo registrado correctamente',
                'data' => $this->teamRegistration->formatTeam($result['team']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function editionTeams(int $editionId): JsonResponse
    {
        $edition = EventEdition::findOrFail($editionId);
        $teams = $this->editionTeam->listForEdition($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Equipos de la edición obtenidos correctamente',
            'data' => [
                'edition' => [
                    'id' => $edition->id,
                    'name' => $edition->name,
                    'year' => $edition->year,
                ],
                'teams' => $teams,
            ],
        ]);
    }

    public function assignTeam(Request $request, int $editionId): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => 'required|integer|exists:event_teams,id',
        ]);

        try {
            $row = $this->editionTeam->assign($editionId, (int) $validated['team_id']);
            $row->load('equipo');

            return response()->json([
                'success' => true,
                'message' => 'Equipo inscrito en la edición correctamente',
                'data' => $this->editionTeam->formatEditionTeam($row),
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function unassignTeam(int $editionId, int $teamId): JsonResponse
    {
        try {
            $this->editionTeam->unassign($editionId, $teamId);

            return response()->json([
                'success' => true,
                'message' => 'Equipo retirado de la edición correctamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function players(int $editionId, int $teamId): JsonResponse
    {
        try {
            $players = $this->editionTeamPlayer->listForTeam($editionId, $teamId);

            return response()->json([
                'success' => true,
                'message' => 'Jugadores obtenidos correctamente',
                'data' => $players,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function linkPlayer(Request $request, int $editionId, int $teamId): JsonResponse
    {
        $validated = $request->validate([
            'person_id' => 'required|integer|exists:people,id',
        ]);

        try {
            $player = $this->editionTeamPlayer->linkExisting(
                $editionId,
                $teamId,
                (int) $validated['person_id']
            );
            $player->load('person');

            return response()->json([
                'success' => true,
                'message' => 'Jugador vinculado al equipo correctamente',
                'data' => $this->editionTeamPlayer->formatPlayer($player),
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function createPlayer(Request $request, int $editionId, int $teamId): JsonResponse
    {
        try {
            $player = $this->editionTeamPlayer->createWithPerson($editionId, $teamId, $request->all());
            $player->load('person');

            return response()->json([
                'success' => true,
                'message' => 'Jugador registrado correctamente',
                'data' => $this->editionTeamPlayer->formatPlayer($player),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updatePlayer(Request $request, int $editionId, int $teamId, int $personId): JsonResponse
    {
        try {
            $player = $this->editionTeamPlayer->update($editionId, $teamId, $personId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Jugador actualizado correctamente',
                'data' => $this->editionTeamPlayer->formatPlayer($player),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function uploadPlayerPhoto(Request $request, int $editionId, int $teamId, int $personId): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|string',
        ]);

        try {
            $person = $this->editionTeamPlayer->uploadPhoto(
                $editionId,
                $teamId,
                $personId,
                $validated['image']
            );

            return response()->json([
                'success' => true,
                'message' => 'Foto del jugador actualizada correctamente',
                'data' => [
                    'image_url' => $person->image ? asset('storage/'.$person->image) : null,
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deletePlayer(int $editionId, int $teamId, int $personId): JsonResponse
    {
        try {
            $this->editionTeamPlayer->delete($editionId, $teamId, $personId);

            return response()->json([
                'success' => true,
                'message' => 'Jugador eliminado del equipo correctamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function searchPerson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type_id' => 'required|integer',
            'number' => 'required|string|max:20',
        ]);

        $person = $this->editionTeamPlayer->searchPersonByDocument(
            (int) $validated['document_type_id'],
            $validated['number']
        );

        if (! $person) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontró persona con ese documento',
                'data' => [
                    'found' => false,
                    'person' => null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Persona encontrada',
            'data' => [
                'found' => true,
                'person' => $this->editionTeamPlayer->formatPerson($person),
            ],
        ]);
    }

    public function savePerson(Request $request): JsonResponse
    {
        try {
            $person = $this->editionTeamPlayer->savePerson($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Persona guardada correctamente',
                'data' => $this->editionTeamPlayer->formatPerson($person),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function ubigeo(): JsonResponse
    {
        $ubigeo = District::join('provinces', 'province_id', 'provinces.id')
            ->join('departments', 'provinces.department_id', 'departments.id')
            ->select(
                'districts.id AS district_id',
                DB::raw("CONCAT(departments.name,'-',provinces.name,'-',districts.name) AS ubigeo_description")
            )
            ->orderBy('ubigeo_description')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Ubigeo obtenido correctamente',
            'data' => $ubigeo,
        ]);
    }
}
