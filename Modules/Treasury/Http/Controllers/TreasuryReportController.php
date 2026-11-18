<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryBudget;
use Modules\Treasury\Entities\TreasuryCategory;
use Modules\Treasury\Entities\TreasuryCostCenter;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\TreasuryAuditService;
use Modules\Treasury\Services\TreasuryBudgetService;
use Modules\Treasury\Services\TreasuryForecastService;
use Modules\Treasury\Services\TreasuryReportService;

/**
 * Reportes de tesorería: posición consolidada, flujo por categoría y por
 * centro de costo. Cada moneda se informa por separado; solo el consolidado
 * se convierte a soles, con la fecha del tipo de cambio.
 */
class TreasuryReportController extends Controller
{
    use ValidatesTreasuryRequests;

    public function __construct(
        private TreasuryReportService $reports,
        private TreasuryForecastService $forecast,
        private TreasuryAuditService $audit,
        private TreasuryBudgetService $budgets,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('treasury_reportes');

        $from = $request->get('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->get('to') ?: now()->toDateString();
        $currency = strtoupper((string) ($request->get('currency') ?: 'PEN')) ?: 'PEN';
        $costCenterId = $request->integer('cost_center_id') ?: null;

        // Horizonte de la proyección: se reporta aparte del rango histórico.
        $days = max(7, min(180, (int) ($request->get('days') ?: 90)));

        return Inertia::render('Treasury::Reports/Index', [
            'position' => $this->reports->position($to),
            'flows' => $this->reports->flows($from, $to, $currency, $costCenterId),
            'byCostCenter' => $this->reports->byCostCenter($from, $to, $currency),
            'totalsByCurrency' => $this->reports->totalsByCurrency($from, $to),
            'costCenters' => $this->reports->costCenters(),
            'currencies' => $this->reports->currencies(),
            'projection' => $this->forecast->build($currency, $days),
            'budgets' => $this->budgets->build((string) ($request->get('month') ?: now()->format('Y-m'))),
            'budgetOptions' => [
                'costCenters' => TreasuryCostCenter::query()->orderBy('code')->get(['id', 'code', 'name']),
                'categories' => TreasuryCategory::query()
                    ->whereIn('applies_to', ['expense', 'both'])
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
            'filters' => [
                'from' => $from,
                'to' => $to,
                'currency' => $currency,
                'cost_center_id' => $costCenterId,
                'preset' => (string) ($request->get('preset') ?: 'custom'),
                'month' => (string) ($request->get('month') ?: ''),
                'year' => (int) ($request->get('year') ?: 0),
                'days' => $days,
            ],
            'canManageCenters' => auth()->user()?->hasPermissionTo('treasury_reportes'),
            'canManageBudgets' => auth()->user()?->hasPermissionTo('treasury_presupuesto'),
        ]);
    }

    /**
     * Crea o actualiza el presupuesto mensual de un centro de costo.
     */
    public function storeBudget(Request $request)
    {
        $this->authorizePermission('treasury_presupuesto');

        $data = $request->validate([
            'month' => ['required', 'regex:/^\\d{4}-(0[1-9]|1[0-2])$/'],
            'cost_center_id' => ['required', 'integer', 'exists:treasury_cost_centers,id'],
            'treasury_category_id' => ['nullable', 'integer', 'exists:treasury_categories,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $budget = $this->budgets->save($data, auth()->id());
        } catch (TreasuryOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Presupuesto de ' . $budget->costCenter?->name . ' guardado.');
    }

    public function destroyBudget(int $id)
    {
        $this->authorizePermission('treasury_presupuesto');

        $this->budgets->remove(TreasuryBudget::findOrFail($id), auth()->id());

        return back()->with('success', 'Presupuesto eliminado. Los movimientos reales no se tocaron.');
    }

    public function storeCostCenter(Request $request)
    {
        $this->authorizePermission('treasury_reportes');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:treasury_cost_centers,code'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $center = TreasuryCostCenter::create([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'status' => true,
        ]);

        $this->audit->log('cost_center', $center->id, 'created', [], $center->only(['code', 'name']));

        return back()->with('success', 'Centro de costo creado.');
    }

    public function updateCostCenter(Request $request, int $id)
    {
        $this->authorizePermission('treasury_reportes');

        $center = TreasuryCostCenter::findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:treasury_cost_centers,code,' . $center->id],
            'name' => ['required', 'string', 'max:120'],
            'status' => ['boolean'],
        ]);

        $center->update([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'status' => $request->boolean('status'),
        ]);

        $this->audit->log('cost_center', $center->id, 'updated', [], $center->only(['code', 'name', 'status']));

        return back()->with('success', 'Centro de costo actualizado.');
    }

    public function destroyCostCenter(int $id)
    {
        $this->authorizePermission('treasury_reportes');

        $center = TreasuryCostCenter::findOrFail($id);

        if ($center->transactions()->exists() || $center->paymentOrders()->exists()) {
            $center->update(['status' => false]);

            return back()->with('success', 'El centro de costo tiene movimientos: se desactivó en lugar de eliminarse.');
        }

        $center->delete();

        return back()->with('success', 'Centro de costo eliminado.');
    }
}
