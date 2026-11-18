<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryReconciliationItem;
use Modules\Treasury\Entities\TreasuryStatementImport;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryReconciliationService;
use Modules\Treasury\Services\TreasuryStatementImportService;
use Modules\Treasury\Services\TreasuryTransactionOriginService;

/**
 * Conciliación bancaria.
 *
 * El cierre congela el día, exige que la diferencia esté explicada por las
 * partidas del extracto (o dentro de la tolerancia), guarda quién lo hizo y
 * bloquea para siempre las transacciones incluidas.
 */
class TreasuryReconciliationController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryReconciliationService $reconciliation,
        private TreasuryStatementImportService $statementImports,
    ) {
    }

    public function index(Request $request, TreasuryTransactionOriginService $origins)
    {
        $this->authorizePermission('treasury_conciliacion');

        $accountId = $request->integer('account_id') ?: null;
        $until = $request->get('until') ?: Carbon::now()->toDateString();

        $accounts = TreasuryAccount::query()->active()->with(['bankAccount.bank', 'companyBilletera.billetera'])->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'type' => $account->type,
                'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
            ]);

        $account = $accountId
            ? TreasuryAccount::findOrFail($accountId)
            : ($accounts->isNotEmpty() ? TreasuryAccount::find($accounts->first()['id']) : null);

        $draft = $account
            ? $this->reconciliation->draft($account, $until)
            : [
                'account' => null,
                'until' => $until,
                'system_balance' => 0.0,
                'items' => [],
                'items_income' => 0.0,
                'items_expense' => 0.0,
                'adjusted_system_balance' => 0.0,
                'transactions' => [],
                'status' => null,
            ];

        // Detalle legible del origen de cada línea (comprobante, cuota, curso).
        $details = $origins->detailsFor(array_column($draft['transactions'], 'id'));

        $draft['transactions'] = array_map(function (array $row) use ($details) {
            $row['detail'] = $details[(int) $row['id']] ?? null;

            return $row;
        }, $draft['transactions']);

        return Inertia::render('Treasury::Reconciliation/Index', [
            'accounts' => $accounts,
            'selectedAccountId' => $account?->id,
            'until' => $until,
            'draft' => $draft,
            'systemBalance' => $draft['system_balance'],
            'unreconciled' => $draft['transactions'],
            'unreconciledCount' => count($draft['transactions']),
            'items' => $draft['items'],
            'adjustedSystemBalance' => $draft['adjusted_system_balance'],
            'history' => $account ? $this->reconciliation->history($account) : [],
            // Comparación del último extracto contra el libro, por rango de fechas.
            'comparison' => $account ? $this->reconciliation->statementComparison($account) : null,
            'tolerance' => (float) config('treasury.reconciliation_tolerance', 0.01),
            'canClose' => auth()->user()?->hasPermissionTo('treasury_conciliacion_cerrar'),
            'canImport' => auth()->user()?->hasPermissionTo('treasury_conciliacion_importar'),
            'imports' => \Modules\Treasury\Entities\TreasuryStatementImport::query()
                ->when($account, fn ($query) => $query->where('treasury_account_id', $account->id))
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn ($import) => [
                    'id' => $import->id,
                    'file_name' => $import->file_name,
                    'format' => strtoupper($import->format),
                    'rows_total' => $import->rows_total,
                    'rows_matched' => $import->rows_matched,
                    'rows_created' => $import->rows_created,
                    'created_at' => $import->created_at?->format('d/m/Y H:i'),
                ])
                ->all(),
        ]);
    }

    /**
     * Cierra la conciliación del día. Es el único camino que marca
     * transacciones como conciliadas.
     */
    public function close(Request $request)
    {
        $this->authorizePermission('treasury_conciliacion_cerrar');

        $data = $request->validate([
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'until' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'transaction_ids' => ['nullable', 'array'],
            'transaction_ids.*' => ['integer', 'exists:treasury_transactions,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $account = TreasuryAccount::query()->active()->findOrFail($data['treasury_account_id']);

        try {
            $act = $this->reconciliation->close(
                $account,
                $data['until'],
                (float) $data['statement_balance'],
                array_map('intval', $data['transaction_ids'] ?? []),
                auth()->id(),
                $data['notes'] ?? null,
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Conciliación cerrada por ' . number_format((float) $act->statement_balance, 2, '.', '')
            . ' al ' . $act->cutoff_date->format('d/m/Y') . '. Las líneas incluidas quedaron bloqueadas.'
        );
    }

    public function storeItem(Request $request)
    {
        $this->authorizePermission('treasury_conciliacion');

        $data = $request->validate([
            'treasury_account_id' => ['required', 'integer', 'exists:treasury_accounts,id'],
            'cutoff_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'type' => ['required', 'in:income,expense'],
        ]);

        $account = TreasuryAccount::query()->active()->findOrFail($data['treasury_account_id']);

        try {
            $this->reconciliation->addItem(
                $account,
                $data['cutoff_date'],
                $data['description'],
                (float) $data['amount'],
                $data['type'],
                auth()->id(),
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Partida del extracto registrada. Ahora la diferencia puede explicarse.');
    }

    /**
     * Registra como movimiento una línea del extracto que no tenía par.
     */
    public function createStatementMovement(Request $request, int $importId)
    {
        $this->authorizePermission('treasury_conciliacion_importar');

        $data = $request->validate([
            'line_index' => ['required', 'integer', 'min:0'],
        ]);

        $import = TreasuryStatementImport::findOrFail($importId);

        try {
            $transaction = $this->reconciliation->createMovementFromStatement(
                $import,
                (int) $data['line_index'],
                auth()->id(),
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        // El egreso se informa con signo negativo para no confundirlo con un ingreso.
        $isIncome = $transaction->type === TreasuryTransaction::TYPE_INCOME;

        return back()->with(
            'success',
            ($isIncome ? 'Ingreso' : 'Egreso') . ' registrado desde el extracto: '
            . ($isIncome ? '' : '-')
            . number_format((float) $transaction->amount, 2, '.', '')
            . ' del ' . $transaction->transaction_date->format('d/m/Y') . '.'
        );
    }

    /**
     * Marca una línea recomendada como «movimiento diferente»: se deshace el
     * par propuesto para poder registrarla como movimiento nuevo, sin tocar el
     * movimiento que ya existe en el libro.
     */
    public function dismissStatementMatch(int $importId, int $lineIndex)
    {
        $this->authorizePermission('treasury_conciliacion_importar');

        $import = TreasuryStatementImport::findOrFail($importId);

        try {
            $this->statementImports->dismissMatch($import, $lineIndex, auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'La línea del extracto quedó como movimiento diferente: ya puedes registrarla como movimiento nuevo. '
            . 'El movimiento del libro no se modificó.'
        );
    }

    /**
     * Registra como movimientos las líneas del extracto seleccionadas.
     */
    public function createStatementMovements(Request $request, int $importId)
    {
        $this->authorizePermission('treasury_conciliacion_importar');

        $data = $request->validate([
            'line_indexes' => ['required', 'array', 'min:1'],
            'line_indexes.*' => ['integer', 'min:0'],
        ]);

        $import = TreasuryStatementImport::findOrFail($importId);

        try {
            $created = $this->reconciliation->createMovementsFromStatement(
                $import,
                $data['line_indexes'],
                auth()->id(),
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        $incomes = array_filter($created, fn ($transaction) => $transaction->type === TreasuryTransaction::TYPE_INCOME);
        $expenses = array_filter($created, fn ($transaction) => $transaction->type !== TreasuryTransaction::TYPE_INCOME);
        $incomeTotal = array_sum(array_map(fn ($transaction) => (float) $transaction->amount, $incomes));
        $expenseTotal = array_sum(array_map(fn ($transaction) => (float) $transaction->amount, $expenses));

        // Se informan por separado y con signo: los egresos nunca se suman
        // como si fueran ingresos.
        $parts = [];
        if ($incomes) {
            $parts[] = count($incomes) . ' ingreso(s) por ' . number_format($incomeTotal, 2, '.', '');
        }
        if ($expenses) {
            $parts[] = count($expenses) . ' egreso(s) por -' . number_format($expenseTotal, 2, '.', '');
        }

        $mensaje = count($created) . ' movimiento(s) registrados desde el extracto: ' . implode(' y ', $parts) . '.';

        if ($incomes && $expenses) {
            $mensaje .= ' Neto: ' . number_format($incomeTotal - $expenseTotal, 2, '.', '') . '.';
        }

        return back()->with('success', $mensaje);
    }

    public function destroyItem(int $id)
    {
        $this->authorizePermission('treasury_conciliacion');

        try {
            $this->reconciliation->removeItem(TreasuryReconciliationItem::findOrFail($id), auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Partida eliminada.');
    }
}
