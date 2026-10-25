<?php

namespace Modules\Socialevents\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionLandingView;
use Modules\Socialevents\Services\SportsOrganizationService;

class EventEditionLandingVisitController extends Controller
{
    public function show(Request $request, int $id): JsonResponse
    {
        $sports = app(SportsOrganizationService::class);
        $sports->assertCanRead($request->user());
        $sports->assertOwnedEdition($request->user(), $id);

        $edition = EventEdition::query()->with('evento')->findOrFail($id);
        $query = EventEditionLandingView::query()->where('edition_id', $edition->id);

        $totals = [
            'views' => (clone $query)->count(),
            'unique_visitors' => (clone $query)->distinct()->count('visitor_hash'),
            'today' => (clone $query)->whereDate('viewed_at', now()->toDateString())->count(),
            'last_7_days' => (clone $query)->where('viewed_at', '>=', now()->subDays(6)->startOfDay())->count(),
            'last_30_days' => (clone $query)->where('viewed_at', '>=', now()->subDays(29)->startOfDay())->count(),
            'last_visit_at' => (clone $query)->max('viewed_at'),
            'counter' => (int) $edition->landing_views,
        ];

        $recent = (clone $query)
            ->orderByDesc('viewed_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->map(fn (EventEditionLandingView $visit) => $this->visitPayload($visit))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Visitas de la landing obtenidas correctamente.',
            'data' => [
                'edition' => [
                    'id' => (int) $edition->id,
                    'name' => (string) $edition->name,
                    'event' => $edition->evento?->title,
                    'landing_published' => (bool) $edition->landing_published,
                    'landing_url' => $edition->landingUrl(),
                ],
                'totals' => $totals,
                'breakdowns' => [
                    'sources' => $this->breakdown($query, 'source'),
                    'cities' => $this->breakdown($query, 'city'),
                    'countries' => $this->breakdown($query, 'country'),
                    'browsers' => $this->breakdown($query, 'browser'),
                    'platforms' => $this->breakdown($query, 'platform'),
                    'devices' => $this->breakdown($query, 'device_type'),
                    'referrers' => $this->breakdown($query, 'referrer_host'),
                ],
                'recent' => $recent,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, total: int}>
     */
    private function breakdown(Builder $query, string $column): array
    {
        return (clone $query)
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->{$column},
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function visitPayload(EventEditionLandingView $visit): array
    {
        $location = implode(', ', array_filter([
            $visit->city,
            $visit->region,
            $visit->country,
        ], fn ($value) => filled($value)));

        $browser = trim(implode(' ', array_filter([
            $visit->browser,
            $visit->browser_version,
        ], fn ($value) => filled($value))));

        return [
            'id' => (int) $visit->id,
            'viewed_at' => optional($visit->viewed_at)->toIso8601String(),
            'viewed_at_label' => optional($visit->viewed_at)->format('d/m/Y H:i'),
            'source' => $visit->source ?: 'Directo',
            'referrer_host' => $visit->referrer_host,
            'referrer' => $visit->referrer,
            'city' => $visit->city,
            'region' => $visit->region,
            'country' => $visit->country,
            'country_code' => $visit->country_code,
            'location' => $location !== '' ? $location : null,
            'browser' => $browser !== '' ? $browser : null,
            'platform' => $visit->platform,
            'device_type' => $visit->device_type,
            'device_label' => $this->deviceLabel($visit->device_type),
            'visitor' => mb_substr((string) $visit->visitor_hash, 0, 8),
        ];
    }

    private function deviceLabel(?string $deviceType): string
    {
        return match ($deviceType) {
            'mobile' => 'Móvil',
            'tablet' => 'Tablet',
            'desktop' => 'Computadora',
            default => 'Sin identificar',
        };
    }
}
