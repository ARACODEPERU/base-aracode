<?php

namespace Modules\Treasury\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryCategory;
use Modules\Treasury\Entities\TreasuryCostCenter;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryIntegrationService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryTransactionOriginService;

class TreasuryTransactionController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryTransactionOriginService $origins,
    ) {
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
                'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
            ]);

        $account = $accountId ? TreasuryAccount::findOrFail($accountId) : ($accounts->first() ? TreasuryAccount::find($accounts->first()['id']) : null);

        $transactions = collect();
        $runningBalance = 0.0;

        if ($account) {
            $statement = collect($this->ledger->statement($account, $from, $to));

            // Detalle del origen (comprobante, cuota, curso) de cada movimiento.
            $details = $this->origins->detailsFor($statement->pluck('id')->all());

            $transactions = $statement->map(function (array $row) use ($details) {
                $row['detail'] = $details[(int) $row['id']] ?? null;

                return $row;
            });

            $runningBalance = $account->opening_balance
                + $statement->sum(fn ($row) => $row['type'] === 'income' ? $row['amount'] : -$row['amount']);
        }

        return Inertia::render('Treasury::Transactions/Index', [
            'categories' => TreasuryCategory::query()->orderBy('name')->get(['id', 'name', 'applies_to', 'color']),
            'paymentMethods' => PaymentMethod::query()->orderBy('description')->get(['id', 'description']),
            'costCenters' => TreasuryCostCenter::query()->where('status', true)->orderBy('name')->get(['id', 'name']),
            'accounts' => $accounts,
            'selectedAccountId' => $account?->id,
            'accountCurrency' => $account ? (strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN') : 'PEN',
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

        // La cuenta de detracción solo se mueve desde su pantalla (IGV, liberación o ajuste crítico).
        if ($account->isDetraction()) {
            return back()->with('error', 'La cuenta de detracción no admite movimientos manuales: usa su pantalla y la contraseña.');
        }

        try {
            $transaction = $data['type'] === TreasuryTransaction::TYPE_INCOME
                ? $this->ledger->registerIncome(
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
                    true,
                    null,
                    $data['currency'] ?? null,
                    null,
                    $data['cost_center_id'] ?? null,
                )
                : $this->ledger->registerExpense(
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
                    true,
                    null,
                    $data['currency'] ?? null,
                    null,
                    $data['cost_center_id'] ?? null,
                );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (! $transaction) {
            return back()->with('error', 'No se pudo registrar el movimiento (monto inválido, cuenta inactiva o duplicado).');
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

        if ($transaction->isLocked()) {
            return back()->with('error', 'El movimiento pertenece a una conciliación cerrada: no se puede editar.');
        }

        if ($transaction->source !== TreasuryTransaction::SOURCE_MANUAL) {
            return back()->with('error', 'Solo los movimientos manuales pueden editarse.');
        }

        $data = $this->validated($request);

        try {
            $transaction->fill([
                'transaction_date' => $data['transaction_date'],
                'type' => $data['type'],
                'treasury_category_id' => $data['treasury_category_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'amount' => round((float) $data['amount'], 2),
                'currency' => strtoupper($data['currency'] ?? $transaction->currency ?: 'PEN') ?: 'PEN',
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
            ])->save();
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Movimiento actualizado.');
    }

    /**
     * Anulación (soft): el registro se conserva con voided_at para trazabilidad.
     */
    public function destroy(int $id)
    {
        $this->authorizePermission('treasury_movimientos_eliminar');

        $transaction = TreasuryTransaction::findOrFail($id);

        if ($transaction->isLocked()) {
            return back()->with('error', 'El movimiento pertenece a una conciliación cerrada: no se puede anular.');
        }

        // Si el movimiento viene de la integración, se revierte también la
        // operación de origen para que la tabla puente no quede "processed"
        // sin respaldo en el libro.
        $bridge = TreasurySourceMovement::query()
            ->where('treasury_transaction_id', $transaction->id)
            ->whereNull('reversal_of_id')
            ->first();

        try {
            if ($bridge) {
                app(TreasuryIntegrationService::class)->reverse(
                    $bridge,
                    'Anulación manual desde tesorería',
                    auth()->id()
                );
            } else {
                $this->ledger->voidTransaction($transaction, auth()->id());
            }
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Movimiento anulado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'transaction_date' => ['required', 'date'],
            'type' => ['required', 'in:income,expense'],
            'treasury_category_id' => ['nullable', 'integer', 'exists:treasury_categories,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:treasury_cost_centers,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'reference' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);
    }
}
