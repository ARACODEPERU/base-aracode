<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Socialevents\Http\Concerns\ChecksEditionMobileAccess;
use Modules\Socialevents\Services\TournamentFixtureService;

class MatchesApiController extends Controller
{
    use ChecksEditionMobileAccess;

    public function __construct(
        private TournamentFixtureService $fixtureService
    ) {}

    public function getUpcomingMatches(int $editionId): JsonResponse
    {
        $edition = $this->editionForMobileApi($editionId);

        if ($edition instanceof JsonResponse) {
            return $edition;
        }

        return response()->json([
            'success' => true,
            'message' => 'Partidos obtenidos correctamente',
            'data' => $this->fixtureService->upcomingPayload($editionId),
        ]);
    }

    public function getRecentResults(int $editionId): JsonResponse
    {
        $edition = $this->editionForMobileApi($editionId);

        if ($edition instanceof JsonResponse) {
            return $edition;
        }

        $data = $this->fixtureService->recentResultsPayload($editionId);

        return response()->json([
            'success' => true,
            'message' => $data === [] ? 'No hay partidos jugados' : 'Resultados obtenidos correctamente',
            'data' => $data,
        ]);
    }

    public function getAllMatches(int $editionId, string $filter = 'all', int $page = 1, int $perPage = 10): JsonResponse
    {
        $edition = $this->editionForMobileApi($editionId);

        if ($edition instanceof JsonResponse) {
            return $edition;
        }

        $result = $this->fixtureService->paginatedPayload($editionId, (string) $filter, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Partidos obtenidos correctamente',
            'data' => $result['data'],
            'pagination' => $result['pagination'],
        ]);
    }
}
