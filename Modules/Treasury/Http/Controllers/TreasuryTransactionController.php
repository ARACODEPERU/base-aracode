<?php

namespace Modules\Treasury\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryCategory;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryLedgerService;

class TreasuryTransactionController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryLedgerService $ledger)
    {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_movimientos');

        $accountId = $request->integer('account_id') ?: null;
        $from = $request->get('from');
        $to = $request->get('to');

        $accounts = TreasuryAccount::query()->active()->with(['bankAccount.bank', 'companyBilletera.billetera'])->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'type' => $account->type,
                'currency' => $account->currency_type_id,
            ]);

        $account = $accountId ? TreasuryAccount::findOrFail($accountId) : ($accounts->first() ? TreasuryAccount::find($accounts->first()['id']) : null);

        $transactions = collect();
        $runningBalance = 0.0;

        if ($account) {
            $statement = collect($this->ledger->statement($account, $from, $to));
            $transactions = $statement;
            $runningBalance = $account->opening_balance
                + $statement->sum(fn ($row) => $row['type'] === 'income' ? $row['amount'] : -$row['amount']);
        }

        return Inertia::render('Treasury::Transactions/Index', [
            'categories' => TreasuryCategory::query()->orderBy('name')->get(['id', 'name', 'applies_to', 'color']),
            'paymentMethods' => PaymentMethod::query()->orderBy('description')->get(['id', 'description']),
            'accounts' => $accounts,
            'selectedAccountId' => $account?->id,
            'from' => $from,
            'to' => $to,
            'transactions' => $transactions->values()->all(),
            'openingBalance' => $account ? (float) $account->opening_balance : 0,
            'runningBalance' => round($runningBalance, 2),
            'calculatedBalance' => $account ? $this->ledger->accountBalance($account) : 0,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('treasury_movimientos_nuevo');

        $data = $this->validated($request);

        $account = TreasuryAccount::findOrFail($data['treasury_account_id']);

        $service = $this->ledger;

        $transaction = $data['type'] === TreasuryTransaction::TYPE_INCOME
            ? $service->registerIncome(
                $account,
                $data['transaction_date'],
                (float) $data['amount'],
                $data['treasury_category_id'] ?? null,
                null,
                null,
                $data['reference'] ?? null,
                $data['description'] ?? null,
                $data['payment_method_id'] ?? null,
                TreasuryTransaction::SOURCE_MANUAL,
                auth()->id(),
            )
            : $service->registerExpense(
                $account,
                $data['transaction_date'],
                (float) $data['amount'],
                $data['treasury_category_id'] ?? null,
                null,
                null,
                $data['reference'] ?? null,
                $data['description'] ?? null,
                $data['payment_method_id'] ?? null,
                TreasuryTransaction::SOURCE_MANUAL,
                auth()->id(),
            );

        if (! $transaction) {
            return back()->with('error', 'No se pudo registrar el movimiento (monto inválido o cuenta inactiva).');
        }

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('treasury', 'public');
            $transaction->attachment_path = $path;
            $transaction->save();
        }

        return back()->with('success', 'Movimiento registrado.');
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission('treasury_movimientos_editar');

        $transaction = TreasuryTransaction::findOrFail($id);

        if ($transaction->voided_at) {
            return back()->with('error', 'No se puede editar un movimiento anulado.');
        }

        if ($transaction->source !== TreasuryTransaction::SOURCE_MANUAL) {
            return back()->with('error', 'Solo los movimientos manuales pueden editarse.');
        }

        $data = $this->validated($request);

        $transaction->fill([
            'transaction_date' => $data['transaction_date'],
            'type' => $data['type'],
            'treasury_category_id' => $data['treasury_category_id'] ?? null,
            'amount' => round((float) $data['amount'], 2),
            'reference' => $data['reference'] ?? null,
            'description' => $data['description'] ?? null,
            'payment_method_id' => $data['payment_method_id'] ?? null,
        ])->save();

        return back()->with('success', 'Movimiento actualizado.');
    }

    /**
     * Anulación (soft): el registro se conserva con voided_at para trazabilidad.
     */
    public function destroy(int $id)
    {
        $this->authorizePermission('treasury_movimientos_eliminar');

        $transaction = TreasuryTransaction::findOrFail($id);
        $this->ledger->voidTransaction($transaction, auth()->id());

        return back()->with('success', 'Movimiento anulado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'transaction_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'treasury_category_id' => ['nullable', 'integer', 'exists:treasury_categories,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'reference' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);
    }
}
