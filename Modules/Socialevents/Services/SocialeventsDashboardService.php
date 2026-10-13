<?php

namespace Modules\Socialevents\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EvenEventTicketClient;
use Modules\Socialevents\Entities\EvenLocalRental;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMatchReport;
use Modules\Socialevents\Entities\EventTeam;

class SocialeventsDashboardService
{
    private function organizationFilter($query, string $relation = 'evento')
    {
        $sports = app(SportsOrganizationService::class);
        $user = Auth::user();
        if ($sports->sportsMode() && ! $sports->isSuperAdmin($user)) {
            $query->whereHas($relation, fn ($q) => $q->where('organization_id', $sports->organizationId($user)));
        }
        return $query;
    }

    public function build(): array
    {
        $user = Auth::user();
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead($user);
        $canSports = $sports->sportsMode()
            ? ($user && ($user->can('even_evento_listado') || $sports->isSuperAdmin($user)))
            : ($user && $user->can('even_ediciones_listado'));
        $canRentals = $user && $user->can('even_alquiler_local_listado');
        $organizationId = $sports->organizationId($user);
        $sportsModule = $canSports ? $this->buildSportsSection() : $this->emptySportsSection();
        if ($canSports && $sports->sportsMode() && ! $sports->isSuperAdmin($user)) {
            $sportsModule['recent_editions'] = $this->recentEditions(['pending', 'in_progress']);
            $sportsModule['metrics']['active_editions'] = EventEdition::whereHas('evento', fn ($q) => $q->where('organization_id', $organizationId))->whereIn('status', ['pending', 'in_progress'])->count();
        }
        $rentalsModule = $canRentals && ! $sports->sportsMode() ? $this->buildRentalSection() : $this->emptyRentalSection();
        $payload = [
            'sportsMode' => $sports->sportsMode(),
            'modules' => ['sports' => $sportsModule, 'rentals' => $rentalsModule],
            'event_types' => $this->filteredEventTypes(),
            'active_module' => config('socialevents.dashboard_active_module', 'sports'),
            'quick_links' => $this->quickLinks(),
        ];
        if ($canSports) {
            $payload['metrics'] = $sportsModule['metrics'];
            $payload['recent_editions'] = $sportsModule['recent_editions'];
            $payload['matches_today_list'] = $sportsModule['matches_today_list'];
            $payload['upcoming_matches'] = $sportsModule['upcoming_matches'];
            $payload['integration'] = $sportsModule['integration'];
        } else {
            $payload['metrics'] = [];
            $payload['recent_editions'] = [];
            $payload['matches_today_list'] = [];
            $payload['upcoming_matches'] = [];
            $payload['integration'] = null;
        }
        $payload['rental_module'] = $rentalsModule;
        return $payload;
    }

    private function buildSportsSection(): array
    {
        $sports = app(SportsOrganizationService::class);
        $activeStatuses = ['pending', 'in_progress'];
        $baseEditions = fn () => $this->organizationFilter(
            EventEdition::query()->whereIn('status', $activeStatuses)
        );
        $activeEditionIds = (clone $baseEditions())->pluck('id');
        $today = Carbon::today();
        $endOfToday = Carbon::today()->endOfDay();
        $matchesToday = EventEditionMatch::query()->whereIn('edition_id', $activeEditionIds)
            ->whereNotNull('match_date')->whereBetween('match_date', [$today, $endOfToday])->count();
        $matchesNeedingResult = EventEditionMatch::query()->whereIn('edition_id', $activeEditionIds)
            ->where('status', 'pending')->whereNotNull('team_h_id')->whereNotNull('team_a_id')
            ->where(fn ($q) => $q->where('match_date', '<=', $endOfToday)->orWhereNull('match_date'))->count();
        $pendingProtests = EventEditionMatchReport::query()->where('protest_status', 'pending')
            ->whereIn('match_id', EventEditionMatch::query()->select('id')->whereIn('edition_id', $activeEditionIds))->count();
        $user = Auth::user();
        return [
            'visible' => true,
            'metrics' => [
                'active_editions' => (clone $baseEditions())->count(),
                'published_landings' => (clone $baseEditions())->where('landing_published', true)->count(),
                'mobile_enabled_editions' => (clone $baseEditions())->where('mobile_enabled', true)->count(),
                'total_events' => $user?->can('even_evento_listado')
                    ? EvenEvent::query()->when($sports->sportsMode() && ! $sports->isSuperAdmin($user), fn ($query) => $query->where('organization_id', $sports->organizationId($user)))->count()
                    : 0,
                'total_teams' => $user?->can('even_equipos_listado')
                    ? EventTeam::query()->when($sports->sportsMode() && ! $sports->isSuperAdmin($user), fn ($query) => $query->where('organization_id', $sports->organizationId($user)))->count()
                    : 0,
                'matches_today' => $matchesToday,
                'matches_needing_result' => $matchesNeedingResult,
                'pending_protests' => $pendingProtests,
                'tickets_sold_month' => $user?->can('even_ventas_listado') && ! app(SportsOrganizationService::class)->sportsMode() ? EvenEventTicketClient::where('created_at', '>=', Carbon::now()->startOfMonth())->count() : 0,
                'tickets_sold_total' => $user?->can('even_ventas_listado') && ! app(SportsOrganizationService::class)->sportsMode() ? EvenEventTicketClient::count() : 0,
            ],
            'recent_editions' => $this->recentEditions($activeStatuses),
            'matches_today_list' => $this->matchesTodayList($activeEditionIds),
            'upcoming_matches' => $this->upcomingMatches($activeEditionIds),
            'integration' => $sports->sportsMode() && ! $sports->isSuperAdmin($user) ? null : $this->integrationSnapshot($activeStatuses),
        ];
    }

