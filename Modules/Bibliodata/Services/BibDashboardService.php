<?php

namespace Modules\Bibliodata\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Bibliodata\Entities\BibBook;
use Modules\Bibliodata\Entities\BibOrganization;
use Modules\Bibliodata\Entities\BibOrganizationUser;
use Modules\Bibliodata\Entities\BibSubscription;
use Modules\Bibliodata\Entities\BibSubscriptionPlan;
use Spatie\Permission\Models\Role;

class BibDashboardService
{
    public function getDashboardData(): array
    {
        $today = Carbon::today();
        $nextThirtyDays = $today->copy()->addDays(30);

        $subscriptionsReady = $this->subscriptionsSchemaReady();

        return [
            'subscriptionsSchemaReady' => $subscriptionsReady,
            'metrics' => $this->metrics($today, $nextThirtyDays, $subscriptionsReady),
            'charts' => $this->charts($today, $subscriptionsReady),
            'tables' => $this->tables($today, $nextThirtyDays, $subscriptionsReady),
        ];
    }

    private function subscriptionsSchemaReady(): bool
    {
        return Schema::hasTable('bib_subscriptions')
            && Schema::hasTable('bib_subscription_plans')
            && Schema::hasTable('bib_organizations');
    }

    private function emptySubscriptionMetrics(): array
    {
        return [
            'activeSubscriptions' => 0,
            'expiringSubscriptions' => 0,
            'orgWithoutBeneficiaries' => 0,
            'stalePendingSubscriptions' => 0,
        ];
    }

    private function emptySubscriptionCharts(): array
    {
        return [
            'newSubscriptions' => ['labels' => [], 'values' => []],
            'subscriptionStatus' => [],
            'subscriberTypes' => [],
        ];
    }

    private function emptySubscriptionTables(): array
    {
        return [
            'expiringSubscriptions' => [],
            'orgWithoutBeneficiaries' => [],
            'recentSubscriptions' => [],
            'topBooksBySubscriptions' => [],
        ];
    }

    private function metrics(Carbon $today, Carbon $nextThirtyDays, bool $subscriptionsReady): array
    {
        $readerRole = config('bibliodata.reader.role', 'Lector');
        $readerRoleExists = Role::where('name', $readerRole)->exists();

        $subscriptionMetrics = $subscriptionsReady
            ? $this->subscriptionMetrics($today, $nextThirtyDays)
            : $this->emptySubscriptionMetrics();

        $readersCount = $readerRoleExists
            ? User::role($readerRole)->count()
            : 0;

        $readersInOrganizations = Schema::hasTable('bib_organization_users')
            ? (int) BibOrganizationUser::query()->distinct('user_id')->count('user_id')
            : 0;

        return array_merge($subscriptionMetrics, [
            'readers' => $readersCount,
            'readersInOrganizations' => $readersInOrganizations,
            'activeOrganizations' => Schema::hasTable('bib_organizations')
                ? BibOrganization::where('status', 'active')->count()
                : 0,
            'organizationMembers' => Schema::hasTable('bib_organization_users')
                ? BibOrganizationUser::count()
                : 0,
            'availableBooks' => BibBook::where('status', 'available')->count(),
            'activePlans' => Schema::hasTable('bib_subscription_plans')
                ? BibSubscriptionPlan::where('is_active', true)->count()
                : 0,
        ]);
    }

