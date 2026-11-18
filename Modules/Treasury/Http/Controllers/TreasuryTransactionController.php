<?php

namespace Modules\Treasury\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\SaleDocument;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryCategory;
use Modules\Treasury\Entities\TreasuryCostCenter;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryCashService;
use Modules\Treasury\Services\TreasuryDetractionService;
use Modules\Treasury\Services\TreasuryIntegrationService;
use Modules\Treasury\Services\TreasuryLedgerService;
use Modules\Treasury\Services\TreasuryTransactionOriginService;

class TreasuryTransactionController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryTransactionOriginService $origins,
        private TreasuryDetractionService $detractions,
        private TreasuryCashService $cash,
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
        $beforeStartCount = 0;

        if ($account) {
            // Se listan también los movimientos anteriores al inicio del módulo
            // (los registrados al conciliar un extracto antiguo), marcados y sin
            // sumar al saldo.
            $statement = collect($this->ledger->statement($account, $from, $to, 500, true));

            // Detalle del origen (comprobante, cuota, curso) de cada movimiento.
            $details = $this->origins->detailsFor($statement->pluck('id')->all());

            $transactions = $statement->map(function (array $row) use ($details) {
                $row['detail'] = $details[(int) $row['id']] ?? null;

                return $row;
            });

            $counted = $statement->reject(fn ($row) => $row['before_start'] ?? false);
            $beforeStartCount = $statement->count() - $counted->count();

            $runningBalance = $account->opening_balance
                + $counted->sum(fn ($row) => $row['type'] === 'income' ? $row['amount'] : -$row['amount']);
        }

        // El efectivo de cajas abiertas se muestra cuando la cuenta elegida es la caja.
        $cashOpen = $this->cash->openSummary();

        return Inertia::render('Treasury::Transactions/Index', [
            'cashOpen' => [
                'count' => $cashOpen['count'],
                'balance' => $cashOpen['balance'],
            ],
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
            'beforeStartCount' => $beforeStartCount,
            // Misma cifra que la pestaña Cuentas: el saldo visible incluye lo que
            // las billeteras vinculadas aportan al banco (y respeta el plazo).
            'calculatedBalance' => $account ? $this->ledger->displayBalance($account) : 0,
            'hidesBalance' => $account ? $this->ledger->hidesBalance($account) : false,
            'walletTransferDays' => $account && $account->type === TreasuryAccount::TYPE_WALLET
                ? (int) ($account->companyBilletera?->bank_transfer_days ?? 0)
                : 0,
            'walletBank' => $account && $account->type === TreasuryAccount::TYPE_WALLET
                ? $this->ledger->bankForWallet($account)?->displayName()
                : null,
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
                    $data['operation_number'] ?? null,
                    $data['transaction_time'] ?? null,
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
                    $data['operation_number'] ?? null,
                    $data['transaction_time'] ?? null,
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
            $this->ledger->assertOperationNumberAvailable(
                (int) $transaction->treasury_account_id,
                $data['operation_number'] ?? null,
                (int) $transaction->id,
            );

            $transaction->fill([
                'transaction_date' => $data['transaction_date'],
                'transaction_time' => $data['transaction_time'] ?? null,
                'type' => $data['type'],
                'treasury_category_id' => $data['treasury_category_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'amount' => round((float) $data['amount'], 2),
                'currency' => strtoupper($data['currency'] ?? $transaction->currency ?: 'PEN') ?: 'PEN',
                'reference' => $data['reference'] ?? null,
                'operation_number' => $data['operation_number'] ?? null,
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

    /**
     * Hace la auto-detracción de una factura desde su cobro en Movimientos:
     * transfiere el importe detraído a la cuenta de Detracción.
     */
    public function autoDetraction(int $id)
    {
        $this->authorizePermission('treasury_movimientos_nuevo');

        $transaction = TreasuryTransaction::findOrFail($id);

        if ($transaction->voided_at) {
            return back()->with('error', 'El movimiento está anulado: no se puede hacer la auto-detracción.');
        }

        $detail = $this->origins->detailsFor([$transaction->id])[$transaction->id] ?? null;

        if (! $detail || empty($detail['document_id'])) {
            return back()->with('error', 'Este movimiento no tiene una factura de origen.');
        }

        if (empty($detail['detraccion_pendiente'])) {
            return back()->with('error', 'La detracción de esta factura ya está registrada o no corresponde.');
        }

        $document = SaleDocument::find((int) $detail['document_id']);
        $account = $transaction->account;

        if (! $document || ! $account) {
            return back()->with('error', 'No se encontró la factura o la cuenta del cobro.');
        }

        try {
            $income = $this->detractions->registerAutoDetraction(
                $document,
                $account,
                $transaction->transaction_date ?? now(),
                auth()->id(),
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Auto-detracción registrada: ' . number_format((float) $income->amount, 2, '.', ',') . ' transferidos a la cuenta de Detracción.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'transaction_date' => ['required', 'date'],
            // Hora opcional: HH:MM (input del navegador) o HH:MM:SS.
            'transaction_time' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'type' => ['required', 'in:income,expense'],
            'treasury_category_id' => ['nullable', 'integer', 'exists:treasury_categories,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:treasury_cost_centers,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'reference' => ['nullable', 'string', 'max:120'],
            'operation_number' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);
    }
}
