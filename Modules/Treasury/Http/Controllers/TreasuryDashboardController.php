<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryCategory;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryCurrencyService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasurySettingsService;

/**
 * Dashboard de tesorería.
 *
 * Regla de moneda: los saldos se muestran por moneda y solo el consolidado se
 * convierte a soles, indicando el tipo de cambio. Nunca se suma un saldo en
 * dólares a uno en soles como si fueran lo mismo.
 */
class TreasuryDashboardController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryCurrencyService $currency,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_dashboard');

        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();

        $data = $this->ledger->consolidatedBalances();
        $balances = collect($data['balances']);

        $accounts = $balances->map(fn ($item) => [
            'id' => $item['account']->id,
            'label' => $item['account']->displayName(),
            'type' => $item['account']->type,
            'currency' => $this->currency->accountCurrency($item['account']),
            'balance' => $item['balance'],
            'hides_balance' => $this->ledger->hidesBalance($item['account']),
        ])->values()->all();

        // Totales del mes por moneda, convertidos aparte al consolidado.
        $monthRows = TreasuryTransaction::query()
            ->active()
            ->when(app(TreasurySettingsService::class)->startsOn(), fn ($q, $start) => $q->whereDate('transaction_date', '>=', $start))
            ->whereDate('transaction_date', '>=', $monthStart)
            ->whereDate('transaction_date', '<=', $monthEnd)
            ->selectRaw("
                currency,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS incomes,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expenses
            ")
            ->groupBy('currency')
            ->get();

        $monthByCurrency = $monthRows->map(fn ($row) => [
            'currency' => strtoupper((string) ($row->currency ?: 'PEN')) ?: 'PEN',
            'incomes' => round((float) $row->incomes, 2),
            'expenses' => round((float) $row->expenses, 2),
        ])->values()->all();

        $monthIncomesBase = 0.0;
        $monthExpensesBase = 0.0;

        foreach ($monthByCurrency as $row) {
            $monthIncomesBase += $this->currency->toBase($row['incomes'], $row['currency']) ?? 0;
            $monthExpensesBase += $this->currency->toBase($row['expenses'], $row['currency']) ?? 0;
        }

        $byCategory = TreasuryTransaction::query()
            ->active()
            ->whereDate('transaction_date', '>=', $monthStart)
            ->whereDate('transaction_date', '<=', $monthEnd)
            ->join('treasury_categories', 'treasury_categories.id', '=', 'treasury_transactions.treasury_category_id')
            ->selectRaw('treasury_categories.name, treasury_categories.color, treasury_transactions.type,
                treasury_transactions.currency AS currency, SUM(treasury_transactions.amount) AS total')
            ->groupBy('treasury_categories.name', 'treasury_categories.color', 'treasury_transactions.type', 'treasury_transactions.currency')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $base = $this->currency->toBase((float) $row->total, $row->currency ?: 'PEN') ?? 0;

                return [
                    'name' => $row->name,
                    'color' => $row->color,
                    'type' => $row->type,
                    'currency' => strtoupper((string) ($row->currency ?: 'PEN')) ?: 'PEN',
                    'total' => round((float) $row->total, 2),
                    'total_base' => round($base, 2),
                ];
            })
            ->sortByDesc('total_base')
            ->take(8)
            ->values();

        $recent = TreasuryTransaction::query()
            ->active()
            ->when(app(TreasurySettingsService::class)->startsOn(), fn ($q, $start) => $q->whereDate('transaction_date', '>=', $start))
            ->with(['account.bankAccount.bank', 'account.companyBilletera.billetera', 'category'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (TreasuryTransaction $tx) => [
                'id' => $tx->id,
                'date' => $tx->transaction_date->format('d/m/Y'),
                'type' => $tx->type,
                'amount' => (float) $tx->amount,
                'currency' => strtoupper((string) ($tx->currency ?: 'PEN')) ?: 'PEN',
                'description' => $tx->description,
                'category' => $tx->category?->name,
                'category_color' => $tx->category?->color,
                'account' => $tx->account?->displayName(),
                'source' => $tx->source,
            ]);

        $pendingOrders = TreasuryPaymentOrder::query()
            ->open()
            ->whereBetween('scheduled_date', [$monthStart, $monthEnd])
            ->get();

        return Inertia::render('Treasury::Dashboard/Index', [
            'settings' => app(TreasurySettingsService::class)->summary(),
            'categories' => TreasuryCategory::query()->orderBy('name')->get(['id', 'name', 'applies_to', 'color']),
            'accounts' => $accounts,
            'groups' => $data['consolidation']['groups'],
            'consolidated' => $data['consolidation']['consolidated'],
            'rates' => $data['consolidation']['rates'],
            'ratesIncomplete' => $data['consolidation']['incomplete'],
            'baseCurrency' => $this->currency->base(),
            'monthIncomes' => round($monthIncomesBase, 2),
            'monthExpenses' => round($monthExpensesBase, 2),
            'monthByCurrency' => $monthByCurrency,
            'byCategory' => $byCategory,
            'recent' => $recent,
            'pendingOrders' => [
                'count' => $pendingOrders->count(),
                'total_base' => round($pendingOrders->sum(fn ($order) => $this->currency->toBase((float) $order->amount, $order->currency) ?? 0), 2),
            ],
        ]);
    }
}
