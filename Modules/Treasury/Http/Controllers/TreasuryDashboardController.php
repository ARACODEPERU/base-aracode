<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryLedgerService;

class TreasuryDashboardController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryLedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_dashboard');

        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd = Carbon::now()->endOfMonth()->toDateString();

        $balances = collect($this->ledger->balancesByAccount());

        $monthTotals = TreasuryTransaction::query()
            ->active()
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS incomes,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expenses
            ")
            ->first();

        $byCategory = TreasuryTransaction::query()
            ->active()
            ->whereBetween('transaction_date', [$monthStart, $monthEnd])
            ->join('treasury_categories', 'treasury_categories.id', '=', 'treasury_transactions.treasury_category_id')
            ->selectRaw("treasury_categories.name, treasury_categories.color, treasury_transactions.type,
                SUM(treasury_transactions.amount) AS total")
            ->groupBy('treasury_categories.name', 'treasury_categories.color', 'treasury_transactions.type')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'color' => $row->color,
                'type' => $row->type,
                'total' => round((float) $row->total, 2),
            ]);

        $recent = TreasuryTransaction::query()
            ->active()
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
                'description' => $tx->description,
                'category' => $tx->category?->name,
                'category_color' => $tx->category?->color,
                'account' => $tx->account?->displayName(),
                'source' => $tx->source,
            ]);

        return Inertia::render('Treasury::Dashboard/Index', [
            'categories' => TreasuryCategory::query()->orderBy('name')->get(['id', 'name', 'applies_to', 'color']),
            'accounts' => $balances->map(fn ($item) => [
                'id' => $item['account']->id,
                'label' => $item['account']->displayName(),
                'type' => $item['account']->type,
                'currency' => $item['account']->currency_type_id,
                'balance' => $item['balance'],
            ])->values()->all(),
            'totalBalance' => round($balances->sum('balance'), 2),
            'monthIncomes' => round((float) ($monthTotals->incomes ?? 0), 2),
            'monthExpenses' => round((float) ($monthTotals->expenses ?? 0), 2),
            'byCategory' => $byCategory,
            'recent' => $recent,
        ]);
    }
}
