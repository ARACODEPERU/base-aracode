<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Modules\Treasury\Entities\TreasuryBudget;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Presupuesto mensual por centro de costo.
 *
 * El presupuesto es un monto planificado; la ejecución se calcula siempre
 * contra los egresos reales del libro (nunca se guarda un acumulado). Si el
 * presupuesto tiene categoría, solo compara esa categoría; si no, compara todos
 * los egresos del centro en el mes.
 */
class TreasuryBudgetService
{
    private const MONTHS = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function __construct(
        private TreasuryAuditService $audit,
        private TreasurySettingsService $settings,
    ) {
    }

    /**
     * Presupuestos del mes con su ejecución real.
     *
     * @return array<string, mixed>
     */
    public function build(?string $month = null): array
    {
        [$year, $monthNumber, $label] = $this->parseMonth($month);

        $start = Carbon::create($year, $monthNumber, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $budgets = TreasuryBudget::query()
            ->forPeriod($year, $monthNumber)
            ->with(['costCenter', 'category'])
            ->orderBy('cost_center_id')
            ->orderBy('treasury_category_id')
            ->get();

        $executed = $this->executedByCenter($start, $end);

        $rows = $budgets->map(function (TreasuryBudget $budget) use ($executed) {
            $currency = strtoupper((string) ($budget->currency ?: 'PEN')) ?: 'PEN';
            $center = (int) $budget->cost_center_id;
            $category = $budget->treasury_category_id ? (int) $budget->treasury_category_id : null;

            // Sin categoría: el presupuesto cubre todos los egresos del centro.
            $real = $executed
                ->filter(fn (array $row) => $row['cost_center_id'] === $center
                    && $row['currency'] === $currency
                    && ($category === null || $row['treasury_category_id'] === $category))
                ->sum('total');

            $amount = round((float) $budget->amount, 2);
            $real = round((float) $real, 2);
            $usage = $amount > 0 ? round(($real / $amount) * 100, 1) : null;

            return [
                'id' => $budget->id,
                'cost_center_id' => $center,
                'center' => $budget->costCenter
                    ? $budget->costCenter->code . ' · ' . $budget->costCenter->name
                    : 'Centro #' . $center,
                'category_id' => $category,
                'category' => $budget->category?->name ?? 'Todas las categorías',
                'currency' => $currency,
                'amount' => $amount,
                'executed' => $real,
                'available' => round($amount - $real, 2),
                'usage' => $usage,
                'status' => $usage === null ? 'ok' : ($usage > 100 ? 'over' : ($usage >= 80 ? 'warning' : 'ok')),
                'notes' => $budget->notes,
            ];
        })->sortByDesc('usage')->values();

        $totals = $rows
            ->groupBy('currency')
            ->map(fn ($group, $currency) => [
                'currency' => $currency,
                'budget' => round((float) $group->sum('amount'), 2),
                'executed' => round((float) $group->sum('executed'), 2),
                'available' => round((float) $group->sum('available'), 2),
                'usage' => ((float) $group->sum('amount')) > 0
                    ? round(((float) $group->sum('executed') / (float) $group->sum('amount')) * 100, 1)
                    : null,
            ])
            ->values()
            ->all();

        return [
            'month' => sprintf('%04d-%02d', $year, $monthNumber),
            'label' => $label,
            'rows' => $rows->all(),
            'totals' => $totals,
            'over' => $rows->where('status', 'over')->count(),
            'warning' => $rows->where('status', 'warning')->count(),
        ];
    }

    /**
     * Crea o actualiza el presupuesto de la misma clave (mes, centro,
     * categoría y moneda).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws TreasuryOperationException
     */
    public function save(array $data, ?int $userId = null): TreasuryBudget
    {
        [$year, $month] = $this->parseMonth($data['month'] ?? null);

        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new TreasuryOperationException('El monto del presupuesto debe ser mayor que cero.');
        }

        if (empty($data['cost_center_id'])) {
            throw new TreasuryOperationException('Elige un centro de costo para el presupuesto.');
        }

        $categoryId = ! empty($data['treasury_category_id']) ? (int) $data['treasury_category_id'] : null;
        $currency = strtoupper(trim((string) ($data['currency'] ?? 'PEN'))) ?: 'PEN';

        $query = TreasuryBudget::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('cost_center_id', (int) $data['cost_center_id'])
            ->where('currency', $currency);

        $categoryId === null
            ? $query->whereNull('treasury_category_id')
            : $query->where('treasury_category_id', $categoryId);

        $budget = $query->first();
        $created = $budget === null;

        $budget ??= new TreasuryBudget([
            'period_year' => $year,
            'period_month' => $month,
            'cost_center_id' => (int) $data['cost_center_id'],
            'treasury_category_id' => $categoryId,
            'currency' => $currency,
        ]);

        $old = $budget->exists ? ['amount' => (float) $budget->amount] : [];

        $budget->fill([
            'amount' => $amount,
            'notes' => $data['notes'] ?? null,
            'created_by' => $budget->created_by ?: $userId,
        ])->save();

        $this->audit->log('budget', $budget->id, $created ? 'created' : 'updated', $old, [
            'period' => sprintf('%04d-%02d', $year, $month),
            'cost_center_id' => $budget->cost_center_id,
            'treasury_category_id' => $categoryId,
            'currency' => $currency,
            'amount' => $amount,
        ]);

        return $budget->load(['costCenter', 'category']);
    }

