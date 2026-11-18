<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryDocument;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\TreasuryLedgerService;

/**
 * Alertas automáticas de Tesorería.
 *
 * Se calculan al abrir el dashboard (no se guardan): saldos negativos o bajo el
 * mínimo, órdenes vencidas, aprobaciones detenidas, cajas abiertas de un día
 * para otro, cheques/letras vencidos o por vencer, IGV impago, presupuestos
 * excedidos, movimientos sin conciliar y la cuenta de detracción sin número.
 *
 * Cada alerta lleva su gravedad y, cuando corresponde, el enlace a la pantalla
 * donde se resuelve.
 */
class TreasuryAlertService
{
    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryBudgetService $budgets,
        private TreasuryDocumentService $documents,
        private TreasuryTaxService $taxes,
        private TreasurySettingsService $settings,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function build(): array
    {
        $alerts = [];

        $this->negativeBalances($alerts);
        $this->lowBalances($alerts);
        $this->overdueOrders($alerts);
        $this->pendingApprovals($alerts);
        $this->openCashSessions($alerts);
        $this->documents($alerts);
        $this->taxes($alerts);
        $this->budgets($alerts);
        $this->unreconciled($alerts);
        $this->detractionAccount($alerts);

        $order = ['danger' => 0, 'warning' => 1, 'info' => 2];

        usort($alerts, fn (array $a, array $b) => ($order[$a['severity']] ?? 3) <=> ($order[$b['severity']] ?? 3));

        return $alerts;
    }

