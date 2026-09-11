<?php

namespace Modules\Treasury\Console;

use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Treasury\Services\TreasuryLedgerService;

class BackfillTreasuryCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'treasury:backfill
                            {--since= : Fecha de corte inicial (Y-m-d). Por defecto usa config treasury.backfill_since}
                            {--dry-run : Muestra lo que se crearía sin escribir}';

    /**
     * The console command description.
     */
    protected $description = 'Importa al libro de tesorería los ingresos históricos de ventas y abonos de cuotas (idempotente)';

    public function handle(TreasuryLedgerService $ledger): int
    {
        $since = $this->option('since')
            ?: config('treasury.backfill_since', '2020-01-01');

        $dryRun = (bool) $this->option('dry-run');

        $this->info("Backfill de tesorería desde {$since}" . ($dryRun ? ' (dry-run)' : ''));

        $createdSales = $this->backfillSales($ledger, $since, $dryRun);
        $createdQuotas = $this->backfillQuotaPayments($ledger, $since, $dryRun);

        $this->newLine();
        $this->info("Listo. Ingresos por ventas: {$createdSales} · Abonos de cuotas: {$createdQuotas}");
        $this->line('Recuerda fijar el saldo inicial (opening_balance) de cada cuenta para cuadrar con el saldo real del banco.');

        return self::SUCCESS;
    }

    /**
     * Ingresos históricos desde el JSON sales.payments.
     */
    private function backfillSales(TreasuryLedgerService $ledger, string $since, bool $dryRun): int
    {
        $categoryId = $ledger->systemCategoryId('Cobro de venta');
        $created = 0;
        $skippedNoAccount = 0;

        Sale::query()
            ->whereDate('sale_date', '>=', $since)
            ->orderBy('sale_date')
            ->chunkById(500, function ($sales) use ($ledger, $categoryId, $dryRun, &$created, &$skippedNoAccount) {
                foreach ($sales as $sale) {
                    $payments = $ledger->normalizeSalePayments($sale->payments);

                    foreach ($payments as $payment) {
                        $account = app(\Modules\Treasury\Services\PaymentAccountResolver::class)
                            ->resolveForMethod($payment['payment_method_id']);

                        if (! $account) {
                            $skippedNoAccount++;
                            continue;
                        }

                        if ($dryRun) {
                            $this->line("  [venta #{$sale->id}] {$account->displayName()}: +{$payment['amount']} ({$sale->sale_date})");

                            $created++;
                            continue;
                        }

                        $tx = $ledger->registerIncome(
                            $account,
                            $sale->sale_date,
                            $payment['amount'],
                            $categoryId,
                            'sale',
                            $sale->id,
                            $payment['reference'],
                            'Cobro de venta (backfill)',
                            $payment['payment_method_id'],
                            \Modules\Treasury\Entities\TreasuryTransaction::SOURCE_BACKFILL,
                            $sale->user_id,
                        );

                        if ($tx) {
                            $created++;
                        }
                    }
                }
            });

        if ($skippedNoAccount > 0) {
            $this->warn("Pagos sin cuenta de tesorería mapeable omitidos: {$skippedNoAccount}");
            $this->line('Asigna los métodos de pago a cuentas en Tesorería > Cuentas y vuelve a ejecutar el comando.');
        }

        return $created;
    }

    /**
     * Abonos históricos de cuotas (Cuentas por Cobrar), excluyendo los
     * marcados como "de relleno" (estado = false).
     */
    private function backfillQuotaPayments(TreasuryLedgerService $ledger, string $since, bool $dryRun): int
    {
        $categoryId = $ledger->systemCategoryId('Abono de cuota');
        $created = 0;
        $skippedNoAccount = 0;

        SalePaymentQuota::query()
            ->with(['quota.saleDocument.sale', 'quota'])
            ->whereDate('payment_date', '>=', $since)
            ->where('estado', true)
            ->orderBy('payment_date')
            ->chunkById(500, function ($payments) use ($ledger, $categoryId, $dryRun, &$created, &$skippedNoAccount) {
                foreach ($payments as $payment) {
                    $document = $payment->quota?->saleDocument;

                    if (! $document) {
                        continue;
                    }

                    $resolver = app(\Modules\Treasury\Services\PaymentAccountResolver::class);
                    $account = $resolver->resolveForMethod($payment->payment_method_id);

                    if (! $account) {
                        $skippedNoAccount++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("  [abono #{$payment->id}] {$account->displayName()}: +{$payment->amount_applied}");

                        $created++;
                        continue;
                    }

                    $tx = $ledger->registerIncome(
                        $account,
                        $payment->payment_date,
                        (float) $payment->amount_applied,
                        $categoryId,
                        'sale_payment_quota',
                        $payment->id,
                        $payment->reference,
                        'Abono de cuota (backfill)',
                        $payment->payment_method_id,
                        \Modules\Treasury\Entities\TreasuryTransaction::SOURCE_BACKFILL,
                        null,
                    );

                    if ($tx) {
                        $created++;
                    }
                }
            });

        if ($skippedNoAccount > 0) {
            $this->warn("Abonos sin cuenta de tesorería mapeable omitidos: {$skippedNoAccount}");
        }

        return $created;
    }
}
