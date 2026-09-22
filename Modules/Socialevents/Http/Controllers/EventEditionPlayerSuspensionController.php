<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Services\PlayerSuspensionService;
use Modules\Socialevents\Support\TournamentLandingCache;

class EventEditionPlayerSuspensionController extends Controller
{
    public function __construct(private PlayerSuspensionService $suspensionService)
    {
    }

    /**
     * Lista las suspensiones de jugadores de una edición.
     */
    public function index(int $editionId): Response
    {
        $edicion = EventEdition::findOrFail($editionId);

        $suspensions = \Modules\Socialevents\Entities\EventEditionPlayerSuspension::with('player')
            ->where('edition_id', $editionId)
            ->orderByDesc('id')
            ->get();

        $suspendedIds = $suspensions->filter(fn ($s) => $s->lifted_at === null)->pluck('player_id')->all();

        // Jugadores inscritos en los equipos de la edición (para el selector).
        $players = EventEditionTeamPlayer::with(['team', 'person'])
            ->where('edition_id', $editionId)
            ->whereNotIn('person_id', $suspendedIds)
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

        return Inertia::render('Socialevents::Editions/PlayerSuspensions', [
            'edicion' => $edicion,
            'suspensions' => $suspensions,
            'players' => $players,
        ]);
    }

    /**
     * Registra la suspensión de un jugador en la edición.
     */
    public function store(Request $request, int $editionId): RedirectResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|integer',
            'type' => 'required|in:definitive,matches,date_range',
            'matches_count' => 'nullable|integer|min:1|max:100|required_if:type,matches',
            'starts_at' => 'nullable|date|required_if:type,date_range',
            'ends_at' => 'nullable|date|after_or_equal:starts_at|required_if:type,date_range',
            'reason' => 'required|string|max:500',
            'suspended_by' => 'nullable|string|max:255',
        ], [
            'matches_count.required_if' => 'Indica cuántos partidos durará la suspensión.',
            'starts_at.required_if' => 'Indica la fecha de inicio de la suspensión.',
            'ends_at.required_if' => 'Indica la fecha de fin de la suspensión.',
        ]);

        $exists = EventEditionTeamPlayer::where('edition_id', $editionId)
            ->where('person_id', $validated['player_id'])
            ->exists();

        if (! $exists) {
            return back()->withErrors([
                'player_id' => 'El jugador seleccionado no está inscrito en esta edición.',
            ]);
        }

        $this->suspensionService->suspend([
            'edition_id' => $editionId,
            'player_id' => $validated['player_id'],
            'type' => $validated['type'],
            'matches_count' => $validated['matches_count'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'reason' => $validated['reason'],
            'suspended_by' => $validated['suspended_by'] ?? null,
        ]);

        TournamentLandingCache::forget($editionId);

        return back()->with('success', 'Jugador suspendido. No podrá jugar ni aparecer en los rankings mientras la suspensión esté vigente.');
    }

    /**
     * Levanta manualmente la suspensión (el jugador vuelve a jugar).
     */
    public function destroy(Request $request, int $editionId, int $suspensionId): RedirectResponse
    {
        $suspension = \Modules\Socialevents\Entities\EventEditionPlayerSuspension::where('edition_id', $editionId)
            ->findOrFail($suspensionId);

        $this->suspensionService->lift($suspension);

        TournamentLandingCache::forget($editionId);

        return back()->with('success', 'Suspensión levantada. El jugador vuelve a jugar y a los rankings.');
    }
}