    public function remove(TreasuryBudget $budget, ?int $userId = null): void
    {
        $this->audit->log('budget', $budget->id, 'deleted', [
            'period' => sprintf('%04d-%02d', $budget->period_year, $budget->period_month),
            'amount' => (float) $budget->amount,
        ], []);

        $budget->delete();
    }

    /**
     * Presupuestos excedidos del mes en curso (para las alertas del dashboard).
     *
     * @return array<int, array<string, mixed>>
     */
    public function exceeded(): array
    {
        $data = $this->build(now()->format('Y-m'));

        return array_values(array_filter(
            $data['rows'],
            fn (array $row) => $row['status'] === 'over'
        ));
    }

    /**
     * Egresos del mes agrupados por centro, categoría y moneda.
     *
     * @return \Illuminate\Support\Collection<int, array{cost_center_id: int, treasury_category_id: ?int, currency: string, total: float}>
     */
    private function executedByCenter(Carbon $start, Carbon $end)
    {
        $query = TreasuryTransaction::query()
            ->active()
            ->where('type', TreasuryTransaction::TYPE_EXPENSE)
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->whereNotNull('cost_center_id');

        // Lo anterior a la fecha de inicio del módulo no cuenta como ejecución.
        $this->settings->applyCutoff($query);

        return $query
            ->get(['cost_center_id', 'treasury_category_id', 'currency', 'amount'])
            ->groupBy(fn ($row) => $row->cost_center_id . '|' . ($row->treasury_category_id ?: '0')
                . '|' . strtoupper((string) ($row->currency ?: 'PEN')))
            ->map(function ($rows) {
                $first = $rows->first();

                return [
                    'cost_center_id' => (int) $first->cost_center_id,
                    'treasury_category_id' => $first->treasury_category_id ? (int) $first->treasury_category_id : null,
                    'currency' => strtoupper((string) ($first->currency ?: 'PEN')) ?: 'PEN',
                    'total' => round((float) $rows->sum('amount'), 2),
                ];
            })
            ->values();
    }

    /**
     * @return array{0: int, 1: int, 2: string}
     */
    private function parseMonth(?string $month): array
    {
        $month = (string) $month;

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = now()->format('Y-m');
        }

        [$year, $monthNumber] = array_map('intval', explode('-', $month));

        return [$year, $monthNumber, (self::MONTHS[$monthNumber] ?? $monthNumber) . ' ' . $year];
    }
}
