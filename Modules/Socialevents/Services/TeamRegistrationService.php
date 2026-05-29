<?php

namespace Modules\Socialevents\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Socialevents\Entities\EventTeam;
use Modules\Socialevents\Support\SavesBase64Image;

class TeamRegistrationService
{
    use SavesBase64Image;

    /**
     * @param  array<string, mixed>  $data
     * @return array{team: EventTeam}
     */
    public function create(array $data): array
    {
        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'short_name' => 'required|string|max:10',
            'ubigeo' => 'nullable',
            'ubigeo_description' => 'nullable|string|max:255',
            'manager_id' => 'nullable|integer|exists:people,id',
            'logo_path' => 'nullable|string',
        ])->validate();

        $team = EventTeam::create([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'],
            'ubigeo' => $validated['ubigeo'] ?? null,
            'ubigeo_description' => $validated['ubigeo_description'] ?? null,
            'manager_id' => $validated['manager_id'] ?? null,
            'champion' => false,
            'status' => true,
        ]);

        if (! empty($validated['logo_path'])) {
            $logoPath = $this->saveBase64ImageAsUploaded(
                $validated['logo_path'],
                'uploads/eventos/equipos',
                (string) $team->id
            );
            if ($logoPath) {
                $team->update(['logo_path' => $logoPath]);
            }
        }

        return ['team' => $team->fresh(['manager'])];
    }

    public function formatTeam(EventTeam $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'short_name' => $team->short_name,
            'ubigeo' => $team->ubigeo,
            'ubigeo_description' => $team->ubigeo_description,
            'logo_url' => $team->logo_path ? asset('storage/'.$team->logo_path) : null,
            'manager_id' => $team->manager_id,
            'manager_name' => $team->manager?->full_name,
            'status' => (bool) $team->status,
        ];
    }
}
