<?php

namespace Modules\Socialevents\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Services\TournamentLandingVisitService;
use Modules\Socialevents\Services\TournamentPlayerProfileService;
use Modules\Socialevents\Services\TournamentPublicDataService;
use Modules\Socialevents\Services\TournamentRankingsService;
use Modules\Socialevents\Services\TournamentTeamProfileService;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Support\TournamentLandingPresenter;

class TournamentLandingController extends Controller
{
    public function __construct(
        private TournamentPublicDataService $publicDataService,
        private TournamentRankingsService $rankingsService,
        private TournamentLandingVisitService $visitService,
        private TournamentPlayerProfileService $playerProfileService,
        private TournamentTeamProfileService $teamProfileService,
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

        // El contador no debe impedir que la landing se muestre si algo falla.
        $this->visitService->record($edition, request());

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
     * Ficha pública del jugador: identidad, totales, puesto en goleadores y
     * partido a partido.
     *
     * Misma regla de visibilidad que la landing: sin publicación no hay ficha.
     * Si el jugador no pertenece a la edición se responde 404 con una página
     * del mismo diseño, nunca con un error técnico.
     */
    public function playerProfile(string $slug, int $playerId)
    {
        $edition = $this->resolveEdition($slug);

        abort_unless($edition, 404, 'Edición o evento no encontrado.');

        if (! $edition->landing_published) {
            abort(404, 'La landing de este torneo no está publicada.');
        }

        $editionId = (int) $edition->id;

        $profile = TournamentLandingCache::rememberPlayerDetail(
            $editionId,
            $playerId,
            'profile',
            fn () => $this->playerProfileService->build($editionId, $playerId)
        );

        $playerName = $profile['player']['name'] ?? 'Jugador no encontrado';

        $viewData = array_merge(TournamentLandingPresenter::viewMeta($edition), [
            'edition' => $edition,
            'profile' => $profile,
            'playerId' => $playerId,
            'playerProfileUrl' => route('socialevents_torneos_player_profile', [
                'slug' => $edition->landingSlug(),
                'playerId' => $playerId,
            ]),
            'seoTitle' => "{$playerName} | {$edition->evento?->title} {$edition->name}",
            'seoDescription' => Str::limit(
                trim(($edition->evento?->title ?? 'Torneo').' '.$edition->name
                    .'. Estadísticas y logros de '.$playerName.': partidos, goles, asistencias, MVP y sanciones.'),
                160,
                '…'
            ),
        ]);

        // El canonical de la ficha es su propia URL, no la de la landing.
        $viewData['canonicalUrl'] = $viewData['playerProfileUrl'];

        if (! $profile) {
            return response()->view('socialevents::torneos.player', $viewData, 404);
        }

        return view('socialevents::torneos.player', $viewData);
    }

    /**
     * Ficha pública del equipo: identidad, puesto, plantilla, logros,
     * disciplina, historial y cronología de su participación.
     *
     * Se rige por la misma regla que la landing: sin publicación no hay ficha,
     * y un equipo ajeno a la edición responde 404 con el mismo diseño.
     */
    public function teamProfile(string $slug, int $teamId)
    {
        $edition = $this->resolveEdition($slug);

        abort_unless($edition, 404, 'Edición o evento no encontrado.');

        if (! $edition->landing_published) {
            abort(404, 'La landing de este torneo no está publicada.');
        }

        $editionId = (int) $edition->id;

        $profile = TournamentLandingCache::rememberTeamDetail(
            $editionId,
            $teamId,
            fn () => $this->teamProfileService->build($edition, $teamId)
        );

        $teamName = $profile['team']['name'] ?? 'Equipo no encontrado';

        $viewData = array_merge(TournamentLandingPresenter::viewMeta($edition), [
            'edition' => $edition,
            'profile' => $profile,
            'teamId' => $teamId,
            'teamProfileUrl' => route('socialevents_torneos_team_profile', [
                'slug' => $edition->landingSlug(),
                'teamId' => $teamId,
            ]),
            'seoTitle' => "{$teamName} | {$edition->evento?->title} {$edition->name}",
            'seoDescription' => Str::limit(
                trim(($edition->evento?->title ?? 'Torneo').' '.$edition->name
                    .'. Historia de '.$teamName.': puesto final, plantilla, logros, sanciones y partido a partido.'),
                160,
                '…'
            ),
        ]);

        // El canonical de la ficha es su propia URL, no la de la landing.
        $viewData['canonicalUrl'] = $viewData['teamProfileUrl'];

        if (! $profile) {
            return response()->view('socialevents::torneos.team', $viewData, 404);
        }

        return view('socialevents::torneos.team', $viewData);
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
