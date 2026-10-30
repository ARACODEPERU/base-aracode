<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionTeamPlayer;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Services\SportsOrganizationService;
use Modules\Socialevents\Services\TournamentService;
use Modules\Socialevents\Support\EditionSlugService;
use Modules\Socialevents\Support\TournamentLandingCache;

/**
 * Alta y edición de torneos y ediciones desde la app móvil (Fase 5A).
 *
 * Repite las reglas de la web que importan al organizador: pertenencia a su
 * organización, acceso de escritura, fechas coherentes y catálogo de formatos.
 * Las rutas viven dentro del grupo `admin`, así que un acceso vencido responde
 * `403 ORG_READ_ONLY` antes de llegar aquí.
 */
class TournamentAdminApiController extends Controller
{
    private const FORMATS = ['round_robin', 'group_stage_and_playoffs', 'single_elimination'];

    public function __construct(
        private SportsOrganizationService $sports
    ) {}

    public function storeEvent(Request $request): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());

        $validated = $this->validateEvent($request);

        $event = EvenEvent::create([
            'organization_id' => $this->sports->isSuperAdmin($request->user())
                ? $this->sports->legacyOrganizationId()
                : $this->sports->organizationId($request->user()),
            'category_id' => $this->sports->sportsMode()
                ? $this->sports->sportsCategoryId()
                : null,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'date_start' => $validated['date_start'],
            'date_end' => $validated['date_end'],
            'number_days' => $this->numberOfDays($validated),
            'tickets_quantity' => $validated['tickets_quantity'] ?? 0,
            'tickets_available' => $validated['tickets_quantity'] ?? 0,
            'broadcast' => (bool) ($validated['broadcast'] ?? false),
            'iframe_transmission' => $validated['iframe_transmission'] ?? null,
            'status' => 'PE',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Torneo creado correctamente',
            'data' => $this->eventPayload($event),
        ], 201);
    }

    public function updateEvent(Request $request, int $eventId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEvent($request->user(), $eventId);

        $validated = $this->validateEvent($request);
        $event = EvenEvent::findOrFail($eventId);

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'date_start' => $validated['date_start'],
            'date_end' => $validated['date_end'],
            'number_days' => $this->numberOfDays($validated),
            'tickets_quantity' => $validated['tickets_quantity'] ?? $event->tickets_quantity,
            'tickets_available' => $validated['tickets_quantity'] ?? $event->tickets_available,
            'broadcast' => (bool) ($validated['broadcast'] ?? false),
            'iframe_transmission' => $validated['iframe_transmission'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Torneo actualizado correctamente',
            'data' => $this->eventPayload($event->fresh()),
        ]);
    }

    public function storeEdition(Request $request): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());

        $validated = $this->validateEdition($request);
        $this->assertOwnedEvent($request->user(), (int) $validated['event_id']);

        $slugService = app(EditionSlugService::class);
        $slug = $slugService->normalizeSlug($validated['public_slug'] ?? null)
            ?? $slugService->generateUniqueSlug(
                (string) $validated['name'],
                $validated['year'] ?? null
            );

        $edition = EventEdition::create([
            'event_id' => $validated['event_id'],
            'year' => (string) $validated['year'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'competition_format' => $validated['competition_format'],
            'score_points_win' => $validated['score_points_win'],
            'score_points_draw' => $validated['score_points_draw'],
            'score_points_loss' => $validated['score_points_loss'],
            'inscription_fee' => $validated['inscription_fee'] ?? 0,
            'min_players_per_team' => $validated['min_players_per_team'],
            'max_players_per_team' => $validated['max_players_per_team'],
            'regular_half_minutes' => $validated['regular_half_minutes'] ?? 20,
            'regular_halves' => $validated['regular_halves'] ?? 2,
            'extra_half_minutes' => $validated['extra_half_minutes'] ?? 10,
            'extra_halves' => $validated['extra_halves'] ?? 2,
            'extra_time_enabled' => (bool) ($validated['extra_time_enabled'] ?? false),
            'max_substitutions' => $validated['max_substitutions'] ?? null,
            'extra_substitution_enabled' => (bool) ($validated['extra_substitution_enabled'] ?? true),
            'yellow_price' => $validated['yellow_price'] ?? 0,
            'direct_red_price' => $validated['direct_red_price'] ?? 0,
            'double_yellow_price' => $validated['double_yellow_price'] ?? 0,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'contact_whatsapp' => $validated['contact_whatsapp'] ?? null,
            'landing_published' => (bool) ($validated['landing_published'] ?? false),
            'mobile_enabled' => (bool) ($validated['mobile_enabled'] ?? true),
            'public_slug' => $slug,
            'status' => 'pending',
        ]);

        TournamentLandingCache::forget((int) $edition->id);

        return response()->json([
            'success' => true,
            'message' => 'Edición creada correctamente',
            'data' => $this->editionPayload($edition),
        ], 201);
    }

    public function updateEdition(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $validated = $this->validateEdition($request, $editionId);
        $this->assertOwnedEvent($request->user(), (int) $validated['event_id']);

        $edition = EventEdition::findOrFail($editionId);

        // Una edición no se mueve de torneo: cambiaría el dueño de los datos.
        abort_unless((int) $edition->event_id === (int) $validated['event_id'], 422);

        $slugService = app(EditionSlugService::class);
        $slug = $slugService->normalizeSlug($validated['public_slug'] ?? null)
            ?? $edition->public_slug
            ?? $slugService->generateUniqueSlug(
                (string) $validated['name'],
                $validated['year'] ?? null,
                $editionId
            );

        $edition->update([
            'year' => (string) $validated['year'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'competition_format' => $validated['competition_format'],
            'score_points_win' => $validated['score_points_win'],
            'score_points_draw' => $validated['score_points_draw'],
            'score_points_loss' => $validated['score_points_loss'],
            'inscription_fee' => $validated['inscription_fee'] ?? 0,
            'min_players_per_team' => $validated['min_players_per_team'],
            'max_players_per_team' => $validated['max_players_per_team'],
            'regular_half_minutes' => $validated['regular_half_minutes'] ?? 20,
            'regular_halves' => $validated['regular_halves'] ?? 2,
            'extra_half_minutes' => $validated['extra_half_minutes'] ?? 10,
            'extra_halves' => $validated['extra_halves'] ?? 2,
            'extra_time_enabled' => (bool) ($validated['extra_time_enabled'] ?? false),
            'max_substitutions' => $validated['max_substitutions'] ?? null,
            'extra_substitution_enabled' => (bool) ($validated['extra_substitution_enabled'] ?? true),
            'yellow_price' => $validated['yellow_price'] ?? 0,
            'direct_red_price' => $validated['direct_red_price'] ?? 0,
            'double_yellow_price' => $validated['double_yellow_price'] ?? 0,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'contact_whatsapp' => $validated['contact_whatsapp'] ?? null,
            'landing_published' => (bool) ($validated['landing_published'] ?? false),
            'mobile_enabled' => (bool) ($validated['mobile_enabled'] ?? true),
            'public_slug' => $slug,
        ]);

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Edición actualizada correctamente',
            'data' => $this->editionPayload($edition->fresh()),
        ]);
    }

    /**
     * Reemplaza los premios de la edición (posiciones 1 a 4).
     *
     * Se guardan con las claves que ya lee la landing (`first`, `second`...),
     * así lo editado desde la app se ve igual en la web.
     */
    public function updatePrizes(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $validated = $request->validate([
            'prizes' => 'present|array|max:4',
            'prizes.*.position' => 'required|integer|between:1,4|distinct',
            'prizes.*.money' => 'nullable|numeric|min:0|max:9999999',
            'prizes.*.gift' => 'nullable|string|max:255',
        ], [
            'prizes.*.position.distinct' => 'Cada puesto solo puede aparecer una vez.',
            'prizes.*.money.numeric' => 'El monto del premio debe ser un número.',
        ]);

        $keys = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth'];
        $details = [];

        foreach ($validated['prizes'] as $prize) {
            $money = $prize['money'] ?? null;
            $gift = trim((string) ($prize['gift'] ?? ''));

            if (($money === null || $money === '') && $gift === '') {
                continue;
            }

            $details[$keys[(int) $prize['position']]] = [
                'money' => $money === null || $money === '' ? null : round((float) $money, 2),
                'gift' => $gift !== '' ? $gift : null,
            ];
        }

        $edition = EventEdition::findOrFail($editionId);
        $edition->update(['prize_details' => $details !== [] ? $details : null]);

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Premios de la edición actualizados correctamente',
            'data' => ['prize_details' => $edition->fresh()->prize_details],
        ]);
    }

    /**
     * Estado de publicación de la landing y de la visibilidad en la app.
     *
     * Incluye una lista de comprobaciones para que el organizador sepa qué
     * falta antes de difundir el enlace; no bloquea la publicación, igual que
     * la web.
     */
    public function publication(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanRead($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $edition = EventEdition::with('evento')->findOrFail($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Publicación de la edición obtenida correctamente',
            'data' => $this->publicationPayload($edition),
        ]);
    }

    /**
     * Publica o despublica la landing y la app, y guarda el enlace amigable.
     */
    public function updatePublication(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $validated = $request->validate([
            'landing_published' => 'required|boolean',
            'mobile_enabled' => 'required|boolean',
            'public_slug' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('event_editions', 'public_slug')->ignore($editionId),
            ],
        ], [
            'public_slug.regex' => 'El enlace solo admite letras minúsculas, números y guiones.',
            'public_slug.unique' => 'Ese enlace ya lo usa otra edición.',
        ]);

        $edition = EventEdition::findOrFail($editionId);

        $slugService = app(EditionSlugService::class);

        // No repetimos el año cuando el nombre ya lo incluye ("Copa 2026-2026").
        $yearInName = str_contains(
            Str::slug((string) $edition->name),
            Str::slug((string) $edition->year)
        );

        $slug = $slugService->normalizeSlug($validated['public_slug'] ?? null)
            ?? $edition->public_slug
            ?? $slugService->generateUniqueSlug(
                (string) $edition->name,
                $yearInName ? null : $edition->year,
                $editionId
            );

        $edition->update([
            'landing_published' => (bool) $validated['landing_published'],
            'mobile_enabled' => (bool) $validated['mobile_enabled'],
            'public_slug' => $slug,
        ]);

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Publicación actualizada correctamente',
            'data' => $this->publicationPayload($edition->fresh('evento')),
        ]);
    }

    /**
     * Genera los cruces todos contra todos que falten, sin tocar los partidos
     * que ya existen. El cobro por partido se registra igual que en la web.
     */
    public function generateFixture(Request $request, int $editionId): JsonResponse
    {
        $this->sports->assertCanWrite($request->user());
        $this->sports->assertOwnedEdition($request->user(), $editionId);

        $validated = $request->validate([
            'teams' => 'required|array|min:2',
            'teams.*' => 'integer',
        ], [
            'teams.required' => 'Selecciona los equipos del fixture.',
            'teams.min' => 'Necesitas al menos 2 equipos.',
        ]);

        $teamIds = collect($validated['teams'])->map(fn ($id) => (int) $id)->unique()->values();

        foreach ($teamIds as $teamId) {
            $this->sports->assertOwnedEditionTeam($request->user(), $editionId, $teamId);
        }

        $registered = EventEditionTeam::where('edition_id', $editionId)
            ->whereIn('team_id', $teamIds)
            ->pluck('team_id')
            ->map(fn ($id) => (int) $id);

        abort_unless($registered->count() === $teamIds->count(), 422, 'Todos los equipos deben estar inscritos en la edición.');

        $user = $request->user();
        $sports = $this->sports;

        $created = (new TournamentService())->generateMissingRoundRobin(
            $editionId,
            $teamIds->all(),
            function ($nuevos) use ($user, $editionId, $sports) {
                if ($nuevos->isEmpty()) return;

                $isFree = $sports->registerMatchBatch($nuevos, $user, $editionId, 'round_robin');
                $sports->chargeMatches($nuevos, $user, $isFree);
            }
        );

        return response()->json([
            'success' => true,
            'message' => $created->isEmpty()
                ? 'El fixture ya estaba completo.'
                : 'Fixture generado correctamente',
            'data' => [
                'created' => $created->count(),
                'total' => EventEditionMatch::where('edition_id', $editionId)->count(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:500',
            'description' => 'required|string',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'tickets_quantity' => 'nullable|integer|min:0|max:1000000',
            'broadcast' => 'nullable|boolean',
            'iframe_transmission' => 'nullable|string|max:2048',
        ], [
            'title.required' => 'El nombre del torneo es obligatorio.',
            'description.required' => 'La descripción del torneo es obligatoria.',
            'date_start.required' => 'La fecha de inicio es obligatoria.',
            'date_end.after_or_equal' => 'La fecha de fin no puede ser anterior al inicio.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEdition(Request $request, ?int $editionId = null): array
    {
        return $request->validate([
            'event_id' => 'required|integer|exists:even_events,id',
            'year' => 'required|digits:4',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'competition_format' => ['required', Rule::in(self::FORMATS)],
            'score_points_win' => 'required|integer|min:0|max:100',
            'score_points_draw' => 'required|integer|min:0|max:100',
            'score_points_loss' => 'required|integer|min:0|max:100',
            'inscription_fee' => 'nullable|numeric|min:0|max:999999.99',
            'min_players_per_team' => 'required|integer|min:1|max:50',
            'max_players_per_team' => 'required|integer|min:1|max:80|gte:min_players_per_team',
            'regular_half_minutes' => 'nullable|integer|min:1|max:200',
            'regular_halves' => 'nullable|integer|min:1|max:4',
            'extra_half_minutes' => 'nullable|integer|min:1|max:60',
            'extra_halves' => 'nullable|integer|min:1|max:4',
            'extra_time_enabled' => 'nullable|boolean',
            'max_substitutions' => 'nullable|integer|min:0|max:20',
            'extra_substitution_enabled' => 'nullable|boolean',
            'yellow_price' => 'nullable|numeric|min:0|max:9999.99',
            'direct_red_price' => 'nullable|numeric|min:0|max:9999.99',
            'double_yellow_price' => 'nullable|numeric|min:0|max:9999.99',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_whatsapp' => 'nullable|string|max:50',
            'landing_published' => 'nullable|boolean',
            'mobile_enabled' => 'nullable|boolean',
            'public_slug' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('event_editions', 'public_slug')->ignore($editionId),
            ],
        ], [
            'competition_format.in' => 'El formato elegido no está disponible.',
            'max_players_per_team.gte' => 'El máximo de jugadores no puede ser menor que el mínimo.',
            'year.digits' => 'El año debe tener cuatro dígitos.',
            'public_slug.regex' => 'El enlace solo admite letras minúsculas, números y guiones.',
        ]);
    }

    private function assertOwnedEvent($user, int $eventId): void
    {
        if (! $this->sports->sportsMode() || $this->sports->isSuperAdmin($user)) {
            abort_unless(EvenEvent::whereKey($eventId)->exists(), 404);
            return;
        }

        $organizationId = $this->sports->organizationId($user);
        abort_unless(
            $organizationId && EvenEvent::whereKey($eventId)
                ->where('organization_id', $organizationId)
                ->exists(),
            404
        );
    }

    /** @param array<string, mixed> $validated */
    private function numberOfDays(array $validated): int
    {
        return Carbon::parse($validated['date_start'])
            ->diffInDays(Carbon::parse($validated['date_end'])) + 1;
    }

    /** @return array<string, mixed> */
    private function publicationPayload(EventEdition $edition): array
    {
        $editionId = (int) $edition->id;

        $teams = EventEditionTeam::where('edition_id', $editionId)->count();
        $players = EventEditionTeamPlayer::where('edition_id', $editionId)->count();
        $matches = EventEditionMatch::where('edition_id', $editionId)->count();
        $results = EventEditionMatch::where('edition_id', $editionId)
            ->whereNotNull('score_h')
            ->whereNotNull('score_a')
            ->count();
        $pendingReports = EventEditionMatch::where('edition_id', $editionId)
            ->whereNotNull('score_h')
            ->whereNotNull('score_a')
            ->whereDoesntHave('report')
            ->count();

        $hasContact = filled($edition->contact_name)
            || filled($edition->contact_phone)
            || filled($edition->contact_whatsapp);

        return [
            'id' => $editionId,
            'name' => $edition->name,
            'event_title' => $edition->evento?->title ?? '',
            'landing_published' => (bool) $edition->landing_published,
            'mobile_enabled' => (bool) $edition->mobile_enabled,
            'public_slug' => $edition->public_slug,
            'landing_url' => $edition->landingUrl(),
            'updated_at' => $edition->updated_at?->toIso8601String(),
            'stats' => [
                'teams' => $teams,
                'players' => $players,
                'matches' => $matches,
                'results' => $results,
                'pending_reports' => $pendingReports,
            ],
            'checklist' => [
                [
                    'key' => 'teams',
                    'label' => 'Equipos inscritos',
                    'ok' => $teams >= 2,
                    'detail' => $teams >= 2
                        ? $teams.' equipos inscritos'
                        : 'Se necesitan al menos 2 equipos para mostrar la tabla.',
                ],
                [
                    'key' => 'fixture',
                    'label' => 'Calendario de partidos',
                    'ok' => $matches > 0,
                    'detail' => $matches > 0
                        ? $matches.' partidos programados'
                        : 'Genera el fixture para publicar fechas y horarios.',
                ],
                [
                    'key' => 'contact',
                    'label' => 'Datos de contacto',
                    'ok' => $hasContact,
                    'detail' => $hasContact
                        ? 'Los participantes saben a quién escribir.'
                        : 'Agrega un teléfono o WhatsApp de contacto.',
                ],
                [
                    'key' => 'slug',
                    'label' => 'Enlace público',
                    'ok' => filled($edition->public_slug),
                    'detail' => filled($edition->public_slug)
                        ? '/torneos/'.$edition->public_slug
                        : 'Se generará desde el nombre al guardar.',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function eventPayload(EvenEvent $event): array
    {
        return [
            'id' => (int) $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'date_start' => $event->date_start,
            'date_end' => $event->date_end,
            'tickets_quantity' => (int) $event->tickets_quantity,
            'broadcast' => (bool) $event->broadcast,
            'status' => $event->status,
        ];
    }

    /** @return array<string, mixed> */
    private function editionPayload(EventEdition $edition): array
    {
        return [
            'id' => (int) $edition->id,
            'event_id' => (int) $edition->event_id,
            'year' => (string) $edition->year,
            'name' => $edition->name,
            'start_date' => $edition->start_date,
            'end_date' => $edition->end_date,
            'competition_format' => $edition->competition_format,
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
            'landing_published' => (bool) $edition->landing_published,
            'mobile_enabled' => (bool) $edition->mobile_enabled,
            'public_slug' => $edition->public_slug,
            'prize_details' => $edition->prize_details,
        ];
    }
}
