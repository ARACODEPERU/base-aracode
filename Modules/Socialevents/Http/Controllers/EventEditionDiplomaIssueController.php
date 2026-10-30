<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionAward;
use Modules\Socialevents\Entities\EventEditionDiploma;
use Modules\Socialevents\Entities\EventEditionDiplomaTemplate;
use Modules\Socialevents\Services\DiplomaPayloadService;
use Modules\Socialevents\Services\DiplomaTemplateService;
use Modules\Socialevents\Services\EditionAwardService;
use Modules\Socialevents\Services\SportsOrganizationService;

/**
 * Entrega el payload de render y registra la emisión del diploma.
 *
 * La emisión congela ganador, plantel y layout: una reimpresión posterior es
 * idéntica aunque el premio o el diseño cambien después.
 */
class EventEditionDiplomaIssueController extends Controller
{
    public function __construct(
        private SportsOrganizationService $sports,
        private EditionAwardService $awards,
        private DiplomaTemplateService $templates,
        private DiplomaPayloadService $payloads,
    ) {}

    public function payload(int $editionId, int $awardId): JsonResponse
    {
        $user = request()->user();
        $this->sports->assertCanRead($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::with(['evento.organization'])->findOrFail($editionId);
        $award = EventEditionAward::where('edition_id', $editionId)->findOrFail($awardId);

        // Si el premio ya se emitió, la reimpresión usa el modelo con el que se
        // emitió (no el que esté vigente ahora), para que el documento no
        // cambie al editar o desactivar modelos.
        $diploma = EventEditionDiploma::where('edition_id', $editionId)
            ->where('award_id', $award->id)
            ->latest('issued_at')
            ->latest('id')
            ->first();

        $template = $diploma?->template ?: $this->awards->resolveTemplate($award);

        if (! $template) {
            return $this->missingTemplate();
        }

        return response()->json([
            'success' => true,
            'payload' => $this->payloads->build($edition, $award, $template, $diploma),
        ]);
    }

    public function issue(int $editionId, int $awardId): JsonResponse
    {
        $user = request()->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::with(['evento.organization'])->findOrFail($editionId);
        $award = EventEditionAward::where('edition_id', $editionId)->findOrFail($awardId);
        $template = $this->awards->resolveTemplate($award);

        if (! $template) {
            return $this->missingTemplate();
        }

        $payload = $this->issueOne($edition, $award, $template, (int) $user->id);

        return response()->json([
            'success' => true,
            'payload' => $payload,
        ]);
    }

    /**
     * Emite todos los premios que tengan diseño vigente y devuelve sus payloads
     * en el orden de la lista (para el PDF multipágina).
     */
    public function issueAll(int $editionId): JsonResponse
    {
        $user = request()->user();
        $this->sports->assertCanWrite($user);
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::with(['evento.organization'])->findOrFail($editionId);

        $awards = EventEditionAward::where('edition_id', $editionId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $payloads = [];
        $skipped = [];

        foreach ($awards as $award) {
            $template = $this->awards->resolveTemplate($award);

            if (! $template) {
                $skipped[] = $award->label;

                continue;
            }

            $payloads[] = array_merge(
                $this->issueOne($edition, $award, $template, (int) $user->id),
                ['award_id' => $award->id]
            );
        }

        return response()->json([
            'success' => true,
            'payloads' => $payloads,
            'skipped' => $skipped,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function issueOne(
        EventEdition $edition,
        EventEditionAward $award,
        EventEditionDiplomaTemplate $template,
        int $userId
    ): array {
        // Un diploma vigente por premiado: al reemitir con otro modelo se
        // actualiza el mismo registro en vez de duplicarlo.
        $diploma = EventEditionDiploma::firstOrNew([
            'edition_id' => $edition->id,
            'award_id' => $award->id,
        ]);

        $diploma->template_id = $template->id;

        if (! $diploma->exists || ! $diploma->public_token) {
            $diploma->public_token = str_replace('-', '', (string) \Illuminate\Support\Str::uuid());
        }

        $diploma->fill([
            'recipient_type' => $award->recipient_type,
            'team_id' => $award->team_id,
            'person_id' => $award->person_id,
            'recipient_name' => $award->recipient_name,
            'team_name' => $award->team_name,
            'award_label' => $award->label,
            'edition_name' => (string) $edition->name,
            'squad_snapshot' => $award->isTeamAward() ? $this->awards->squad($award) : null,
            'layout_snapshot' => $template->layout,
            'issued_at' => now(),
            'issued_by' => $userId,
        ]);

        $diploma->save();

        return $this->payloads->build($edition, $award, $template, $diploma);
    }

    private function missingTemplate(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Este premio todavía no tiene un diseño de diploma vigente.',
        ], 422);
    }
}