    private function subscriptionMetrics(Carbon $today, Carbon $nextThirtyDays): array
    {
        $activeSubscriptions = BibSubscription::query()
            ->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
            });

        $expiringSubscriptions = (clone $activeSubscriptions)
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '>=', $today)
            ->whereDate('ends_at', '<=', $nextThirtyDays)
            ->count();

        return [
            'activeSubscriptions' => (clone $activeSubscriptions)->count(),
            'expiringSubscriptions' => $expiringSubscriptions,
            'orgWithoutBeneficiaries' => $this->orgWithoutBeneficiariesQuery()->count(),
            'stalePendingSubscriptions' => BibSubscription::where('status', 'pending')
                ->where('created_at', '<', $today->copy()->subDays(7))
                ->count(),
        ];
    }

    private function charts(Carbon $today, bool $subscriptionsReady): array
    {
        if (! $subscriptionsReady) {
            return $this->emptySubscriptionCharts();
        }

        $monthLabels = [];
        $newSubscriptions = [];

        for ($index = 5; $index >= 0; $index--) {
            $date = $today->copy()->subMonths($index);
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $monthLabels[] = $date->locale('es')->isoFormat('MMM');
            $newSubscriptions[] = BibSubscription::whereBetween('created_at', [$start, $end])->count();
        }

        $statusCounts = [
            'pending' => BibSubscription::where('status', 'pending')->count(),
            'cancelled' => BibSubscription::where('status', 'cancelled')->count(),
            'suspended' => BibSubscription::where('status', 'suspended')->count(),
            'expired' => BibSubscription::where('status', 'expired')->count()
                + BibSubscription::where('status', 'active')
                    ->whereNotNull('ends_at')
                    ->whereDate('ends_at', '<', $today)
                    ->count(),
            'active' => BibSubscription::where('status', 'active')
                ->where(function ($q) use ($today) {
                    $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
                })
                ->count(),
        ];

        $statusLabels = [
            'pending' => 'Pendiente',
            'active' => 'Activa',
            'expired' => 'Expirada',
            'cancelled' => 'Cancelada',
            'suspended' => 'Suspendida',
        ];

        $subscriptionStatus = collect($statusCounts)
            ->filter(fn ($count) => $count > 0)
            ->map(fn ($count, $key) => [
                'label' => $statusLabels[$key] ?? $key,
                'value' => $count,
            ])
            ->values()
            ->all();

        $subscriberTypeLabels = [
            'individual' => 'Individual',
            'organization' => 'Organización',
        ];

        $subscriberTypes = BibSubscription::selectRaw('subscriber_type, COUNT(*) as total')
            ->groupBy('subscriber_type')
            ->get()
            ->map(fn ($item) => [
                'label' => $subscriberTypeLabels[$item->subscriber_type] ?? $item->subscriber_type,
                'value' => (int) $item->total,
            ])
            ->values()
            ->all();

        return [
            'newSubscriptions' => [
                'labels' => $monthLabels,
                'values' => $newSubscriptions,
            ],
            'subscriptionStatus' => $subscriptionStatus,
            'subscriberTypes' => $subscriberTypes,
        ];
    }

    private function tables(Carbon $today, Carbon $nextThirtyDays, bool $subscriptionsReady): array
    {
        if (! $subscriptionsReady) {
            return $this->emptySubscriptionTables();
        }

        $expiringSubscriptions = BibSubscription::with(['plan', 'user', 'organization', 'book'])
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '>=', $today)
            ->whereDate('ends_at', '<=', $nextThirtyDays)
            ->orderBy('ends_at')
            ->limit(8)
            ->get()
            ->map(fn (BibSubscription $sub) => $this->subscriptionRow($sub, $today));

        $orgWithoutBeneficiaries = $this->orgWithoutBeneficiariesQuery()
            ->with(['plan', 'organization', 'book'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (BibSubscription $sub) => $this->subscriptionRow($sub, $today));

        $recentSubscriptions = BibSubscription::with(['plan', 'user', 'organization', 'book'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (BibSubscription $sub) => $this->subscriptionRow($sub, $today));

        $topBooks = BibSubscription::query()
            ->select('book_id', DB::raw('COUNT(*) as total'))
            ->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
            })
            ->whereNotNull('book_id')
            ->groupBy('book_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $bookIds = $topBooks->pluck('book_id')->filter()->all();
        $booksById = BibBook::whereIn('id', $bookIds)->get()->keyBy('id');

        $topBooksBySubscriptions = $topBooks->map(function ($row) use ($booksById) {
            $book = $booksById->get($row->book_id);

            return [
                'book_id' => $row->book_id,
                'title' => $book?->title ?? 'Libro',
                'code_name' => $book?->code_name,
                'total' => (int) $row->total,
            ];
        })->values()->all();

        return [
            'expiringSubscriptions' => $expiringSubscriptions,
            'orgWithoutBeneficiaries' => $orgWithoutBeneficiaries,
            'recentSubscriptions' => $recentSubscriptions,
            'topBooksBySubscriptions' => $topBooksBySubscriptions,
        ];
    }

    private function orgWithoutBeneficiariesQuery()
    {
        $query = BibSubscription::query()
            ->where('subscriber_type', 'organization')
            ->whereIn('status', ['active', 'pending']);

        if (Schema::hasTable('bib_subscription_beneficiaries')) {
            $query->whereDoesntHave('beneficiaries');
        }

        return $query;
    }

    private function subscriptionRow(BibSubscription $sub, Carbon $today): array
    {
        $endsAt = $sub->ends_at ? Carbon::parse($sub->ends_at) : null;

        return [
            'id' => $sub->id,
            'subscriber_type' => $sub->subscriber_type,
            'subscriber' => $sub->subscriber_type === 'individual'
                ? ($sub->user?->name ?? '—')
                : ($sub->organization?->name ?? '—'),
            'plan' => $sub->plan?->name,
            'book' => $sub->book?->title,
            'status' => $sub->status,
            'ends_at' => $endsAt?->format('Y-m-d'),
            'days_left' => $endsAt ? $today->diffInDays($endsAt, false) : null,
            'created_at' => $sub->created_at?->format('Y-m-d'),
        ];
    }
}
