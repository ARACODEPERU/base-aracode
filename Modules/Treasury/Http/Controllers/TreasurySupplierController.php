<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Treasury\Entities\TreasurySupplier;
use Modules\Treasury\Services\TreasuryAuditService;

/**
 * Proveedores de pagos y compras. No se borran si tienen órdenes: se
 * desactivan para conservar el historial.
 */
class TreasurySupplierController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(private TreasuryAuditService $audit)
    {
    }

    public function store(Request $request)
    {
        $this->authorizePermission('treasury_proveedores');

        $data = $this->validated($request);

        $supplier = TreasurySupplier::create($data);

        $this->audit->log('supplier', $supplier->id, 'created', [], $supplier->only([
            'doc_type', 'doc_number', 'name', 'email', 'payment_terms_days',
        ]));

        return back()->with('success', 'Proveedor registrado.');
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission('treasury_proveedores');

        $supplier = TreasurySupplier::findOrFail($id);
        $before = $supplier->only(['name', 'doc_number', 'email', 'phone', 'payment_terms_days', 'status']);

        $supplier->fill($this->validated($request, $supplier->id))->save();

        $this->audit->log('supplier', $supplier->id, 'updated', $before, $supplier->only([
            'name', 'doc_number', 'email', 'phone', 'payment_terms_days', 'status',
        ]));

        return back()->with('success', 'Proveedor actualizado.');
    }

    public function destroy(int $id)
    {
        $this->authorizePermission('treasury_proveedores');

        $supplier = TreasurySupplier::findOrFail($id);

        if ($supplier->orders()->exists() || $supplier->recurringExpenses()->exists()) {
            $supplier->status = false;
            $supplier->save();

            $this->audit->log('supplier', $supplier->id, 'deactivated', ['status' => true], ['status' => false]);

            return back()->with('success', 'El proveedor tiene órdenes o gastos: se desactivó en lugar de eliminarse.');
        }

        $this->audit->log('supplier', $supplier->id, 'deleted', $supplier->only(['name', 'doc_number']), []);
        $supplier->delete();

        return back()->with('success', 'Proveedor eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'doc_type' => ['required', 'in:6,1,0'],
            'doc_number' => [
                'nullable', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('treasury_suppliers', 'doc_number')
                    ->where(fn ($query) => $query->where('doc_type', $request->input('doc_type')))
                    ->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:250'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['boolean'],
        ]);
    }
}