    private function emptySportsSection(): array
    {
        return ['visible' => false, 'metrics' => [], 'recent_editions' => [], 'matches_today_list' => [], 'upcoming_matches' => [], 'integration' => null];
    }

    private function buildRentalSection(): array
    {
        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth();
        $active = EvenLocalRental::query()->where('reservation_status', '!=', 'cancelled');
        $eventsToday = (clone $active)->where(fn ($q) => $q->whereDate('event_date', $today)->orWhereDate('event_end_date', $today))->count();
        $pendingBalance = (clone $active)->where('balance_amount', '>', 0)->sum('balance_amount');
        $advancePending = EvenLocalRental::query()->where('reservation_status', '!=', 'cancelled')->where('paid_amount', '>', 0)
            ->whereRaw('paid_amount > COALESCE((SELECT SUM(amount) FROM even_local_rental_payments WHERE rental_id = even_local_rentals.id), 0)')->count();
        return [
            'visible' => true,
            'metrics' => [
                'active_reservations' => (clone $active)->count(), 'events_today' => $eventsToday,
                'in_occupation' => (clone $active)->where('reservation_status', 'in_occupation')->count(),
                'pending_balance' => round((float) $pendingBalance, 2),
                'reservations_month' => EvenLocalRental::where('created_at', '>=', $startOfMonth)->count(),
                'advance_pending_formalization' => $advancePending,
            ],
            'recent_rentals' => $this->recentRentals(), 'upcoming_rentals' => $this->upcomingRentals(),
        ];
    }

    private function emptyRentalSection(): array
    {
        return ['visible' => false, 'metrics' => [], 'recent_rentals' => [], 'upcoming_rentals' => []];
    }

    private function recentRentals(): array
    {
        return EvenLocalRental::with(['local', 'customer'])->latest('created_at')->latest('id')->limit(6)->get()
            ->map(fn (EvenLocalRental $rental) => $this->serializeRentalRow($rental))->all();
    }

    private function upcomingRentals(): array
    {
        return EvenLocalRental::with(['local', 'customer'])->whereIn('reservation_status', ['pending', 'confirmed', 'in_occupation'])
            ->whereDate('event_date', '>=', Carbon::today()->toDateString())->orderBy('event_date')->orderBy('start_time')->limit(6)->get()
            ->map(fn (EvenLocalRental $rental) => $this->serializeRentalRow($rental))->all();
    }

    private function serializeRentalRow(EvenLocalRental $rental): array
    {
        $start = $rental->event_date?->format('d/m/Y') ?? '-';
        $end = ($rental->event_end_date ?? $rental->event_date)?->format('d/m/Y') ?? $start;
        $startTime = $rental->start_time ? substr((string) $rental->start_time, 0, 5) : '';
        $endTime = $rental->end_time ? substr((string) $rental->end_time, 0, 5) : '';
        return [
            'id' => $rental->id, 'local_name' => $rental->local?->description ?? '-', 'customer_name' => $rental->customer?->full_name ?? '-',
            'event_date' => $rental->event_date?->format('Y-m-d'), 'event_date_label' => $start,
            'schedule_label' => trim($start === $end ? "{$start} {$startTime} - {$endTime}" : "{$start} {$startTime} → {$end} {$endTime}"),
            'total_price' => (float) $rental->total_price, 'paid_amount' => (float) $rental->paid_amount,
            'balance_amount' => (float) $rental->balance_amount, 'reservation_status' => $rental->reservation_status,
            'payment_status' => $rental->payment_status, 'show_url' => route('even_alquiler_local_show', $rental->id),
        ];
    }

    private function filteredEventTypes(): array
    {
        $user = Auth::user();
        return collect(config('socialevents.event_types', []))->filter(fn (array $type) => empty($type['permission']) || ($user && $user->can($type['permission'])))->values()->all();
    }

