<?php

namespace Modules\Socialevents\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Services\TournamentPublicDataService;
use Modules\Socialevents\Services\TournamentRankingsService;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Support\TournamentLandingPresenter;

class TournamentLandingController extends Controller
{
    public function __construct(
        private TournamentPublicDataService $publicDataService,
        private TournamentRankingsService $rankingsService,
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

        $edition = $this->resolveEdition($slug);

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
     * Detalle partido a partido de un jugador del torneo (modal de la landing).
     *
     * Endpoint público: solo responde para ediciones con la landing publicada.
     */
    public function playerDetail(Request $request, string $slug, int $playerId): JsonResponse
    {
        $edition = $this->resolveEdition($slug);

        if (! $edition || ! $edition->landing_published) {
            return response()->json([
                'success' => false,
                'message' => 'Torneo no disponible.',
            ], 404);
        }

        // Se normaliza antes de cachear: la clave de caché queda acotada a las
        // tres categorías válidas y no a cualquier texto recibido por la URL.
        $category = (string) $request->query('categoria', 'player');

        if (! in_array($category, TournamentRankingsService::DETAIL_CATEGORIES, true)) {
            $category = 'player';
        }

        $payload = TournamentLandingCache::rememberPlayerDetail(
            (int) $edition->id,
            $playerId,
            $category,
            fn () => $this->rankingsService->playerDetail((int) $edition->id, $playerId, $category)
        );

        if (! $payload) {
            return response()->json([
                'success' => false,
                'message' => 'No encontramos estadísticas de este jugador.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
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

    /**
     * Resuelve la edición por slug público o por id legado.
     */
    private function resolveEdition(string $slug): ?EventEdition
    {
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

        return $edition;
    }
}
