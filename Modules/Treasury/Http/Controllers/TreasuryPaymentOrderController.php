<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryCategory;
use Modules\Treasury\Entities\TreasuryCostCenter;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Entities\TreasuryRecurringExpense;
use Modules\Treasury\Entities\TreasurySupplier;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryBulkPaymentService;
use Modules\Treasury\Services\TreasuryPaymentOrderService;

/**
 * Pagos, compras, pagos a proveedores y gastos.
 *
 * Crear la orden no mueve dinero: el egreso entra al libro al pagar, por la
 * capa de integración. Sobre el umbral configurado se exige aprobación de
 * otro usuario.
 */
class TreasuryPaymentOrderController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryPaymentOrderService $orders,
        private TreasuryBulkPaymentService $bulk,
    ) {
    }

    public function index()
    {
        $this->authorizePermission('treasury_pagos');

        $orders = TreasuryPaymentOrder::query()
            ->with(['supplier', 'category', 'costCenter', 'account', 'approver', 'creator'])
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (TreasuryPaymentOrder $order) => [
                'id' => $order->id,
                'code' => $order->code,
                'type' => $order->type,
                'type_label' => $order->typeLabel(),
                'supplier' => $order->supplier?->name,
                'description' => $order->description,
                'category' => $order->category?->name,
                'cost_center' => $order->costCenter?->name,
                'currency' => $order->currency,
                'amount' => (float) $order->amount,
                'issue_date' => $order->issue_date?->format('Y-m-d'),
                'due_date' => $order->due_date?->format('Y-m-d'),
                'scheduled_date' => $order->scheduled_date?->format('Y-m-d'),
                'account' => $order->account?->displayName(),
                'status' => $order->status,
                'status_label' => $order->statusLabel(),
                'approved_by' => $order->approver?->name,
                'approved_at' => $order->approved_at?->format('d/m/Y H:i'),
                'paid_at' => $order->paid_at?->format('d/m/Y H:i'),
                'created_by' => $order->creator?->name,
                'can_approve' => $order->canBeApproved(),
                'can_pay' => $order->canBePaid(),
                'can_void' => ! in_array($order->status, [TreasuryPaymentOrder::STATUS_VOIDED, TreasuryPaymentOrder::STATUS_REJECTED], true),
                'attachment' => $order->attachment_path,
                'notes' => $order->notes,
                'recurring_id' => $order->recurring_id,
            ]);

        $suppliers = TreasurySupplier::query()
            ->withCount('orders')
            ->orderBy('name')
            ->get()
            ->map(fn (TreasurySupplier $supplier) => [
                'id' => $supplier->id,
                'doc_type' => $supplier->doc_type,
                'doc_number' => $supplier->doc_number,
                'name' => $supplier->name,
                'trade_name' => $supplier->trade_name,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'payment_terms_days' => $supplier->payment_terms_days,
                'bank_account_id' => $supplier->bank_account_id,
                'status' => (bool) $supplier->status,
                'orders_count' => $supplier->orders_count,
            ]);

        $recurring = TreasuryRecurringExpense::query()
            ->with(['supplier', 'category', 'account', 'runs'])
            ->orderByDesc('status')
            ->orderBy('next_run_date')
            ->get()
            ->map(function (TreasuryRecurringExpense $expense) {
                $lastRun = $expense->runs->sortByDesc('id')->first();

                return [
                    'id' => $expense->id,
                    'concept' => $expense->concept,
                    'type' => $expense->type,
                    'type_label' => TreasuryRecurringExpense::TYPE_LABELS[$expense->type] ?? $expense->type,
                    'frequency' => $expense->frequency,
                    'frequency_label' => $expense->frequencyLabel(),
                    'amount' => (float) $expense->amount,
                    'currency' => $expense->currency,
                    'supplier' => $expense->supplier?->name,
                    'category' => $expense->category?->name,
                    'account' => $expense->account?->displayName(),
                    'next_run_date' => $expense->next_run_date?->format('Y-m-d'),
                    'auto_pay' => (bool) $expense->auto_pay,
                    'status' => (bool) $expense->status,
                    'last_run_at' => $expense->last_run_at?->format('d/m/Y H:i'),
                    'last_error' => $lastRun?->error,
                ];
            });

        $categories = TreasuryCategory::query()
            ->whereIn('applies_to', ['expense', 'both'])
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $accounts = TreasuryAccount::query()
            ->active()
            ->with(['bankAccount.bank', 'companyBilletera.billetera'])
            ->orderBy('type')
            ->get()
            ->map(fn (TreasuryAccount $account) => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
            ]);

        return Inertia::render('Treasury::Payments/Index', [
            'orders' => $orders,
            'suppliers' => $suppliers,
            'recurring' => $recurring,
            'categories' => $categories,
            'accounts' => $accounts,
            'costCenters' => TreasuryCostCenter::query()->orderBy('code')->get(['id', 'code', 'name']),
            'paymentMethods' => \App\Models\PaymentMethod::query()->orderBy('description')->get(['id', 'description']),
            'bankAccounts' => \App\Models\BankAccount::query()->with('bank')->get()->map(fn ($item) => [
                'id' => $item->id,
                'label' => trim(($item->bank?->short_name ? $item->bank->short_name . ' ' : '') . ($item->description ?? $item->number)),
            ]),
            'options' => [
                'types' => collect(TreasuryPaymentOrder::TYPE_LABELS)->map(fn ($label, $code) => ['code' => $code, 'label' => $label])->values(),
                'frequencies' => collect(TreasuryRecurringExpense::FREQUENCY_LABELS)->map(fn ($label, $code) => ['code' => $code, 'label' => $label])->values(),
                'recurring_types' => collect(TreasuryRecurringExpense::TYPE_LABELS)->map(fn ($label, $code) => ['code' => $code, 'label' => $label])->values(),
                'approval_threshold' => (float) config('treasury.approval_threshold', 0),
                'require_different_approver' => (bool) config('treasury.require_different_approver', true),
            ],
            'bulk' => [
                'candidates' => $this->bulk->candidates(),
                'formats' => collect(TreasuryBulkPaymentService::FORMATS)
                    ->map(fn ($label, $code) => ['code' => $code, 'label' => $label])
                    ->values(),
            ],
            'can' => [
                'create' => auth()->user()?->hasPermissionTo('treasury_pagos_nuevo'),
                'approve' => auth()->user()?->hasPermissionTo('treasury_pagos_aprobar'),
                'pay' => auth()->user()?->hasPermissionTo('treasury_pagos_ejecutar'),
                'void' => auth()->user()?->hasPermissionTo('treasury_pagos_anular'),
                'suppliers' => auth()->user()?->hasPermissionTo('treasury_proveedores'),
                'recurring' => auth()->user()?->hasPermissionTo('treasury_recurrentes'),
                'bulk' => auth()->user()?->hasPermissionTo('treasury_pagos_masivos'),
            ],
        ]);
    }

    /**
     * Genera el archivo del lote para el banco. No ejecuta pagos: deja el lote
     * y la fecha de exportación en cada orden.
     */
    public function exportBulk(Request $request)
    {
        $this->authorizePermission('treasury_pagos_masivos');

        $data = $request->validate([
            'orders' => ['required', 'array', 'min:1'],
            'orders.*' => ['integer'],
            'format' => ['required', 'in:' . implode(',', array_keys(TreasuryBulkPaymentService::FORMATS))],
            'date' => ['nullable', 'date'],
        ]);

        try {
            $file = $this->bulk->generate(
                $data['orders'],
                $data['format'],
                $data['date'] ?? null,
                auth()->id()
            );
        } catch (TreasuryOperationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->streamDownload(
            fn () => print $file['content'],
            $file['filename'],
            [
                'Content-Type' => $file['mime'],
                'X-Treasury-Batch' => $file['batch'],
                'X-Treasury-Count' => (string) $file['count'],
                'X-Treasury-Total' => number_format((float) $file['total'], 2, '.', ''),
                'X-Treasury-Currency' => $file['currency'],
                'X-Treasury-Warnings' => rawurlencode(implode(' | ', $file['warnings'])),
            ]
        );
    }

    public function store(Request $request)
    {
        $this->authorizePermission('treasury_pagos_nuevo');

        $data = $request->validate([
            'type' => ['required', 'in:' . implode(',', array_keys(TreasuryPaymentOrder::TYPE_LABELS))],
            'supplier_id' => ['nullable', 'integer', 'exists:treasury_suppliers,id'],
            'cost_center_id' => ['nullable', 'integer', 'exists:treasury_cost_centers,id'],
            'description' => ['required', 'string', 'max:500'],
            'treasury_category_id' => ['nullable', 'integer', 'exists:treasury_categories,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'scheduled_date' => ['nullable', 'date'],
            'treasury_account_id' => ['nullable', 'integer', 'exists:treasury_accounts,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('treasury/payments', 'public');
        }

        try {
            $order = $this->orders->create($data, auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $order->status === TreasuryPaymentOrder::STATUS_PENDING
            ? "Orden {$order->code} creada y enviada a aprobación."
            : "Orden {$order->code} creada y aprobada.";

        return back()->with('success', $message);
    }

    public function approve(int $id)
    {
        $this->authorizePermission('treasury_pagos_aprobar');

        try {
            $order = $this->orders->approve(TreasuryPaymentOrder::findOrFail($id), auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Orden {$order->code} aprobada.");
    }

    public function reject(Request $request, int $id)
    {
        $this->authorizePermission('treasury_pagos_aprobar');

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        try {
            $order = $this->orders->reject(TreasuryPaymentOrder::findOrFail($id), $data['reason'], auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Orden {$order->code} rechazada.");
    }

    public function pay(Request $request, int $id)
    {
        $this->authorizePermission('treasury_pagos_ejecutar');

        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'treasury_account_id' => ['nullable', 'integer', 'exists:treasury_accounts,id'],
        ]);

        try {
            $order = $this->orders->pay(
                TreasuryPaymentOrder::findOrFail($id),
                auth()->id(),
                $data['date'] ?? null,
                $data['treasury_account_id'] ?? null
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Orden {$order->code} pagada y registrada en el libro.");
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizePermission('treasury_pagos_anular');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $order = $this->orders->void(
                TreasuryPaymentOrder::findOrFail($id),
                auth()->id(),
                $data['reason'] ?? null
            );
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Orden {$order->code} anulada. Nada se eliminó: quedó el rastro en auditoría.");
    }
}