    private function recentEditions(array $statuses): array
    {
        return $this->organizationFilter(EventEdition::query()->with(['evento'])->withCount('equipos')->whereIn('status', $statuses))
            ->orderByDesc('updated_at')->limit(6)->get()->map(fn (EventEdition $edition) => [
                'id' => $edition->id, 'name' => $edition->name, 'year' => $edition->year, 'status' => $edition->status,
                'event_title' => $edition->evento?->title, 'teams_count' => $edition->equipos_count,
                'landing_published' => (bool) $edition->landing_published, 'mobile_enabled' => (bool) $edition->mobile_enabled,
                'landing_url' => $edition->landing_published ? $edition->landingUrl() : null,
                'api_public_url' => url("/api/socialevents/v1/edition/{$edition->id}/public"),
                'edit_url' => route('even_ediciones_editar', $edition->id), 'fixtures_url' => route('even_ediciones_fixtures', $edition->id),
            ])->all();
    }

    private function matchesTodayList($editionIds): array
    {
        if ($editionIds->isEmpty()) return [];
        return EventEditionMatch::with(['equipolocal', 'equipovisitante', 'edicion.evento'])->whereIn('edition_id', $editionIds)
            ->whereNotNull('match_date')->whereBetween('match_date', [Carbon::today(), Carbon::today()->endOfDay()])->orderBy('match_date')->limit(8)
            ->get()->map(fn (EventEditionMatch $match) => $this->serializeMatchRow($match))->all();
    }

    private function upcomingMatches($editionIds): array
    {
        if ($editionIds->isEmpty()) return [];
        return EventEditionMatch::with(['equipolocal', 'equipovisitante', 'edicion.evento'])->whereIn('edition_id', $editionIds)
            ->where('status', 'pending')->where('match_date', '>', Carbon::now())->orderBy('match_date')->limit(6)
            ->get()->map(fn (EventEditionMatch $match) => $this->serializeMatchRow($match))->all();
    }

    private function integrationSnapshot(array $statuses): ?array
    {
        $edition = $this->organizationFilter(EventEdition::query()->whereIn('status', $statuses)->where('mobile_enabled', true))->orderByDesc('updated_at')->first()
            ?? $this->organizationFilter(EventEdition::query()->whereIn('status', $statuses))->orderByDesc('updated_at')->first();
        if (! $edition) return null;
        return [
            'edition_id' => $edition->id, 'edition_name' => $edition->name,
            'landing_url' => $edition->landing_published ? $edition->landingUrl() : null,
            'api_public_url' => url("/api/socialevents/v1/edition/{$edition->id}/public"),
            'mobile_enabled' => (bool) $edition->mobile_enabled, 'landing_published' => (bool) $edition->landing_published,
            'app_base_hint' => rtrim((string) config('app.url'), '/'),
        ];
    }

    private function quickLinks(): array
    {
        $sportsMode = app(SportsOrganizationService::class)->sportsMode();
        $links = [
            ['label' => 'Nueva edición', 'route' => 'even_ediciones_nuevo', 'permission' => 'even_ediciones_nuevo'],
            ['label' => 'Ediciones', 'route' => 'even_ediciones_listado', 'permission' => 'even_ediciones_listado'],
            ['label' => 'Eventos', 'route' => 'even_eventos_list', 'permission' => 'even_evento_listado'],
            ['label' => 'Equipos', 'route' => 'even_equipos_listado', 'permission' => 'even_equipos_listado'],
            ['label' => 'Tickets vendidos', 'route' => 'even_tickets_listado', 'permission' => 'even_ventas_listado'],
            ['label' => 'Alquiler de local', 'route' => 'even_alquiler_local_index', 'permission' => 'even_alquiler_local_listado'],
            ['label' => 'Nueva reserva', 'route' => 'even_alquiler_local_create', 'permission' => 'even_alquiler_local_nuevo'],
        ];
        return collect($links)->filter(fn ($link) => ! $sportsMode || ! in_array($link['permission'], ['even_ventas_listado', 'even_alquiler_local_listado', 'even_alquiler_local_nuevo'], true))->values()->all();
    }

    private function serializeMatchRow(EventEditionMatch $match): array
    {
        $edition = $match->edicion;
        return [
            'id' => $match->id, 'edition_id' => $match->edition_id, 'edition_name' => $edition?->name,
            'event_title' => $edition?->evento?->title, 'team_h' => $match->equipolocal?->name ?? $match->placeholder_h ?? 'Local',
            'team_a' => $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Visitante',
            'match_date' => $match->match_date?->format('Y-m-d H:i'), 'match_date_label' => $match->match_date?->format('d/m/Y H:i') ?? 'Sin fecha',
            'status' => $match->status, 'location' => $match->location,
            'fixtures_url' => $edition ? route('even_ediciones_fixtures', $edition->id) : null,
        ];
    }
}
