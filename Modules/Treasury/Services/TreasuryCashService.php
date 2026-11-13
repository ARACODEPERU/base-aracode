<?php

namespace Modules\Treasury\Services;

use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\PettyCash;
use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Sales\Entities\SalePaymentQuota;

/**
 * Panel de efectivo de Tesorería.
 *
 * El efectivo no entra al libro de bancos: se controla con la caja chica
 * (petty_cashes + sales.petty_cash_id + expenses) para no duplicar saldos ni
 * mezclar dinero que no está en el banco.
 *
 * Define "efectivo" con la misma regla que el libro: los métodos excluidos de
 * config('treasury.excluded_payment_methods'). Solo suma importes en soles;
 * una venta en moneda extranjera no se incluye en el saldo de caja.
 */
class TreasuryCashService
{
    public function __construct(
        private PaymentAccountResolver $resolver,
        private TreasuryLedgerService $ledger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(?string $from = null, ?string $to = null): array
    {
        $from = $from ?: Carbon::now()->startOfMonth()->toDateString();
        $to = $to ?: Carbon::now()->toDateString();

        $cashIds = $this->resolver->excludedMethodIds();

        $movements = collect($this->cashSales($from, $to, $cashIds))
            ->merge($this->cashQuotaPayments($from, $to, $cashIds))
            ->merge($this->cashExpenses($from, $to))
            ->sortByDesc('sort')
            ->values();

        $unsessioned = $movements
            ->filter(fn (array $row) => $row['kind'] !== 'egreso' && empty($row['petty_cash_id']))
            ->values();

        $sessions = $this->sessions($from, $to, $movements);

        return [
            'from' => $from,
            'to' => $to,
            'movements' => $movements->all(),
            'sessions' => $sessions,
            'unsessioned' => $unsessioned->all(),
            'cashMethods' => $this->cashMethodNames($cashIds),
            'totals' => [
                'sales' => round($movements->where('kind', 'venta')->sum('amount'), 2),
                'quotas' => round($movements->where('kind', 'cuota')->sum('amount'), 2),
                'expenses' => round($movements->where('kind', 'egreso')->sum('amount'), 2),
                'net' => round($movements->sum(
                    fn (array $row) => $row['kind'] === 'egreso' ? -1 * $row['amount'] : $row['amount']
                ), 2),
                'unsessioned' => round($unsessioned->sum('amount'), 2),
                'unsessioned_count' => $unsessioned->count(),
                'sessions' => count($sessions),
                'counts' => [
                    'sales' => $movements->where('kind', 'venta')->count(),
                    'quotas' => $movements->where('kind', 'cuota')->count(),
                    'expenses' => $movements->where('kind', 'egreso')->count(),
                ],
            ],
        ];
    }

    /**
     * Ventas del período cuyo pago se hizo con un método de efectivo.
     *
     * @param  array<int, int>  $cashIds
     * @return array<int, array<string, mixed>>
     */
    private function cashSales(string $from, string $to, array $cashIds): array
    {
        if (empty($cashIds)) {
            return [];
        }

        $rows = [];

        $sales = Sale::query()
            ->with(['client', 'document'])
            ->where('status', 1)
            ->whereBetween('sale_date', [$from, $to])
            ->orderBy('id')
            ->get();

        foreach ($sales as $sale) {
            $document = $sale->document;

            // La caja chica se lleva en soles: no se mezcla moneda extranjera.
            if ($document && strtoupper((string) $document->invoice_type_currency) === 'USD') {
                continue;
            }

            $amount = 0.0;

            foreach ($this->ledger->normalizeSalePayments($sale->payments) as $payment) {
                $methodId = (int) ($payment['payment_method_id'] ?? 0);

                if ($methodId > 0 && in_array($methodId, $cashIds, true)) {
                    $amount += (float) $payment['amount'];
                }
            }

            if ($amount <= 0) {
                continue;
            }

            $date = $sale->sale_date ? Carbon::parse($sale->sale_date)->toDateString() : null;

            $rows[] = [
                'sort' => ($date ?? '') . '|' . str_pad((string) $sale->id, 10, '0', STR_PAD_LEFT),
                'date' => $date,
                'kind' => 'venta',
                'label' => 'Venta en efectivo',
                'document' => $document ? trim($document->invoice_serie . '-' . $document->invoice_correlative) : null,
                'client' => $sale->client?->full_name,
                'reference' => 'Venta #' . $sale->id,
                'amount' => round($amount, 2),
                'petty_cash_id' => $sale->petty_cash_id ? (int) $sale->petty_cash_id : null,
            ];
        }

        return $rows;
    }

    /**
     * Abonos de cuota cobrados en efectivo.
     *
     * @param  array<int, int>  $cashIds
     * @return array<int, array<string, mixed>>
     */
    private function cashQuotaPayments(string $from, string $to, array $cashIds): array
    {
        if (empty($cashIds)) {
            return [];
        }

        $payments = SalePaymentQuota::query()
            ->whereIn('payment_method_id', $cashIds)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            return [];
        }

        $quotas = SaleDocumentQuota::query()
            ->whereIn('id', $payments->pluck('sale_document_quota_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        $documents = SaleDocument::query()
            ->whereIn('id', $quotas->pluck('sale_document_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        $sales = Sale::query()
            ->whereIn('id', $documents->pluck('sale_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($payments as $payment) {
            $quota = $quotas->get($payment->sale_document_quota_id);
            $document = $quota ? $documents->get($quota->sale_document_id) : null;
            $sale = $document ? $sales->get($document->sale_id) : null;
            $date = $payment->payment_date ? Carbon::parse($payment->payment_date)->toDateString() : null;

            $rows[] = [
                'sort' => ($date ?? '') . '|' . str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT),
                'date' => $date,
                'kind' => 'cuota',
                'label' => 'Abono de cuota en efectivo',
                'document' => $document ? trim($document->invoice_serie . '-' . $document->invoice_correlative) : null,
                'client' => $document?->client_rzn_social,
                'reference' => $payment->reference,
                'amount' => round((float) $payment->amount_applied, 2),
                'petty_cash_id' => $sale?->petty_cash_id ? (int) $sale->petty_cash_id : null,
            ];
        }

        return $rows;
    }

    /**
     * Egresos registrados en caja chica (la tabla no guarda fecha propia: se
     * usa la de creación del registro).
     *
     * @return array<int, array<string, mixed>>
     */
    private function cashExpenses(string $from, string $to): array
    {
        $rows = [];

        $expenses = Expense::query()
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            $date = $expense->created_at ? $expense->created_at->toDateString() : null;

            $rows[] = [
                'sort' => ($date ?? '') . '|' . str_pad((string) $expense->id, 10, '0', STR_PAD_LEFT),
                'date' => $date,
                'kind' => 'egreso',
                'label' => 'Egreso de caja',
                'document' => $expense->document,
                'client' => null,
                'reference' => $expense->description,
                'amount' => round((float) $expense->amount, 2),
                'petty_cash_id' => $expense->petty_cash_id ? (int) $expense->petty_cash_id : null,
            ];
        }

        return $rows;
    }

    /**
     * Sesiones de caja del período con sus totales de efectivo.
     *
     * @param  Collection<int, array<string, mixed>>  $movements
     * @return array<int, array<string, mixed>>
     */
    private function sessions(string $from, string $to, Collection $movements): array
    {
        return PettyCash::query()
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('date_opening', [$from, $to])
                    ->orWhere('state', 1);
            })
            ->orderByDesc('date_opening')
            ->limit(200)
            ->get()
            ->map(function (PettyCash $cash) use ($movements) {
                $rows = $movements->where('petty_cash_id', (int) $cash->id);

                $sales = round($rows->where('kind', 'venta')->sum('amount'), 2);
                $quotas = round($rows->where('kind', 'cuota')->sum('amount'), 2);
                $expenses = round($rows->where('kind', 'egreso')->sum('amount'), 2);
                $beginning = round((float) $cash->beginning_balance, 2);

                return [
                    'id' => $cash->id,
                    'opening' => (string) $cash->date_opening,
                    'closed' => $cash->date_closed ? (string) $cash->date_closed : null,
                    'is_open' => (int) $cash->state === 1,
                    'seller' => $cash->seller_name,
                    'beginning_balance' => $beginning,
                    'sales' => $sales,
                    'quotas' => $quotas,
                    'expenses' => $expenses,
                    'balance' => round($beginning + $sales + $quotas - $expenses, 2),
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, int>  $cashIds
     * @return array<int, string>
     */
    private function cashMethodNames(array $cashIds): array
    {
        if (empty($cashIds)) {
            return [];
        }

        return PaymentMethod::query()
            ->whereIn('id', $cashIds)
            ->orderBy('description')
            ->pluck('description')
            ->all();
    }
}
