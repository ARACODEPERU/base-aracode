<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;

class PlayerApiController extends Controller
{
    /**
     * Obtiene los jugadores del equipo del usuario en la edición actual
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayers(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $personId = $user->person_id;

        if (!$personId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        // Obtener el equipo del usuario
        $team = EventTeam::where('manager_id', $personId)->first();

        if (!$team) {
            return response()->json([
                'message' => 'No tienes un equipo asignado como delegado',
                'has_team' => false,
                'players' => [],
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

        if (!$currentEdition) {
            return response()->json([
                'error' => 'No hay una edición activa del torneo'
            ], 400);
        }

        // Obtener jugadores del equipo en la edición
        $players = EventEditionTeamPlayer::with('person')
            ->where('edition_id', $currentEdition->id)
            ->where('team_id', $team->id)
            ->get()
            ->map(function ($player) {
                $person = $player->person;
                return [
                    'id' => $player->id,
                    'person_id' => $player->person_id,
                    'team_id' => $player->team_id,
                    'edition_id' => $player->edition_id,
                    'full_name' => $person->full_name ?? '',
                    'father_lastname' => $person->father_lastname ?? '',
                    'mother_lastname' => $person->mother_lastname ?? '',
                    'names' => $person->names ?? '',
                    'dni' => $person->number ?? '',
                    'gender' => $person->gender ?? '',
                    'image_url' => $person->image ? asset('storage/' . $person->image) : null,
                    'jersey_number' => $player->jersey_number ?? '',
                    'position' => $player->position ?? '',
                    'role_in_team' => $player->role_in_team ?? '',
                    'registration_date' => $player->registration_date,
                ];
            });

        return response()->json([
            'has_team' => true,
            'team_id' => $team->id,
            'team_name' => $team->name,
            'edition_id' => $currentEdition->id,
            'edition_name' => $currentEdition->name,
            'players' => $players,
        ], 200);
    }

    /**
     * Crea un nuevo jugador para el equipo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createPlayer(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $personId = $user->person_id;

        if (!$personId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        // Obtener el equipo del usuario
        $team = EventTeam::where('manager_id', $personId)->first();

        if (!$team) {
            return response()->json([
                'error' => 'No tienes un equipo asignado como delegado'
            ], 400);
        }

        // Validar datos
        $request->validate([
            'father_lastname' => 'required|string|max:255',
            'mother_lastname' => 'required|string|max:255',
            'names' => 'required|string|max:255',
            'dni' => 'required|string|max:20|unique:people,number',
            'gender' => 'required|in:M,F',
            'jersey_number' => 'nullable|string|max:10',
            'position' => 'nullable|string|max:100',
            'role_in_team' => 'nullable|in:Ninguno,Capitán,Sub-Capitán',
            'image' => 'nullable|string',
        ], [
            'dni.unique' => 'El DNI ya está registrado en el sistema',
        ]);

        // Obtener edición actual
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

        if (!$currentEdition) {
            return response()->json([
                'error' => 'No hay una edición activa del torneo'
            ], 400);
        }

        // Verificar que el DNI no esté en otro equipo de la misma edición
        $existingPlayer = EventEditionTeamPlayer::where('edition_id', $currentEdition->id)
            ->whereHas('person', function ($query) use ($request) {
                $query->where('number', $request->dni);
            })
            ->first();

        if ($existingPlayer) {
            return response()->json([
                'error' => 'El jugador con este DNI ya está registrado en otro equipo para esta edición'
            ], 400);
        }

        // Crear persona
        $person = Person::create([
            'full_name' => trim($request->father_lastname . ' ' .  $request->mother_lastname . ' ' . $request->names),
            'document_type_id' => '1', // DNI
            'number' => trim($request->dni),
            'father_lastname' => trim($request->father_lastname),
            'mother_lastname' => trim($request->mother_lastname),
            'names' => trim($request->names),
            'gender' => $request->gender,
            'status' => '1',
        ]);

        // Procesar imagen si existe
        $imagePath = null;
        if ($request->has('image') && $request->image) {
            $imagePath = $this->savePlayerImage($request->image, $person->id);
            if ($imagePath) {
                $person->update(['image' => $imagePath]);
            }
        }

        // Crear registro de jugador en la edición
        EventEditionTeamPlayer::create([
            'edition_id' => $currentEdition->id,
            'team_id' => $team->id,
            'person_id' => $person->id,
            'jersey_number' => $request->jersey_number ?? '',
            'position' => $request->position ?? '',
            'role_in_team' => $request->role_in_team ?? 'Ninguno',
            'registration_date' => now()->format('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jugador registrado correctamente',
            'player' => [
                'id' => $person->id,
                'person_id' => $person->id,
                'father_lastname' => $person->father_lastname,
                'mother_lastname' => $person->mother_lastname,
                'names' => $person->names,
                'dni' => $person->number,
                'gender' => $person->gender,
                'image_url' => $person->image ? asset('storage/' . $person->image) : null,
                'jersey_number' => $request->jersey_number ?? '',
                'position' => $request->position ?? '',
                'role_in_team' => $request->role_in_team ?? 'Ninguno',
            ],
        ], 201);
    }

    /**
     * Actualiza un jugador existente
     *
     * @param Request $request
     * @param int $personId
     * @return JsonResponse
     */
    public function updatePlayer(Request $request, int $personId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $managerId = $user->person_id;

        if (!$managerId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        // Obtener el equipo del usuario
        $team = EventTeam::where('manager_id', $managerId)->first();

        if (!$team) {
            return response()->json([
                'error' => 'No tienes un equipo asignado como delegado'
            ], 400);
        }

        // Obtener edición actual
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

        if (!$currentEdition) {
            return response()->json([
                'error' => 'No hay una edición activa del torneo'
            ], 400);
        }

        // Verificar que el jugador pertenezca al equipo del usuario
        $player = EventEditionTeamPlayer::where('edition_id', $currentEdition->id)
            ->where('team_id', $team->id)
            ->where('person_id', $personId)
            ->first();

        if (!$player) {
            return response()->json([
                'error' => 'El jugador no pertenece a tu equipo'
            ], 400);
        }

        // Validar datos
        $request->validate([
            'father_lastname' => 'sometimes|string|max:255',
            'mother_lastname' => 'sometimes|string|max:255',
            'names' => 'sometimes|string|max:255',
            'dni' => 'sometimes|string|max:20|unique:people,number,' . $personId,
            'gender' => 'sometimes|in:M,F',
            'jersey_number' => 'nullable|string|max:10',
            'position' => 'nullable|string|max:100',
            'role_in_team' => 'nullable|in:Ninguno,Capitán,Sub-Capitán',
        ], [
            'dni.unique' => 'El DNI ya está registrado en el sistema',
        ]);

        // Actualizar persona
        $person = Person::find($personId);

        $personData = $request->only([
            'father_lastname',
            'mother_lastname',
            'names',
            'dni',
            'gender',
        ]);

        // Mapear dni a number
        if (isset($personData['dni'])) {
            $personData['number'] = $personData['dni'];
            unset($personData['dni']);
        }

        $personData['full_name'] = trim($request->father_lastname . ' ' .  $request->mother_lastname . ' ' . $request->names);

        $person->update($personData);

        // Actualizar datos del jugador
        $player->update([
            'jersey_number' => $request->jersey_number ?? '',
            'position' => $request->position ?? '',
            'role_in_team' => $request->role_in_team ?? 'Ninguno',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jugador actualizado correctamente',
            'player' => [
                'id' => $player->id,
                'person_id' => $person->id,
                'father_lastname' => $person->father_lastname,
                'mother_lastname' => $person->mother_lastname,
                'names' => $person->names,
                'dni' => $person->number,
                'gender' => $person->gender,
                'image_url' => $person->image ? asset('storage/' . $person->image) : null,
                'jersey_number' => $player->jersey_number,
                'position' => $player->position,
                'role_in_team' => $player->role_in_team,
            ],
        ], 200);
    }

    /**
     * Elimina un jugador del equipo
     *
     * @param int $personId
     * @return JsonResponse
     */
    public function deletePlayer(int $personId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $managerId = $user->person_id;

        if (!$managerId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        // Obtener el equipo del usuario
        $team = EventTeam::where('manager_id', $managerId)->first();

        if (!$team) {
            return response()->json([
                'error' => 'No tienes un equipo asignado como delegado'
            ], 400);
        }

        // Obtener edición actual
        $currentEdition = EventEdition::whereNotIn('status', ['CA'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$currentEdition) {
            return response()->json([
                'error' => 'No hay una edición activa del torneo'
            ], 400);
        }

        // Verificar que el jugador pertenezca al equipo del usuario
        $player = EventEditionTeamPlayer::where('edition_id', $currentEdition->id)
            ->where('team_id', $team->id)
            ->where('person_id', $personId)
            ->first();

        if (!$player) {
            return response()->json([
                'error' => 'El jugador no pertenece a tu equipo'
            ], 400);
        }

        // Eliminar registro de jugador
        $player->delete();

        // Opcional: también eliminar la persona (comentado por si se necesita después)
        // Person::destroy($personId);

        return response()->json([
            'success' => true,
            'message' => 'Jugador eliminado correctamente',
        ], 200);
    }

    /**
     * Sube la foto de un jugador
     *
     * @param Request $request
     * @param int $personId
     * @return JsonResponse
     */
    public function uploadPlayerPhoto(Request $request, int $personId): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $managerId = $user->person_id;

        if (!$managerId) {
            return response()->json(['error' => 'Usuario no tiene persona asociada'], 400);
        }

        // Obtener el equipo del usuario
        $team = EventTeam::where('manager_id', $managerId)->first();

        if (!$team) {
            return response()->json([
                'error' => 'No tienes un equipo asignado como delegado'
            ], 400);
        }

        // Obtener edición actual
        $currentEdition = EventEdition::whereNotIn('status', ['CA'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$currentEdition) {
            return response()->json([
                'error' => 'No hay una edición activa del torneo'
            ], 400);
        }

        // Verificar que el jugador pertenezca al equipo del usuario
        $player = EventEditionTeamPlayer::where('edition_id', $currentEdition->id)
            ->where('team_id', $team->id)
            ->where('person_id', $personId)
            ->first();

        if (!$player) {
            return response()->json([
                'error' => 'El jugador no pertenece a tu equipo'
            ], 400);
        }

        // Validar imagen
        $request->validate([
            'image' => 'required|string',
        ], [
            'image.required' => 'La imagen es obligatoria.',
        ]);

        // Guardar imagen
        $person = Person::find($personId);
        $imagePath = $this->savePlayerImage($request->image, $personId);

        if ($imagePath) {
            // Eliminar imagen anterior si existe
            if ($person->image && Storage::disk('public')->exists($person->image)) {
                Storage::disk('public')->delete($person->image);
            }

            $person->update(['image' => $imagePath]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Foto del jugador actualizada correctamente',
            'image_url' => $person->image ? asset('storage/' . $person->image) : null,
        ], 200);
    }

    /**
     * Guarda la imagen del jugador
     *
     * @param string $base64Image
     * @param int $personId
     * @return string|null
     */
    private function savePlayerImage(string $base64Image, int $personId): ?string
    {
        try {
            $destination = 'uploads/eventos/jugadores';

            if (!Storage::disk('public')->exists($destination)) {
                Storage::disk('public')->makeDirectory($destination, 0755, true);
            }

            $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));

            $tempFile = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tempFile, $fileData);
            $mime = mime_content_type($tempFile);

            $extension = str_replace('image/', '', $mime);
            $fileName = $personId . '_' . time() . '.' . $extension;

            $path = Storage::disk('public')->putFileAs($destination, new \Illuminate\Http\File($tempFile), $fileName);

            unlink($tempFile);

            return $path;
        } catch (\Exception $e) {
            return null;
        }
    }
}
