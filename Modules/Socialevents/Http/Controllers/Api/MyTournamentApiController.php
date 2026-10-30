<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Services\SportsOrganizationService;
use Modules\Socialevents\Support\TournamentMedia;

/**
 * Torneos de la organización autenticada.
 *
 * A diferencia de los endpoints públicos, aquí sí se incluyen las ediciones que
 * todavía no se publicaron: la autorización es por pertenencia, no por landing.
 * La organización sale del token; nunca de la URL ni del cuerpo.
 */
class MyTournamentApiController extends Controller
{
    public function __construct(
        private SportsOrganizationService $sports
    ) {}

    public function events(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizationId = $this->sports->organizationId($user);

        if (! $organizationId) {
            return $this->missingOrganization();
        }

        $this->sports->assertCanRead($user);

        $events = EvenEvent::query()
            ->where('organization_id', $organizationId)
            ->with(['editions' => function ($query) {
                $query->whereNotIn('status', ['CA'])
                    ->withCount(['equipos', 'matches'])
                    ->orderByDesc('created_at');
            }])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Torneos de la organización obtenidos correctamente',
            'data' => $events->map(fn (EvenEvent $event) => $this->eventPayload($event))->values(),
        ]);
    }

    public function edition(Request $request, int $editionId): JsonResponse
    {
        $user = $request->user();

        if (! $this->sports->organizationId($user)) {
            return $this->missingOrganization();
        }

        $this->sports->assertCanRead($user);

        // Un ID de otra organización responde 404, sin revelar que existe.
        $this->sports->assertOwnedEdition($user, $editionId);

        $edition = EventEdition::query()
            ->with('evento')
            ->withCount(['equipos', 'matches'])
            ->findOrFail($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Edición obtenida correctamente',
            'data' => $this->editionPayload($edition, true),
        ]);
    }

    /** @return array<string, mixed> */
    private function eventPayload(EvenEvent $event): array
    {
        $editions = $event->editions
            ->map(fn (EventEdition $edition) => $this->editionPayload($edition))
            ->values();

        return [
            'id' => (int) $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'image' => TournamentMedia::url($event->image1),
            'date_start' => $event->date_start,
            'date_end' => $event->date_end,
            'status' => $event->status,
            'broadcast' => (bool) $event->broadcast,
            'editions' => $editions,
            // Compatibilidad con el modelo móvil actual, que espera una edición.
            'edition' => $editions->first(),
            'has_edition' => $editions->isNotEmpty(),
        ];
    }

    /** @return array<string, mixed> */
    private function editionPayload(EventEdition $edition, bool $withEvent = false): array
    {
        $payload = [
            'id' => (int) $edition->id,
            'name' => $edition->name,
            'year' => $edition->year,
            'start_date' => $edition->start_date,
            'end_date' => $edition->end_date,
            'competition_format' => $edition->competition_format,
            'status' => $edition->status,
            'landing_published' => (bool) $edition->landing_published,
            'mobile_enabled' => (bool) $edition->mobile_enabled,
            'teams_count' => (int) ($edition->equipos_count ?? $edition->equipos()->count()),
            'matches_count' => (int) ($edition->matches_count ?? $edition->matches()->count()),
        ];

        if ($withEvent) {
            $payload['event'] = $edition->evento ? [
                'id' => (int) $edition->evento->id,
                'title' => $edition->evento->title,
                'image' => TournamentMedia::url($edition->evento->image1),
            ] : null;

            // La app edita la edición con las mismas reglas que la web: necesita
            // la configuración vigente, no solo lo que muestra el listado.
            $payload += [
                'event_id' => (int) $edition->event_id,
                'score_points_win' => (int) $edition->score_points_win,
                'score_points_draw' => (int) $edition->score_points_draw,
                'score_points_loss' => (int) $edition->score_points_loss,
                'inscription_fee' => (float) $edition->inscription_fee,
                'min_players_per_team' => (int) $edition->min_players_per_team,
                'max_players_per_team' => (int) $edition->max_players_per_team,
                'regular_half_minutes' => (int) $edition->regular_half_minutes,
                'regular_halves' => (int) $edition->regular_halves,
                'extra_half_minutes' => (int) $edition->extra_half_minutes,
                'extra_halves' => (int) $edition->extra_halves,
                'extra_time_enabled' => (bool) $edition->extra_time_enabled,
                'max_substitutions' => $edition->max_substitutions,
                'extra_substitution_enabled' => (bool) $edition->extra_substitution_enabled,
                'yellow_price' => (float) $edition->yellow_price,
                'direct_red_price' => (float) $edition->direct_red_price,
                'double_yellow_price' => (float) $edition->double_yellow_price,
                'contact_name' => $edition->contact_name,
                'contact_phone' => $edition->contact_phone,
                'contact_whatsapp' => $edition->contact_whatsapp,
                'public_slug' => $edition->public_slug,
                'prize_details' => $edition->prize_details,
            ];
        }

        return $payload;
    }

    private function missingOrganization(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'ORG_REQUIRED',
            'message' => 'Esta cuenta no tiene una organización asociada. Crea tu cuenta desde la web.',
        ], 403);
    }
}
