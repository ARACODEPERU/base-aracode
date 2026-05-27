<?php

namespace Modules\Socialevents\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Modules\Socialevents\Entities\EventEdition;

trait ChecksEditionMobileAccess
{
    protected function editionForMobileApi(int $editionId): EventEdition|JsonResponse
    {
        $edition = EventEdition::query()->find($editionId);

        if (! $edition) {
            return response()->json([
                'success' => false,
                'message' => 'Edición no encontrada',
                'data' => null,
            ], 404);
        }

        if (! $edition->mobile_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Esta edición no está disponible en la aplicación móvil',
                'data' => null,
            ], 403);
        }

        return $edition;
    }
}
