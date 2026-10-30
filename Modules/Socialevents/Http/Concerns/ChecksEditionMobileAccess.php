<?php

namespace Modules\Socialevents\Http\Concerns;

use Illuminate\Http\JsonResponse;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Services\SportsOrganizationService;

trait ChecksEditionMobileAccess
{
    /**
     * Una edición es visible si está publicada para la app/landing o si quien
     * consulta pertenece a la organización dueña. Se responde 404 en ambos
     * fallos para no revelar la existencia de ediciones ajenas.
     */
    protected function editionForMobileApi(int $editionId): EventEdition|JsonResponse
    {
        $edition = EventEdition::query()->find($editionId);
        $user = request()->user('sanctum');

        if (! $edition || ! app(SportsOrganizationService::class)->canViewEdition($user, $edition)) {
            return response()->json([
                'success' => false,
                'message' => 'Edición no encontrada',
                'data' => null,
            ], 404);
        }

        return $edition;
    }
}
