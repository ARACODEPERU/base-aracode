<?php

namespace Modules\Socialevents\Services;

use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Support\SavesBase64Image;
use RuntimeException;

class EditionTeamPlayerService
{
    use SavesBase64Image;

    public function listForTeam(int $editionId, int $teamId): array
    {
        $this->assertTeamInEdition($editionId, $teamId);

        return EventEditionTeamPlayer::with('person')
            ->where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->get()
            ->map(fn (EventEditionTeamPlayer $player) => $this->formatPlayer($player))
            ->values()
            ->all();
    }

    public function linkExisting(int $editionId, int $teamId, int $personId): EventEditionTeamPlayer
    {
        $this->assertTeamInEdition($editionId, $teamId);
        Person::findOrFail($personId);

        $exists = EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('person_id', $personId)
            ->exists();

        if ($exists) {
            throw new RuntimeException('El jugador ya está registrado en otro equipo para esta edición.');
        }

        return EventEditionTeamPlayer::create([
            'edition_id' => $editionId,
            'team_id' => $teamId,
            'person_id' => $personId,
            'registration_date' => Carbon::now()->format('Y-m-d'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createWithPerson(int $editionId, int $teamId, array $data): EventEditionTeamPlayer
    {
        $this->assertTeamInEdition($editionId, $teamId);

        $validated = Validator::make($data, [
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
        ])->validate();

        $existingPlayer = EventEditionTeamPlayer::where('edition_id', $editionId)
            ->whereHas('person', fn ($q) => $q->where('number', $validated['dni']))
            ->first();

        if ($existingPlayer) {
            throw new RuntimeException('El jugador con este DNI ya está registrado en otro equipo para esta edición.');
        }

        $person = Person::create([
            'full_name' => trim($validated['father_lastname'].' '.$validated['mother_lastname'].' '.$validated['names']),
            'document_type_id' => '1',
            'number' => trim($validated['dni']),
            'father_lastname' => trim($validated['father_lastname']),
            'mother_lastname' => trim($validated['mother_lastname']),
            'names' => trim($validated['names']),
            'gender' => $validated['gender'],
            'status' => '1',
        ]);

        if (! empty($validated['image'])) {
            $imagePath = $this->saveBase64Image(
                $validated['image'],
                'uploads/eventos/jugadores',
                $person->id.'_'.time()
            );
            if ($imagePath) {
                $person->update(['image' => $imagePath]);
            }
        }

        return EventEditionTeamPlayer::create([
            'edition_id' => $editionId,
            'team_id' => $teamId,
            'person_id' => $person->id,
            'jersey_number' => $validated['jersey_number'] ?? '',
            'position' => $validated['position'] ?? '',
            'role_in_team' => $validated['role_in_team'] ?? 'Ninguno',
            'registration_date' => Carbon::now()->format('Y-m-d'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $editionId, int $teamId, int $personId, array $data): EventEditionTeamPlayer
    {
        $player = $this->findPlayerOrFail($editionId, $teamId, $personId);

        $validated = Validator::make($data, [
            'father_lastname' => 'sometimes|string|max:255',
            'mother_lastname' => 'sometimes|string|max:255',
            'names' => 'sometimes|string|max:255',
            'dni' => 'sometimes|string|max:20|unique:people,number,'.$personId,
            'gender' => 'sometimes|in:M,F',
            'jersey_number' => 'nullable|string|max:10',
            'position' => 'nullable|string|max:100',
            'role_in_team' => 'nullable|in:Ninguno,Capitán,Sub-Capitán',
        ], [
            'dni.unique' => 'El DNI ya está registrado en el sistema',
        ])->validate();

        $person = Person::findOrFail($personId);
        $personData = [];

        foreach (['father_lastname', 'mother_lastname', 'names', 'gender'] as $field) {
            if (array_key_exists($field, $validated)) {
                $personData[$field] = $validated[$field];
            }
        }

        if (isset($validated['dni'])) {
            $personData['number'] = $validated['dni'];
        }

        if ($personData) {
            $father = $validated['father_lastname'] ?? $person->father_lastname;
            $mother = $validated['mother_lastname'] ?? $person->mother_lastname;
            $names = $validated['names'] ?? $person->names;
            $personData['full_name'] = trim($father.' '.$mother.' '.$names);
            $person->update($personData);
        }

        $player->update([
            'jersey_number' => $validated['jersey_number'] ?? $player->jersey_number,
            'position' => $validated['position'] ?? $player->position,
            'role_in_team' => $validated['role_in_team'] ?? $player->role_in_team,
        ]);

        return $player->fresh('person');
    }

    public function uploadPhoto(int $editionId, int $teamId, int $personId, string $base64Image): Person
    {
        $this->findPlayerOrFail($editionId, $teamId, $personId);
        $person = Person::findOrFail($personId);

        $imagePath = $this->saveBase64Image(
            $base64Image,
            'uploads/eventos/jugadores',
            $personId.'_'.time()
        );

        if (! $imagePath) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        if ($person->image && Storage::disk('public')->exists($person->image)) {
            Storage::disk('public')->delete($person->image);
        }

        $person->update(['image' => $imagePath]);

        return $person->fresh();
    }

    public function delete(int $editionId, int $teamId, int $personId): void
    {
        $player = $this->findPlayerOrFail($editionId, $teamId, $personId);
        $player->delete();
    }

    public function searchPersonByDocument(int $documentTypeId, string $number): ?Person
    {
        return Person::where('document_type_id', $documentTypeId)
            ->where('number', $number)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function savePerson(array $data): Person
    {
        $validated = Validator::make($data, [
            'document_type_id' => 'required|integer',
            'number' => 'required|string|max:20',
            'full_name' => 'required|string|max:255',
            'father_lastname' => 'nullable|string|max:255',
            'mother_lastname' => 'nullable|string|max:255',
            'names' => 'nullable|string|max:255',
            'gender' => 'nullable|in:M,F',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'ubigeo' => 'nullable',
            'ubigeo_description' => 'nullable|string|max:255',
        ])->validate();

        return Person::updateOrCreate(
            [
                'document_type_id' => $validated['document_type_id'],
                'number' => $validated['number'],
            ],
            [
                'full_name' => trim($validated['full_name']),
                'father_lastname' => $validated['father_lastname'] ?? null,
                'mother_lastname' => $validated['mother_lastname'] ?? null,
                'names' => $validated['names'] ?? null,
                'gender' => $validated['gender'] ?? 'M',
                'telephone' => $validated['telephone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'ubigeo' => $validated['ubigeo'] ?? null,
                'ubigeo_description' => $validated['ubigeo_description'] ?? null,
                'status' => '1',
            ]
        );
    }

    public function formatPlayer(EventEditionTeamPlayer $player): array
    {
        $person = $player->person;

        return [
            'id' => $player->person_id,
            'person_id' => $player->person_id,
            'team_id' => $player->team_id,
            'edition_id' => $player->edition_id,
            'full_name' => $person?->full_name ?? '',
            'father_lastname' => $person?->father_lastname ?? '',
            'mother_lastname' => $person?->mother_lastname ?? '',
            'names' => $person?->names ?? '',
            'dni' => $person?->number ?? '',
            'gender' => $person?->gender ?? '',
            'image_url' => $person?->image ? asset('storage/'.$person->image) : null,
            'jersey_number' => $player->jersey_number ?? '',
            'position' => $player->position ?? '',
            'role_in_team' => $player->role_in_team ?? 'Ninguno',
            'registration_date' => $player->registration_date,
        ];
    }

    public function formatPerson(Person $person): array
    {
        return [
            'id' => $person->id,
            'person_id' => $person->id,
            'document_type_id' => $person->document_type_id,
            'number' => $person->number,
            'full_name' => $person->full_name,
            'father_lastname' => $person->father_lastname,
            'mother_lastname' => $person->mother_lastname,
            'names' => $person->names,
            'gender' => $person->gender,
            'telephone' => $person->telephone,
            'email' => $person->email,
            'address' => $person->address,
            'ubigeo' => $person->ubigeo,
            'ubigeo_description' => $person->ubigeo_description,
            'image_url' => $person->image ? asset('storage/'.$person->image) : null,
        ];
    }

    private function assertTeamInEdition(int $editionId, int $teamId): void
    {
        EventEdition::findOrFail($editionId);
        EventTeam::findOrFail($teamId);

        $inEdition = \Modules\Socialevents\Entities\EventEditionTeam::where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->exists();

        if (! $inEdition) {
            throw new RuntimeException('El equipo no está inscrito en esta edición.');
        }
    }

    private function findPlayerOrFail(int $editionId, int $teamId, int $personId): EventEditionTeamPlayer
    {
        return EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('team_id', $teamId)
            ->where('person_id', $personId)
            ->firstOrFail();
    }
}
