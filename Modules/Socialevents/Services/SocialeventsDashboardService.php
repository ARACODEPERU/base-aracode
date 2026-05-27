<?php

namespace Modules\Socialevents\Services;

use Carbon\Carbon;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EvenEventTicketClient;
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

        $metrics = [
            'active_editions' => (clone $activeEditionsQuery)->count(),
            'published_landings' => (clone $activeEditionsQuery)->where('landing_published', true)->count(),
            'mobile_enabled_editions' => (clone $activeEditionsQuery)->where('mobile_enabled', true)->count(),
            'total_events' => EvenEvent::query()->count(),
            'total_teams' => EventTeam::query()->count(),
            'matches_today' => $matchesToday,
            'matches_needing_result' => $matchesNeedingResult,
            'pending_protests' => $pendingProtests,
            'tickets_sold_month' => EvenEventTicketClient::query()
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count(),
            'tickets_sold_total' => EvenEventTicketClient::query()->count(),
        ];

        return [
            'event_types' => config('socialevents.event_types', []),
            'active_module' => config('socialevents.dashboard_active_module', 'sports'),
            'metrics' => $metrics,
            'recent_editions' => $this->recentEditions($activeEditionStatuses),
            'matches_today_list' => $this->matchesTodayList($activeEditionIds),
            'upcoming_matches' => $this->upcomingMatches($activeEditionIds),
            'integration' => $this->integrationSnapshot($activeEditionStatuses),
            'quick_links' => $this->quickLinks(),
        ];
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
