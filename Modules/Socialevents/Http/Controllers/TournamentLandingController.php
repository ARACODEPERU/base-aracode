<?php

namespace Modules\Socialevents\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Services\TournamentPublicDataService;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Support\TournamentLandingPresenter;

class TournamentLandingController extends Controller
{
    public function __construct(
        private TournamentPublicDataService $publicDataService
    ) {}

    public function show(string $slug)
    {
        if (ctype_digit($slug)) {
            $legacy = EventEdition::query()->find((int) $slug);

            if ($legacy?->public_slug) {
                return redirect()->route('socialevents_torneos_landing', [
                    'slug' => $legacy->public_slug,
                ], 301);
            }
        }

        $edition = EventEdition::with([
            'evento',
            'equipos.equipo',
        ])
            ->where('public_slug', $slug)
            ->first();

        if (! $edition && ctype_digit($slug)) {
            $edition = EventEdition::with([
                'evento',
                'equipos.equipo',
            ])->find((int) $slug);
        }

        if (! $edition) {
            abort(404, 'Edición o evento no encontrado.');
        }

        if (! $edition->landing_published) {
            abort(404, 'La landing de este torneo no está publicada.');
        }

        $editionId = $edition->id;

        $viewData = TournamentLandingCache::remember($editionId, function () use ($editionId) {
            $fresh = EventEdition::with([
                'evento',
                'equipos.equipo',
            ])->findOrFail($editionId);

            return array_merge(
                $this->publicDataService->buildForLanding($fresh),
                TournamentLandingPresenter::viewMeta($fresh),
            );
        });

        return view('socialevents::torneos.landing', $viewData);
    }

    /**
     * Registra una descarga de la app móvil e incrementa el contador de la edición.
     */
    public function downloadApp(string $slug)
    {
        $edition = EventEdition::query()
            ->where('public_slug', $slug)
            ->when(ctype_digit($slug), fn ($q) => $q->orWhere('id', (int) $slug))
            ->first();

        abort_unless($edition, 404);

        $url = TournamentLandingPresenter::appDownloadUrl($edition);

        abort_unless($url, 404);

        $edition->increment('app_downloads');

        // Refresca la vista cacheada para que el contador se actualice.
        TournamentLandingCache::forget((int) $edition->id);

        return redirect()->away($url);
    }
}
