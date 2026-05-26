<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AccountsReceivableDashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $creditOpenTotal = round((float) (clone $this->creditQuotasQuery())->sum('sale_document_quotas.balance'), 2);
        $installmentOpenTotal = round((float) (clone $this->installmentSchedulesQuery())->sum('sale_payment_schedules.remaining_amount'), 2);
        $portfolioTotal = round($creditOpenTotal + $installmentOpenTotal, 2);

        $overdueCredit = round((float) (clone $this->creditQuotasQuery())
            ->whereDate('sale_document_quotas.due_date', '<', $today->toDateString())
            ->sum('sale_document_quotas.balance'), 2);
        $overdueInstallments = round((float) (clone $this->installmentSchedulesQuery())
            ->whereDate('sale_payment_schedules.payment_date', '<', $today->toDateString())
            ->sum('sale_payment_schedules.remaining_amount'), 2);

        $dueSoonCredit = round((float) (clone $this->creditQuotasQuery())
            ->whereBetween('sale_document_quotas.due_date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()])
            ->sum('sale_document_quotas.balance'), 2);
        $dueSoonInstallments = round((float) (clone $this->installmentSchedulesQuery())
            ->whereBetween('sale_payment_schedules.payment_date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()])
            ->sum('sale_payment_schedules.remaining_amount'), 2);

        $recoveredThisMonth = round(
            (float) $this->recoveredCreditThisMonth($monthStart, $monthEnd)
            + (float) $this->recoveredInstallmentsThisMonth($monthStart, $monthEnd),
            2
        );

        return Inertia::render('Sales::AccountsReceivable/Dashboard', [
            'metrics' => [
                'portfolioTotal' => $portfolioTotal,
                'overdueTotal' => round($overdueCredit + $overdueInstallments, 2),
                'dueSoonTotal' => round($dueSoonCredit + $dueSoonInstallments, 2),
                'recoveredThisMonth' => $recoveredThisMonth,
                'creditOpenTotal' => $creditOpenTotal,
                'installmentOpenTotal' => $installmentOpenTotal,
                'openCasesCount' => $this->openCasesCount(),
                'overdueCasesCount' => $this->overdueCasesCount($today),
            ],
            'charts' => [
                'agingBuckets' => $this->buildAgingBucketsChart($today),
                'upcomingCollections' => $this->buildUpcomingCollectionsChart($today),
            ],
            'tables' => [
                'priorityQueue' => $this->buildPriorityQueue($today),
                'topDebtors' => $this->buildTopDebtors(),
                'recentCollections' => $this->buildRecentCollections(),
            ],
        ]);
    }

    private function canReviewAllAccounts(): bool
    {
        return Auth::user()->hasAnyRole(['admin', 'Contabilidad']);
    }

    private function applyOwnerScope($query, string $userColumn = 'sales.user_id')
    {
        if (! $this->canReviewAllAccounts()) {
            $query->where($userColumn, Auth::id());
        }

        return $query;
    }

    private function creditQuotasQuery()
    {
        $query = DB::table('sale_document_quotas')
            ->join('sale_documents', 'sale_document_quotas.sale_document_id', '=', 'sale_documents.id')
            ->join('sales', 'sale_documents.sale_id', '=', 'sales.id')
            ->join('people', 'sales.client_id', '=', 'people.id')
            ->where('sales.physical', 2)
            ->whereIn('sale_documents.invoice_type_doc', ['01', '03'])
            ->where('sale_documents.forma_pago', 'Credito')
            ->where('sale_documents.status', 1)
            ->whereNotIn('sale_documents.invoice_status', ['Rechazada'])
            ->where('sale_document_quotas.balance', '>', 0)
            ->where(function ($subQuery) {
                $subQuery->whereNull('sales.status')
                    ->orWhere('sales.status', 1);
            });

        return $this->applyOwnerScope($query);
    }

    private function installmentSchedulesQuery()
    {
        $query = DB::table('sale_payment_schedules')
            ->join('sales', 'sale_payment_schedules.sale_id', '=', 'sales.id')
            ->join('people', 'sales.client_id', '=', 'people.id')
            ->where('sales.payment_installments', true)
            ->where('sale_payment_schedules.remaining_amount', '>', 0)
            ->where(function ($subQuery) {
                $subQuery->whereNull('sales.status')
                    ->orWhere('sales.status', 1);
            });

        return $this->applyOwnerScope($query);
    }

    private function recoveredCreditThisMonth(Carbon $monthStart, Carbon $monthEnd): float
    {
        $query = DB::table('sale_payment_quotas')
            ->join('sale_document_quotas', 'sale_payment_quotas.sale_document_quota_id', '=', 'sale_document_quotas.id')
            ->join('sale_documents', 'sale_document_quotas.sale_document_id', '=', 'sale_documents.id')
            ->join('sales', 'sale_documents.sale_id', '=', 'sales.id')
            ->where('sales.physical', 2)
            ->whereIn('sale_documents.invoice_type_doc', ['01', '03'])
            ->where('sale_documents.forma_pago', 'Credito')
            ->whereBetween('sale_payment_quotas.payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $this->applyOwnerScope($query);

        return (float) $query->sum('sale_payment_quotas.amount_applied');
    }

    private function recoveredInstallmentsThisMonth(Carbon $monthStart, Carbon $monthEnd): float
    {
        $query = DB::table('sale_payment_schedule_destinations')
            ->join('sales', 'sale_payment_schedule_destinations.sale_id', '=', 'sales.id')
            ->where('sales.payment_installments', true)
            ->where('sale_payment_schedule_destinations.status', 'paid')
            ->whereBetween('sale_payment_schedule_destinations.date_payment', [$monthStart->toDateString(), $monthEnd->toDateString()]);

        $this->applyOwnerScope($query);

        return (float) $query->sum('sale_payment_schedule_destinations.amount');
    }

    private function openCasesCount(): int
    {
        $creditCount = (clone $this->creditQuotasQuery())
            ->distinct()
            ->count('sale_document_quotas.sale_document_id');

        $installmentCount = (clone $this->installmentSchedulesQuery())
            ->distinct()
            ->count('sale_payment_schedules.sale_id');

        return (int) $creditCount + (int) $installmentCount;
    }

    private function overdueCasesCount(Carbon $today): int
    {
        $creditCount = (clone $this->creditQuotasQuery())
            ->whereDate('sale_document_quotas.due_date', '<', $today->toDateString())
            ->distinct()
            ->count('sale_document_quotas.sale_document_id');

        $installmentCount = (clone $this->installmentSchedulesQuery())
            ->whereDate('sale_payment_schedules.payment_date', '<', $today->toDateString())
            ->distinct()
            ->count('sale_payment_schedules.sale_id');

        return (int) $creditCount + (int) $installmentCount;
    }

    private function buildAgingBucketsChart(Carbon $today): array
    {
        $buckets = [
            'Por vencer' => 0,
            '1-7 dias' => 0,
            '8-30 dias' => 0,
            '31-60 dias' => 0,
            '61+ dias' => 0,
        ];

        $creditItems = (clone $this->creditQuotasQuery())
            ->get(['sale_document_quotas.due_date as due_date', 'sale_document_quotas.balance as pending_amount']);

        $scheduleItems = (clone $this->installmentSchedulesQuery())
            ->get(['sale_payment_schedules.payment_date as due_date', 'sale_payment_schedules.remaining_amount as pending_amount']);

        foreach ($creditItems->concat($scheduleItems) as $item) {
            $bucket = $this->resolveAgingBucket($today, $item->due_date);
            $buckets[$bucket] += (float) $item->pending_amount;
        }

        return [
            'labels' => array_keys($buckets),
            'series' => array_map(fn ($amount) => round((float) $amount, 2), array_values($buckets)),
        ];
    }

    private function resolveAgingBucket(Carbon $today, ?string $dueDate): string
    {
        if (! $dueDate) {
            return 'Por vencer';
        }

        $daysLate = Carbon::parse($dueDate)->startOfDay()->diffInDays($today, false);

        if ($daysLate <= 0) {
            return 'Por vencer';
        }

        if ($daysLate <= 7) {
            return '1-7 dias';
        }

        if ($daysLate <= 30) {
            return '8-30 dias';
        }

        if ($daysLate <= 60) {
            return '31-60 dias';
        }

        return '61+ dias';
    }

    private function buildUpcomingCollectionsChart(Carbon $today): array
    {
        $weeks = collect(range(0, 5))->map(function ($index) use ($today) {
            $start = $today->copy()->startOfWeek()->addWeeks($index);
            $end = $start->copy()->endOfWeek();

            return [
                'label' => $index === 0 ? 'Esta semana' : 'Semana '.($index + 1),
                'start' => $start,
                'end' => $end,
                'amount' => 0,
            ];
        })->values();

        $creditItems = (clone $this->creditQuotasQuery())
            ->whereDate('sale_document_quotas.due_date', '>=', $today->toDateString())
            ->whereDate('sale_document_quotas.due_date', '<=', $today->copy()->addWeeks(5)->endOfWeek()->toDateString())
            ->get(['sale_document_quotas.due_date as due_date', 'sale_document_quotas.balance as pending_amount']);

        $scheduleItems = (clone $this->installmentSchedulesQuery())
            ->whereDate('sale_payment_schedules.payment_date', '>=', $today->toDateString())
            ->whereDate('sale_payment_schedules.payment_date', '<=', $today->copy()->addWeeks(5)->endOfWeek()->toDateString())
            ->get(['sale_payment_schedules.payment_date as due_date', 'sale_payment_schedules.remaining_amount as pending_amount']);

        $weeks = $weeks->all();

        foreach ($creditItems->concat($scheduleItems) as $item) {
            $itemDate = Carbon::parse($item->due_date);

            foreach ($weeks as &$week) {
                if ($itemDate->between($week['start'], $week['end'])) {
                    $week['amount'] += (float) $item->pending_amount;
                    break;
                }
            }
        }

        return [
            'labels' => array_map(fn ($week) => $week['label'], $weeks),
            'series' => array_map(fn ($week) => round((float) $week['amount'], 2), $weeks),
        ];
    }

    private function buildPriorityQueue(Carbon $today): array
    {
        $creditQueue = (clone $this->creditQuotasQuery())
            ->whereDate('sale_document_quotas.due_date', '<', $today->toDateString())
            ->orderBy('sale_document_quotas.due_date')
            ->limit(8)
            ->get([
                'people.full_name as client_name',
                'sale_documents.invoice_serie',
                'sale_documents.invoice_correlative',
                'sale_document_quotas.quota_number',
                'sale_document_quotas.due_date',
                'sale_document_quotas.balance as pending_amount',
            ])
            ->map(function ($row) use ($today) {
                return [
                    'client' => $row->client_name,
                    'source' => 'Documento al credito',
                    'reference' => $this->formatDocumentReference($row->invoice_serie, $row->invoice_correlative, $row->quota_number),
                    'dueDate' => $row->due_date,
                    'daysLate' => Carbon::parse($row->due_date)->startOfDay()->diffInDays($today),
                    'pendingAmount' => round((float) $row->pending_amount, 2),
                ];
            });

        $scheduleQueue = (clone $this->installmentSchedulesQuery())
            ->whereDate('sale_payment_schedules.payment_date', '<', $today->toDateString())
            ->orderBy('sale_payment_schedules.payment_date')
            ->limit(8)
            ->get([
                'people.full_name as client_name',
                'sale_payment_schedules.installment_number',
                'sale_payment_schedules.payment_date as due_date',
                'sale_payment_schedules.remaining_amount as pending_amount',
            ])
            ->map(function ($row) use ($today) {
                return [
                    'client' => $row->client_name,
                    'source' => 'Venta en cuotas',
                    'reference' => 'Cuota '.($row->installment_number ?? '-'),
                    'dueDate' => $row->due_date,
                    'daysLate' => Carbon::parse($row->due_date)->startOfDay()->diffInDays($today),
                    'pendingAmount' => round((float) $row->pending_amount, 2),
                ];
            });

        return $creditQueue
            ->concat($scheduleQueue)
            ->sortByDesc(fn ($item) => ($item['daysLate'] * 1000000) + $item['pendingAmount'])
            ->take(6)
            ->values()
            ->all();
    }

    private function buildTopDebtors(): array
    {
        $creditDebtors = (clone $this->creditQuotasQuery())
            ->groupBy('sales.client_id', 'people.full_name')
            ->selectRaw('sales.client_id as client_id, people.full_name as client_name, SUM(sale_document_quotas.balance) as pending_amount')
            ->get();

        $scheduleDebtors = (clone $this->installmentSchedulesQuery())
            ->groupBy('sales.client_id', 'people.full_name')
            ->selectRaw('sales.client_id as client_id, people.full_name as client_name, SUM(sale_payment_schedules.remaining_amount) as pending_amount')
            ->get();

        return $creditDebtors
            ->concat($scheduleDebtors)
            ->groupBy('client_id')
            ->map(function (Collection $items) {
                return [
                    'client' => $items->first()->client_name,
                    'pendingAmount' => round((float) $items->sum('pending_amount'), 2),
                    'itemsCount' => $items->count(),
                ];
            })
            ->sortByDesc('pendingAmount')
            ->take(6)
            ->values()
            ->all();
    }

    private function buildRecentCollections(): array
    {
        $creditPayments = DB::table('sale_payment_quotas')
            ->join('sale_document_quotas', 'sale_payment_quotas.sale_document_quota_id', '=', 'sale_document_quotas.id')
            ->join('sale_documents', 'sale_document_quotas.sale_document_id', '=', 'sale_documents.id')
            ->join('sales', 'sale_documents.sale_id', '=', 'sales.id')
            ->join('people', 'sales.client_id', '=', 'people.id')
            ->leftJoin('payment_methods', 'sale_payment_quotas.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.physical', 2)
            ->where('sale_documents.forma_pago', 'Credito')
            ->whereIn('sale_documents.invoice_type_doc', ['01', '03'])
            ->orderByDesc('sale_payment_quotas.payment_date')
            ->limit(6);

        $this->applyOwnerScope($creditPayments);

        $creditPayments = $creditPayments->get([
            'people.full_name as client_name',
            'sale_payment_quotas.payment_date as paid_date',
            'sale_payment_quotas.amount_applied as amount',
            'payment_methods.description as method_name',
            'sale_payment_quotas.reference',
        ])->map(function ($row) {
            return [
                'client' => $row->client_name,
                'source' => 'Documento al credito',
                'date' => $row->paid_date,
                'amount' => round((float) $row->amount, 2),
                'method' => $row->method_name ?: 'Sin metodo',
                'reference' => $row->reference ?: 'Sin referencia',
            ];
        });

        $schedulePayments = DB::table('sale_payment_schedule_destinations')
            ->join('sales', 'sale_payment_schedule_destinations.sale_id', '=', 'sales.id')
            ->join('people', 'sales.client_id', '=', 'people.id')
            ->leftJoin('payment_methods', 'sale_payment_schedule_destinations.method_id', '=', 'payment_methods.id')
            ->where('sales.payment_installments', true)
            ->where('sale_payment_schedule_destinations.status', 'paid')
            ->orderByDesc('sale_payment_schedule_destinations.date_payment')
            ->limit(6);

        $this->applyOwnerScope($schedulePayments);

        $schedulePayments = $schedulePayments->get([
            'people.full_name as client_name',
            'sale_payment_schedule_destinations.date_payment as paid_date',
            'sale_payment_schedule_destinations.amount',
            'payment_methods.description as method_name',
            'sale_payment_schedule_destinations.reference',
        ])->map(function ($row) {
            return [
                'client' => $row->client_name,
                'source' => 'Venta en cuotas',
                'date' => $row->paid_date,
                'amount' => round((float) $row->amount, 2),
                'method' => $row->method_name ?: 'Sin metodo',
                'reference' => $row->reference ?: 'Sin referencia',
            ];
        });

        return $creditPayments
            ->concat($schedulePayments)
            ->sortByDesc('date')
            ->take(8)
            ->values()
            ->all();
    }

    private function formatDocumentReference(?string $serie, $correlative, $quotaNumber): string
    {
        $serie = $serie ?: 'DOC';
        $correlative = str_pad((string) ($correlative ?: 0), 8, '0', STR_PAD_LEFT);

        return "{$serie}-{$correlative} · Cuota {$quotaNumber}";
    }
}
