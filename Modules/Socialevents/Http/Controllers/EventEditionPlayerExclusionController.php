<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionPlayerExclusion;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Support\TournamentLandingCache;

class EventEditionPlayerExclusionController extends Controller
{
    /**
     * Lista las exclusiones de jugadores de una edición.
     */
    public function index(int $editionId): Response
    {
        $edicion = EventEdition::findOrFail($editionId);

        $exclusions = EventEditionPlayerExclusion::with('player')
            ->where('edition_id', $editionId)
            ->orderByDesc('id')
            ->get();

        $excludedIds = $exclusions->pluck('player_id')->all();

        // Jugadores inscritos en los equipos de la edición (para el selector).
        $players = EventEditionTeamPlayer::with(['team', 'person'])
            ->where('edition_id', $editionId)
            ->whereNotIn('person_id', $excludedIds)
            ->get()
            ->groupBy('team_id')
            ->mapWithKeys(function ($teamPlayers) {
                $team = $teamPlayers->first()->team;
                $label = $team->name ?? 'Equipo';

                $members = $teamPlayers
                    ->filter(fn ($tp) => $tp->person !== null)
                    ->map(function ($tp) {
                        return [
                            'player_id' => $tp->person_id,
                            'name' => $tp->person->full_name ?? $tp->person->names ?? 'Jugador sin nombre',
                        ];
                    })
                    ->values();

                return [$label => $members];
            });

        return Inertia::render('Socialevents::Editions/PlayerExclusions', [
            'edicion' => $edicion,
            'exclusions' => $exclusions,
            'players' => $players,
        ]);
    }

    /**
     * Registra la exclusión definitiva de un jugador en la edición.
     */
    public function store(Request $request, int $editionId): RedirectResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer',
            'reason' => 'required|string|max:500',
            'excluded_by' => 'nullable|string|max:255',
        ]);

        $exists = EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('person_id', $validated['player_id'])
            ->exists();

        if (! $exists) {
            return back()->withErrors([
                'player_id' => 'El jugador seleccionado no está inscrito en esta edición.',
            ]);
        }

        EventEditionPlayerExclusion::updateOrCreate(
            [
                'edition_id' => $editionId,
                'player_id' => $validated['player_id'],
            ],
            [
                'reason' => $validated['reason'],
                'excluded_by' => $validated['excluded_by'] ?? null,
                'excluded_at' => now(),
            ]
        );

        TournamentLandingCache::forget($editionId);

        return back()->with('success', 'Jugador excluido de los rankings de la edición.');
    }

    /**
     * Elimina la exclusión y restaura al jugador en los rankings.
     */
    public function destroy(Request $request, int $editionId, int $exclusionId): RedirectResponse
    {
        $exclusion = EventEditionPlayerExclusion::where('edition_id', $editionId)
            ->findOrFail($exclusionId);

        $exclusion->delete();

        TournamentLandingCache::forget($editionId);

        return back()->with('success', 'Exclusión eliminada. El jugador vuelve a los rankings.');
    }
}