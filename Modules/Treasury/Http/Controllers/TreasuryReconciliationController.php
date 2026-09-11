<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryLedgerService;

class TreasuryReconciliationController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryLedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_conciliacion');

        $accountId = $request->integer('account_id') ?: null;
        $until = $request->get('until') ?: Carbon::now()->endOfMonth()->toDateString();

        $accounts = TreasuryAccount::query()->active()->with(['bankAccount.bank', 'companyBilletera.billetera'])->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'type' => $account->type,
                'currency' => $account->currency_type_id,
            ]);

        $account = $accountId
            ? TreasuryAccount::findOrFail($accountId)
            : ($accounts->isNotEmpty() ? TreasuryAccount::find($accounts->first()['id']) : null);

        $systemBalance = $account ? $this->ledger->accountBalance($account, $until) : 0.0;

        $unreconciled = collect();
        if ($account) {
            $unreconciled = TreasuryTransaction::query()
                ->active()
                ->where('treasury_account_id', $account->id)
                ->whereDate('transaction_date', '<=', $until)
                ->whereNull('reconciled_at')
                ->with(['category'])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get()
                ->map(fn (TreasuryTransaction $tx) => [
                    'id' => $tx->id,
                    'date' => $tx->transaction_date->format('d/m/Y'),
                    'type' => $tx->type,
                    'amount' => (float) $tx->amount,
                    'reference' => $tx->reference,
                    'description' => $tx->description,
                    'category' => $tx->category?->name,
                    'source' => $tx->source,
                ]);
        }

        return Inertia::render('Treasury::Reconciliation/Index', [
            'accounts' => $accounts,
            'selectedAccountId' => $account?->id,
            'until' => $until,
            'systemBalance' => $systemBalance,
            'unreconciled' => $unreconciled->values()->all(),
            'unreconciledCount' => $unreconciled->count(),
        ]);
    }

    /**
     * Marca transacciones como conciliadas contra el extracto real.
     */
    public function reconcile(Request $request)
    {
        $this->authorizePermission('treasury_conciliacion');

        $data = $request->validate([
            'transaction_ids' => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['integer', 'exists:treasury_transactions,id'],
        ]);

        TreasuryTransaction::query()
            ->whereIn('id', $data['transaction_ids'])
            ->whereNull('reconciled_at')
            ->whereNull('voided_at')
            ->update(['reconciled_at' => Carbon::now()]);

        return back()->with('success', 'Transacciones conciliadas.');
    }
}
