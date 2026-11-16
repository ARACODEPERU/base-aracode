<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Treasury\Entities\TreasuryRecurringExpense;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryRecurringExpenseService;

/**
 * Gastos recurrentes: planillas, servicios, insumos y alquileres.
 *
 * La ejecución automática pasa por la cola `queue:work`; aquí se puede
 * ejecutar uno a mano para probar o adelantar el pago.
 */
class TreasuryRecurringExpenseController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryRecurringExpenseService $recurring)
    {
    }

    public function store(Request $request)
    {
        $this->authorizePermission('treasury_recurrentes');

        $data = $request->validate([
            'concept' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:' . implode(',', array_keys(TreasuryRecurringExpense::TYPE_LABELS))],
            'supplier_id' => ['nullable', 'integer', 'exists:treasury_suppliers,id'],
            'treasury_category_id' => ['nullable', 'integer', 'exists:treasury_categories,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'treasury_account_id' => ['nullable', 'integer', 'exists:treasury_accounts,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'frequency' => ['required', 'in:' . implode(',', array_keys(TreasuryRecurringExpense::FREQUENCY_LABELS))],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'auto_pay' => ['boolean'],
            'status' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $expense = $this->recurring->create($data, auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Gasto recurrente «{$expense->concept}» creado. Próxima ejecución: {$expense->next_run_date->format('d/m/Y')}.");
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission('treasury_recurrentes');

        $data = $request->validate([
            'concept' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:' . implode(',', array_keys(TreasuryRecurringExpense::TYPE_LABELS))],
            'supplier_id' => ['nullable', 'integer', 'exists:treasury_suppliers,id'],
            'treasury_category_id' => ['nullable', 'integer', 'exists:treasury_categories,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'treasury_account_id' => ['nullable', 'integer', 'exists:treasury_accounts,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'frequency' => ['required', 'in:' . implode(',', array_keys(TreasuryRecurringExpense::FREQUENCY_LABELS))],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'end_date' => ['nullable', 'date'],
            'auto_pay' => ['boolean'],
            'status' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $expense = $this->recurring->update(TreasuryRecurringExpense::findOrFail($id), $data, auth()->id());

        return back()->with('success', "Gasto recurrente «{$expense->concept}» actualizado.");
    }

    public function runNow(int $id)
    {
        $this->authorizePermission('treasury_recurrentes');

        $expense = TreasuryRecurringExpense::findOrFail($id);
        $order = $this->recurring->run($expense, auth()->id());

        if (! $order) {
            return back()->with('error', 'No se pudo generar la orden del gasto recurrente. Revisa la última ejecución en la lista.');
        }

        return back()->with('success', "Ocurrencia ejecutada: orden {$order->code}.");
    }

    public function destroy(int $id)
    {
        $this->authorizePermission('treasury_recurrentes');

        $expense = TreasuryRecurringExpense::findOrFail($id);

        if ($expense->runs()->exists()) {
            $expense->status = false;
            $expense->save();

            return back()->with('success', 'El gasto recurrente ya tiene ejecuciones: se desactivó en lugar de eliminarse.');
        }

        $expense->delete();

        return back()->with('success', 'Gasto recurrente eliminado.');
    }
}