    /**
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function negativeBalances(array &$alerts): void
    {
        foreach ($this->ledger->balancesByAccount() as $row) {
            if ($row['balance'] >= -0.009) {
                continue;
            }

            $alerts[] = $this->alert(
                'negative_balance_' . $row['account']->id,
                'danger',
                'Saldo negativo en ' . $row['account']->displayName(),
                'El libro muestra ' . number_format((float) $row['balance'], 2, '.', '')
                . '. Revisa los últimos movimientos o registra el saldo inicial real.',
                'treasury_accounts_index'
            );
        }
    }

    /**
     * Saldo consolidado por moneda por debajo del mínimo configurado.
     *
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function lowBalances(array &$alerts): void
    {
        $minimum = (float) config('treasury.forecast_min_balance', 0);

        if ($minimum <= 0) {
            return;
        }

        $balances = $this->ledger->balancesByAccount();
        $groups = collect($balances)
            ->reject(fn (array $row) => $row['account']->isDetraction())
            ->groupBy(fn (array $row) => strtoupper((string) ($row['account']->currency_type_id ?: 'PEN')) ?: 'PEN')
            ->map(fn ($rows, $currency) => [
                'currency' => $currency,
                'total' => round((float) $rows->sum('balance'), 2),
            ]);

        foreach ($groups as $group) {
            if ($group['total'] >= $minimum) {
                continue;
            }

            $alerts[] = $this->alert(
                'low_balance_' . $group['currency'],
                'warning',
                'Saldo bajo en ' . $group['currency'],
                'El disponible es ' . number_format($group['total'], 2, '.', '')
                . ' y el mínimo de referencia es ' . number_format($minimum, 2, '.', '') . '.',
                'treasury_forecast_index'
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function overdueOrders(array &$alerts): void
    {
        $orders = TreasuryPaymentOrder::query()
            ->open()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', Carbon::today()->toDateString())
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $alerts[] = $this->alert(
            'overdue_orders',
            'danger',
            $orders->count() . ' orden(es) de pago vencidas',
            'Hay ' . number_format((float) $orders->sum('amount'), 2, '.', '')
            . ' pendientes de pago con vencimiento anterior a hoy.',
            'treasury_payments_index',
            ['count' => $orders->count()]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function pendingApprovals(array &$alerts): void
    {
        $orders = TreasuryPaymentOrder::query()
            ->where('status', TreasuryPaymentOrder::STATUS_PENDING)
            ->where('created_at', '<=', now()->subDays(2))
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $alerts[] = $this->alert(
            'pending_approvals',
            'warning',
            $orders->count() . ' pago(s) esperando aprobación',
            'Llevan más de dos días sin aprobarse. Revisa quién debe autorizarlos.',
            'treasury_payments_index',
            ['count' => $orders->count()]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function openCashSessions(array &$alerts): void
    {
        $sessions = DB::table('petty_cashes')
            ->where('state', 1)
            ->whereDate('date_opening', '<', Carbon::today()->toDateString())
            ->get();

        if ($sessions->isEmpty()) {
            return;
        }

        $alerts[] = $this->alert(
            'open_cash_sessions',
            'warning',
            $sessions->count() . ' caja(s) sin arqueo ni cierre',
            'Quedaron abiertas de un día anterior. Registra el arqueo y ciérralas para no arrastrar diferencias.',
            'treasury_cash_index',
            ['count' => $sessions->count()]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function documents(array &$alerts): void
    {
        $summary = $this->documents->summary();

        if ($summary['overdue'] > 0) {
            $alerts[] = $this->alert(
                'documents_overdue',
                'danger',
                $summary['overdue'] . ' cheque(s)/letra(s) vencidos',
                'Están en cartera con vencimiento anterior a hoy. Gestiona el cobro o el pago.',
                'treasury_documents_index',
                ['count' => $summary['overdue']]
            );
        }

        if ($summary['due_soon'] > 0) {
            $alerts[] = $this->alert(
                'documents_due_soon',
                'info',
                $summary['due_soon'] . ' documento(s) vencen en 7 días',
                'Prepara el depósito o el pago para no caer en protesto.',
                'treasury_documents_index',
                ['count' => $summary['due_soon']]
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function taxes(array &$alerts): void
    {
        if (! (bool) config('treasury.tax_igv_enabled', true)) {
            return;
        }

        $period = Carbon::now()->subMonthNoOverflow()->format('Y-m');
        $due = $this->taxes->dueDate($period);
        $unpaid = $this->taxes->unpaidForPeriod($period, 'PEN');

        if ($unpaid <= 0) {
            return;
        }

        if ($due < Carbon::today()->toDateString()) {
            $alerts[] = $this->alert(
                'igv_overdue',
                'danger',
                'IGV de ' . $period . ' vencido',
                'Quedan ' . number_format($unpaid, 2, '.', '') . ' sin pagar desde el ' . Carbon::parse($due)->format('d/m/Y') . '.',
                'treasury_taxes_index'
            );

            return;
        }

        if (Carbon::parse($due)->lte(Carbon::today()->addDays(7))) {
            $alerts[] = $this->alert(
                'igv_due',
                'warning',
                'IGV de ' . $period . ' por vencer',
                'Vence el ' . Carbon::parse($due)->format('d/m/Y') . ': ' . number_format($unpaid, 2, '.', '') . '.',
                'treasury_taxes_index'
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function budgets(array &$alerts): void
    {
        foreach ($this->budgets->exceeded() as $row) {
            $alerts[] = $this->alert(
                'budget_over_' . $row['id'],
                'warning',
                'Presupuesto excedido: ' . $row['center'],
                $row['category'] . ' lleva ' . number_format((float) $row['executed'], 2, '.', '')
                . ' de ' . number_format((float) $row['amount'], 2, '.', '') . ' (' . $row['usage'] . ' %).',
                'treasury_reports_index'
            );
        }
    }

    /**
     * Movimientos bancarios anteriores al mes en curso sin conciliar.
     *
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function unreconciled(array &$alerts): void
    {
        $query = TreasuryTransaction::query()
            ->active()
            ->whereNull('reconciled_at')
            ->whereDate('transaction_date', '<', Carbon::now()->startOfMonth()->toDateString())
            ->whereHas('account', fn ($q) => $q->whereIn('type', ['bank', 'wallet']));

        $this->settings->applyCutoff($query);

        $count = (int) (clone $query)->count();

        if ($count === 0) {
            return;
        }

        $alerts[] = $this->alert(
            'unreconciled_old',
            'info',
            $count . ' movimiento(s) antiguos sin conciliar',
            'Son de meses anteriores y siguen abiertos en la conciliación bancaria.',
            'treasury_reconciliation_index',
            ['count' => $count]
        );
    }

    /**
     * La cuenta de detracción necesita banco y número para que los clientes
     * depositen; sin número, la instrucción de pago sale incompleta.
     *
     * @param  array<int, array<string, mixed>>  $alerts
     */
    private function detractionAccount(array &$alerts): void
    {
        $account = \Modules\Treasury\Entities\TreasuryAccount::query()->detraction()->active()->first();

        if (! $account || filled($account->account_number)) {
            return;
        }

        $alerts[] = $this->alert(
            'detraction_account_number',
            'info',
            'Cuenta de detracción sin número',
            'Registra el número de cuenta del Banco de la Nación para indicar a los clientes dónde depositar.',
            'treasury_detraction_index'
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function alert(
        string $code,
        string $severity,
        string $title,
        string $message,
        ?string $route = null,
        array $meta = [],
    ): array {
        return [
            'code' => $code,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'route' => $route,
            'meta' => $meta,
        ];
    }
}
