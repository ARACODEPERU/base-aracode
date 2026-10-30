<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Http\Requests\EditionAwardRequest;
use Modules\Socialevents\Services\EditionAwardService;
use Modules\Socialevents\Services\SportsOrganizationService;

/**
 * Premiados de la edición: alta, edición, borrado, orden y sugerencias.
 */
class EventEditionAwardController extends Controller
{
    public function __construct(
        private SportsOrganizationService $sports,
        private EditionAwardService $awards,
    ) {}

    public function store(EditionAwardRequest $request, int $editionId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $award = $this->awards->save($editionId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Premiado agregado.',
            'award_id' => $award->id,
        ]);
    }

    public function update(EditionAwardRequest $request, int $editionId, int $awardId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $this->awards->save($editionId, $request->validated(), $awardId);

        return response()->json([
            'success' => true,
            'message' => 'Premiado actualizado.',
        ]);
    }

    public function destroy(int $editionId, int $awardId): JsonResponse
    {
        $user = request()->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $award = EventEditionAward::where('edition_id', $editionId)->findOrFail($awardId);
        $this->awards->delete($award);

        return response()->json([
            'success' => true,
            'message' => 'Premiado eliminado.',
        ]);
    }

    public function order(Request $request, int $editionId): JsonResponse
    {
        $user = $request->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $this->awards->reorder($editionId, $request->input('ids'));

        return response()->json(['success' => true]);
    }

    public function suggestions(int $editionId): JsonResponse
    {
        $user = request()->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        return response()->json([
            'success' => true,
            'candidates' => $this->awards->candidates($editionId),
        ]);
    }
}
