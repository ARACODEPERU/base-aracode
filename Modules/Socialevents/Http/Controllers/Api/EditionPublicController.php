<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Socialevents\Http\Concerns\ChecksEditionMobileAccess;
use Modules\Socialevents\Services\TournamentPublicDataService;

class EditionPublicController extends Controller
{
    use ChecksEditionMobileAccess;

    public function show(
        int $editionId,
        TournamentPublicDataService $publicDataService
    ): JsonResponse {
        $edition = $this->editionForMobileApi($editionId);

        if ($edition instanceof JsonResponse) {
            return $edition;
        }

        return response()->json([
            'success' => true,
            'message' => 'Datos públicos de la edición obtenidos correctamente',
            'data' => $publicDataService->buildForApi($editionId),
        ]);
    }
}
