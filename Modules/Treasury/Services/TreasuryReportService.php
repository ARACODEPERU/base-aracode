<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryCostCenter;
use Modules\Treasury\Entities\TreasuryTransaction;

/**
 * Reportes de tesorería con dimensión de moneda y centro de costo.
 *
 * La posición nunca mezcla monedas: cada grupo se muestra en su moneda y,
 * aparte, el consolidado en soles con el tipo de cambio y su fecha.
 */
class TreasuryReportService
{
    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryCurrencyService $currency,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function position(?string $until = null): array
    {
        $data = $this->ledger->consolidatedBalances($until);

        return [
            'balances' => collect($data['balances'])->map(fn (array $row) => [
                'id' => $row['account']->id,
                'label' => $row['account']->displayName(),
                'type' => $row['account']->type,
                'currency' => $this->currency->accountCurrency($row['account']),
                'balance' => (float) $row['balance'],
            ])->all(),
            'groups' => $data['consolidation']['groups'],
            'consolidated' => $data['consolidation']['consolidated'],
            // Dinero restringido: se informa aparte y no suma al consolidado.
            'detraction' => $data['consolidation']['detraction'] ?? 0.0,
            'rates' => $data['consolidation']['rates'],
            'incomplete' => $data['consolidation']['incomplete'],
            'base_currency' => $this->currency->base(),
            'until' => $until,
        ];
    }

    /**
     * Flujo por categoría dentro de un rango y una moneda.
     *
     * @return array<string, mixed>
     */
    public function flows(string $from, string $to, string $currency, ?int $costCenterId = null): array
    {
        $currency = $this->currency->normalize($currency);

        $rows = TreasuryTransaction::query()
            ->active()
            ->when(app(TreasurySettingsService::class)->startsOn(), fn ($q, $start) => $q->whereDate('transaction_date', '>=', $start))
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->where('currency', $currency)
            ->when($costCenterId, fn ($query) => $query->where('cost_center_id', $costCenterId))
            ->leftJoin('treasury_categories', 'treasury_categories.id', '=', 'treasury_transactions.treasury_category_id')
            ->selectRaw("
                COALESCE(treasury_categories.name, 'Sin categoría') AS category,
                COALESCE(treasury_categories.color, '#94a3b8') AS color,
                treasury_transactions.type AS type,
                SUM(treasury_transactions.amount) AS total,
                COUNT(*) AS movements
            ")
            ->groupBy('category', 'color', 'treasury_transactions.type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'color' => $row->color,
                'type' => $row->type,
                'total' => round((float) $row->total, 2),
                'movements' => (int) $row->movements,
            ])
            ->all();

        $incomes = array_sum(array_map(fn ($row) => $row['type'] === 'income' ? $row['total'] : 0, $rows));
        $expenses = array_sum(array_map(fn ($row) => $row['type'] === 'expense' ? $row['total'] : 0, $rows));

        return [
            'currency' => $currency,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'incomes' => round($incomes, 2),
            'expenses' => round($expenses, 2),
            'net' => round($incomes - $expenses, 2),
        ];
    }

    /**
     * Flujo por centro de costo (dimensión de gestión).
     *
     * @return array<int, array<string, mixed>>
     */
    public function byCostCenter(string $from, string $to, string $currency): array
    {
        $currency = $this->currency->normalize($currency);

        $rows = TreasuryTransaction::query()
            ->active()
            ->when(app(TreasurySettingsService::class)->startsOn(), fn ($q, $start) => $q->whereDate('transaction_date', '>=', $start))
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->where('currency', $currency)
            ->leftJoin('treasury_cost_centers', 'treasury_cost_centers.id', '=', 'treasury_transactions.cost_center_id')
            ->selectRaw("
                COALESCE(treasury_cost_centers.name, 'Sin centro de costo') AS center,
                treasury_transactions.type AS type,
                SUM(treasury_transactions.amount) AS total,
                COUNT(*) AS movements
            ")
            ->groupBy('center', 'treasury_transactions.type')
            ->orderByDesc('total')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row->center] ??= ['center' => $row->center, 'incomes' => 0.0, 'expenses' => 0.0, 'movements' => 0];

            if ($row->type === 'income') {
                $grouped[$row->center]['incomes'] += (float) $row->total;
            } else {
                $grouped[$row->center]['expenses'] += (float) $row->total;
            }

            $grouped[$row->center]['movements'] += (int) $row->movements;
        }

        return array_values(array_map(function (array $row) {
            $row['incomes'] = round($row['incomes'], 2);
            $row['expenses'] = round($row['expenses'], 2);
            $row['net'] = round($row['incomes'] - $row['expenses'], 2);

            return $row;
        }, $grouped));
    }

    /**
     * Totales por moneda del periodo (sin consolidar).
     *
     * @return array<int, array<string, mixed>>
     */
    public function totalsByCurrency(string $from, string $to): array
    {
        return TreasuryTransaction::query()
            ->active()
            ->when(app(TreasurySettingsService::class)->startsOn(), fn ($q, $start) => $q->whereDate('transaction_date', '>=', $start))
            ->whereDate('transaction_date', '>=', $from)
            ->whereDate('transaction_date', '<=', $to)
            ->selectRaw("
                currency,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS incomes,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expenses,
                COUNT(*) AS movements
            ")
            ->groupBy('currency')
            ->orderBy('currency')
            ->get()
            ->map(fn ($row) => [
                'currency' => $row->currency ?: 'PEN',
                'incomes' => round((float) $row->incomes, 2),
                'expenses' => round((float) $row->expenses, 2),
                'net' => round((float) $row->incomes - (float) $row->expenses, 2),
                'movements' => (int) $row->movements,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function costCenters(): array
    {
        return TreasuryCostCenter::query()
            ->orderBy('code')
            ->get()
            ->map(fn (TreasuryCostCenter $center) => [
                'id' => $center->id,
                'code' => $center->code,
                'name' => $center->name,
                'status' => (bool) $center->status,
                'movements' => TreasuryTransaction::query()->where('cost_center_id', $center->id)->count(),
            ])
            ->all();
    }

    /**
     * Cuentas y monedas para los selectores de la pantalla.
     *
     * @return array<int, string>
     */
    public function currencies(): array
    {
        $list = DB::table('treasury_accounts')
            ->where('status', true)
            ->pluck('currency_type_id')
            ->map(fn ($value) => strtoupper(trim((string) $value)) ?: 'PEN')
            ->unique()
            ->values()
            ->all();

        return $list !== [] ? $list : ['PEN'];
    }
}
