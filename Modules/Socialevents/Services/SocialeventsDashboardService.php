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
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $user = Auth::user();
        $canSports = $user && $user->can('even_ediciones_listado');
        $canRentals = $user && $user->can('even_alquiler_local_listado');

        $sportsModule = $canSports ? $this->buildSportsSection() : $this->emptySportsSection();
        $rentalsModule = $canRentals ? $this->buildRentalSection() : $this->emptyRentalSection();

        $payload = [
            'modules' => [
                'sports' => $sportsModule,
                'rentals' => $rentalsModule,
            ],
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

    /**
     * @return array<string, mixed>
     */
    private function buildSportsSection(): array
    {
        $activeEditionStatuses = ['pending', 'in_progress'];

        $activeEditionsQuery = EventEdition::query()
            ->whereIn('status', $activeEditionStatuses);

        $activeEditionIds = (clone $activeEditionsQuery)->pluck('id');

        $today = Carbon::today();
        $endOfToday = Carbon::today()->endOfDay();

        $matchesToday = EventEditionMatch::query()
            ->when($activeEditionIds->isNotEmpty(), fn ($q) => $q->whereIn('edition_id', $activeEditionIds))
            ->when($activeEditionIds->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'))
            ->whereNotNull('match_date')
            ->whereBetween('match_date', [$today, $endOfToday])
            ->count();

        $matchesNeedingResult = EventEditionMatch::query()
            ->when($activeEditionIds->isNotEmpty(), fn ($q) => $q->whereIn('edition_id', $activeEditionIds))
            ->when($activeEditionIds->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'))
            ->where('status', 'pending')
            ->whereNotNull('team_h_id')
            ->whereNotNull('team_a_id')
            ->where(function ($q) use ($endOfToday) {
                $q->where('match_date', '<=', $endOfToday)
                    ->orWhereNull('match_date');
            })
            ->count();

        $pendingProtests = EventEditionMatchReport::query()
            ->where('protest_status', 'pending')
            ->when($activeEditionIds->isNotEmpty(), function ($q) use ($activeEditionIds) {
                $q->whereIn('match_id', function ($sub) use ($activeEditionIds) {
                    $sub->select('id')
                        ->from('event_edition_matches')
                        ->whereIn('edition_id', $activeEditionIds);
                });
            })
            ->when($activeEditionIds->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'))
            ->count();

        $user = Auth::user();

        return [
            'visible' => true,
            'metrics' => [
                'active_editions' => (clone $activeEditionsQuery)->count(),
                'published_landings' => (clone $activeEditionsQuery)->where('landing_published', true)->count(),
                'mobile_enabled_editions' => (clone $activeEditionsQuery)->where('mobile_enabled', true)->count(),
                'total_events' => $user?->can('even_evento_listado') ? EvenEvent::query()->count() : 0,
                'total_teams' => $user?->can('even_equipos_listado') ? EventTeam::query()->count() : 0,
                'matches_today' => $matchesToday,
                'matches_needing_result' => $matchesNeedingResult,
                'pending_protests' => $pendingProtests,
                'tickets_sold_month' => $user?->can('even_ventas_listado')
                    ? EvenEventTicketClient::query()->where('created_at', '>=', Carbon::now()->startOfMonth())->count()
                    : 0,
                'tickets_sold_total' => $user?->can('even_ventas_listado')
                    ? EvenEventTicketClient::query()->count()
                    : 0,
            ],
            'recent_editions' => $this->recentEditions($activeEditionStatuses),
            'matches_today_list' => $this->matchesTodayList($activeEditionIds),
            'upcoming_matches' => $this->upcomingMatches($activeEditionIds),
            'integration' => $this->integrationSnapshot($activeEditionStatuses),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySportsSection(): array
    {
        return [
            'visible' => false,
            'metrics' => [],
            'recent_editions' => [],
            'matches_today_list' => [],
            'upcoming_matches' => [],
            'integration' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRentalSection(): array
    {
        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth();

        $activeQuery = EvenLocalRental::query()->where('reservation_status', '!=', 'cancelled');

        $eventsToday = (clone $activeQuery)
            ->where(function ($q) use ($today) {
                $q->whereDate('event_date', $today)
                    ->orWhereDate('event_end_date', $today);
            })
            ->count();

        $pendingBalance = (clone $activeQuery)
            ->where('balance_amount', '>', 0)
            ->sum('balance_amount');

        $advancePendingFormalization = EvenLocalRental::query()
            ->where('reservation_status', '!=', 'cancelled')
            ->where('paid_amount', '>', 0)
            ->whereRaw(
                'paid_amount > COALESCE((SELECT SUM(amount) FROM even_local_rental_payments WHERE rental_id = even_local_rentals.id), 0)'
            )
            ->count();

        return [
            'visible' => true,
            'metrics' => [
                'active_reservations' => (clone $activeQuery)->count(),
                'events_today' => $eventsToday,
                'in_occupation' => (clone $activeQuery)->where('reservation_status', 'in_occupation')->count(),
                'pending_balance' => round((float) $pendingBalance, 2),
                'reservations_month' => EvenLocalRental::query()
                    ->where('created_at', '>=', $startOfMonth)
                    ->count(),
                'advance_pending_formalization' => $advancePendingFormalization,
            ],
            'recent_rentals' => $this->recentRentals(),
            'upcoming_rentals' => $this->upcomingRentals(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyRentalSection(): array
    {
        return [
            'visible' => false,
            'metrics' => [],
            'recent_rentals' => [],
            'upcoming_rentals' => [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentRentals(): array
    {
        return EvenLocalRental::query()
            ->with(['local', 'customer'])
            ->latest('created_at')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (EvenLocalRental $rental) => $this->serializeRentalRow($rental))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function upcomingRentals(): array
    {
        $today = Carbon::today()->toDateString();
        $activeStatuses = ['pending', 'confirmed', 'in_occupation'];

        return EvenLocalRental::query()
            ->with(['local', 'customer'])
            ->whereIn('reservation_status', $activeStatuses)
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->limit(6)
            ->get()
            ->map(fn (EvenLocalRental $rental) => $this->serializeRentalRow($rental))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRentalRow(EvenLocalRental $rental): array
    {
        $startTime = $rental->start_time ? substr((string) $rental->start_time, 0, 5) : '';
        $endTime = $rental->end_time ? substr((string) $rental->end_time, 0, 5) : '';
        $eventDate = $rental->event_date?->format('d/m/Y') ?? '-';
        $endDate = ($rental->event_end_date ?? $rental->event_date)?->format('d/m/Y') ?? $eventDate;

        $schedule = $eventDate === $endDate
            ? "{$eventDate} {$startTime} - {$endTime}"
            : "{$eventDate} {$startTime} → {$endDate} {$endTime}";

        return [
            'id' => $rental->id,
            'local_name' => $rental->local?->description ?? '-',
            'customer_name' => $rental->customer?->full_name ?? '-',
            'event_date' => $rental->event_date?->format('Y-m-d'),
            'event_date_label' => $eventDate,
            'schedule_label' => trim($schedule),
            'total_price' => (float) $rental->total_price,
            'paid_amount' => (float) $rental->paid_amount,
            'balance_amount' => (float) $rental->balance_amount,
            'reservation_status' => $rental->reservation_status,
            'payment_status' => $rental->payment_status,
            'show_url' => route('even_alquiler_local_show', $rental->id),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filteredEventTypes(): array
    {
        $user = Auth::user();

        return collect(config('socialevents.event_types', []))
            ->filter(function (array $type) use ($user) {
                $permission = $type['permission'] ?? null;

                if (! $permission) {
                    return true;
                }

                return $user && $user->can($permission);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<int, array<string, mixed>>
     */
    private function recentEditions(array $statuses): array
    {
        return EventEdition::query()
            ->with(['evento'])
            ->withCount('equipos')
            ->whereIn('status', $statuses)
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (EventEdition $edition) => [
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
                'status' => $edition->status,
                'event_title' => $edition->evento?->title,
                'teams_count' => $edition->equipos_count,
                'landing_published' => (bool) $edition->landing_published,
                'mobile_enabled' => (bool) $edition->mobile_enabled,
                'landing_url' => $edition->landing_published ? $edition->landingUrl() : null,
                'api_public_url' => url("/api/socialevents/v1/edition/{$edition->id}/public"),
                'edit_url' => route('even_ediciones_editar', $edition->id),
                'fixtures_url' => route('even_ediciones_fixtures', $edition->id),
            ])
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $editionIds
     * @return array<int, array<string, mixed>>
     */
    private function matchesTodayList($editionIds): array
    {
        if ($editionIds->isEmpty()) {
            return [];
        }

        $today = Carbon::today();
        $endOfToday = Carbon::today()->endOfDay();

        return EventEditionMatch::query()
            ->with(['equipolocal', 'equipovisitante', 'edicion.evento'])
            ->whereIn('edition_id', $editionIds)
            ->whereNotNull('match_date')
            ->whereBetween('match_date', [$today, $endOfToday])
            ->orderBy('match_date')
            ->limit(8)
            ->get()
            ->map(fn (EventEditionMatch $match) => $this->serializeMatchRow($match))
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $editionIds
     * @return array<int, array<string, mixed>>
     */
    private function upcomingMatches($editionIds): array
    {
        if ($editionIds->isEmpty()) {
            return [];
        }

        return EventEditionMatch::query()
            ->with(['equipolocal', 'equipovisitante', 'edicion.evento'])
            ->whereIn('edition_id', $editionIds)
            ->where('status', 'pending')
            ->where('match_date', '>', Carbon::now())
            ->orderBy('match_date')
            ->limit(6)
            ->get()
            ->map(fn (EventEditionMatch $match) => $this->serializeMatchRow($match))
            ->all();
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<string, mixed>|null
     */
    private function integrationSnapshot(array $statuses): ?array
    {
        $edition = EventEdition::query()
            ->whereIn('status', $statuses)
            ->where('mobile_enabled', true)
            ->orderByDesc('updated_at')
            ->first();

        if (! $edition) {
            $edition = EventEdition::query()
                ->whereIn('status', $statuses)
                ->orderByDesc('updated_at')
                ->first();
        }

        if (! $edition) {
            return null;
        }

        return [
            'edition_id' => $edition->id,
            'edition_name' => $edition->name,
            'landing_url' => $edition->landing_published ? $edition->landingUrl() : null,
            'api_public_url' => url("/api/socialevents/v1/edition/{$edition->id}/public"),
            'mobile_enabled' => (bool) $edition->mobile_enabled,
            'landing_published' => (bool) $edition->landing_published,
            'app_base_hint' => rtrim((string) config('app.url'), '/'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function quickLinks(): array
    {
        return [
            [
                'label' => 'Nueva edición',
                'route' => 'even_ediciones_nuevo',
                'permission' => 'even_ediciones_nuevo',
            ],
            [
                'label' => 'Ediciones',
                'route' => 'even_ediciones_listado',
                'permission' => 'even_ediciones_listado',
            ],
            [
                'label' => 'Eventos',
                'route' => 'even_eventos_list',
                'permission' => 'even_evento_listado',
            ],
            [
                'label' => 'Equipos',
                'route' => 'even_equipos_listado',
                'permission' => 'even_equipos_listado',
            ],
            [
                'label' => 'Tickets vendidos',
                'route' => 'even_tickets_listado',
                'permission' => 'even_ventas_listado',
            ],
            [
                'label' => 'Alquiler de local',
                'route' => 'even_alquiler_local_index',
                'permission' => 'even_alquiler_local_listado',
            ],
            [
                'label' => 'Nueva reserva',
                'route' => 'even_alquiler_local_create',
                'permission' => 'even_alquiler_local_nuevo',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMatchRow(EventEditionMatch $match): array
    {
        $edition = $match->edicion;

        return [
            'id' => $match->id,
            'edition_id' => $match->edition_id,
            'edition_name' => $edition?->name,
            'event_title' => $edition?->evento?->title,
            'team_h' => $match->equipolocal?->name ?? $match->placeholder_h ?? 'Local',
            'team_a' => $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Visitante',
            'match_date' => $match->match_date?->format('Y-m-d H:i'),
            'match_date_label' => $match->match_date?->format('d/m/Y H:i') ?? 'Sin fecha',
            'status' => $match->status,
            'location' => $match->location,
            'fixtures_url' => $edition ? route('even_ediciones_fixtures', $edition->id) : null,
        ];
    }
}
