<?php

namespace Modules\Treasury\Console;

use App\Models\Sale;
use Illuminate\Console\Command;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Sales\Entities\SalePaymentScheduleDestination;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Services\FinancialMovement;
use Modules\Treasury\Services\TreasuryHooks;
use Modules\Treasury\Services\TreasuryIntegrationService;

/**
 * Importación histórica de ingresos al libro de tesorería.
 *
 * Es manual y nunca automática: primero se ejecuta con --dry-run para ver el
 * informe de diferencias y recién después sin --dry-run. Todos los movimientos
 * pasan por la capa de integración, así que la clave única evita duplicar lo
 * que ya se registró en línea y no se recrean anulados.
 */
class BackfillTreasuryCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'treasury:backfill
                            {--since= : Fecha de corte inicial (Y-m-d). Por defecto usa config treasury.backfill_since}
                            {--dry-run : Informe de diferencias: muestra lo que se crearía sin escribir}';

    /**
     * The console command description.
     */
    protected $description = 'Importa al libro de tesorería los ingresos históricos de ventas, cuotas y cronogramas (idempotente)';

    public function handle(TreasuryIntegrationService $service): int
    {
        $since = $this->option('since')
            ?: config('treasury.backfill_since', '2020-01-01');

        $dryRun = (bool) $this->option('dry-run');

        $this->info("Backfill de tesorería desde {$since}" . ($dryRun ? ' (dry-run: informe de diferencias)' : ''));

        $sales = $this->backfillSales($service, $since, $dryRun);
        $quotas = $this->backfillQuotaPayments($service, $since, $dryRun);
        $destinations = $this->backfillScheduleDestinations($service, $since, $dryRun);

        $this->newLine();
        $this->table(
            ['Origen', 'Nuevos', 'Ya registrados', 'Ignorados', 'Fallidos'],
            [
                ['Ventas al contado', $sales['new'], $sales['existing'], $sales['ignored'], $sales['failed']],
                ['Abonos de cuota', $quotas['new'], $quotas['existing'], $quotas['ignored'], $quotas['failed']],
                ['Cronograma de pagos', $destinations['new'], $destinations['existing'], $destinations['ignored'], $destinations['failed']],
            ]
        );

        if ($dryRun) {
            $this->warn('Dry-run: no se escribió nada. Revisa el informe y vuelve a ejecutar sin --dry-run.');
        } else {
            $this->line('Recuerda fijar el saldo inicial (opening_balance) de cada cuenta para cuadrar con el saldo real del banco.');
        }

        return self::SUCCESS;
    }

    /**
     * Ingresos históricos desde el JSON sales.payments.
     *
     * @return array{new: int, existing: int, ignored: int, failed: int}
     */
    private function backfillSales(TreasuryIntegrationService $service, string $since, bool $dryRun): array
    {
        $totals = $this->emptyTotals();
        $skipped = 0;

        Sale::query()
            ->whereDate('sale_date', '>=', $since)
            ->orderBy('sale_date')
            ->chunkById(500, function ($sales) use ($service, $dryRun, &$totals, &$skipped) {
                foreach ($sales as $sale) {
                    // En ventas con cobranza diferida el JSON de pagos no es el
                    // dinero recibido: se importan sus cuotas o su cronograma,
                    // nunca el total como si se hubiera cobrado al contado.
                    if ($this->hasDeferredCollection($sale)) {
                        $skipped++;
                        continue;
                    }

                    $movements = TreasuryHooks::saleIncomeMovements($sale, TreasuryTransaction::SOURCE_BACKFILL);
                    $this->addTotals($totals, $this->registerMovements($service, $movements, $dryRun));
                }
            });

        if ($skipped > 0) {
            $this->line("  Ventas con cobranza diferida omitidas en el JSON de pagos: {$skipped}");
        }

        return $totals;
    }

    /**
     * Abonos históricos de cuotas (Cuentas por Cobrar), excluyendo los
     * marcados como "de relleno" (estado = false).
     *
     * @return array{new: int, existing: int, ignored: int, failed: int}
     */
    private function backfillQuotaPayments(TreasuryIntegrationService $service, string $since, bool $dryRun): array
    {
        $totals = $this->emptyTotals();

        SalePaymentQuota::query()
            ->with(['quota.saleDocument', 'quota'])
            ->whereDate('payment_date', '>=', $since)
            ->where('estado', true)
            ->orderBy('payment_date')
            ->chunkById(500, function ($payments) use ($service, $dryRun, &$totals) {
                foreach ($payments as $payment) {
                    $document = $payment->quota?->saleDocument;

                    if (! $document) {
                        continue;
                    }

                    $movements = TreasuryHooks::quotaPaymentMovements(
                        $payment,
                        $document,
                        TreasuryTransaction::SOURCE_BACKFILL
                    );

                    $this->addTotals($totals, $this->registerMovements($service, $movements, $dryRun));
                }
            });

        return $totals;
    }

    /**
     * Cobros históricos del cronograma de pagos.
     *
     * @return array{new: int, existing: int, ignored: int, failed: int}
     */
    private function backfillScheduleDestinations(TreasuryIntegrationService $service, string $since, bool $dryRun): array
    {
        $totals = $this->emptyTotals();

        SalePaymentScheduleDestination::query()
            ->where('status', 'paid')
            ->where(function ($query) use ($since) {
                $query->whereNull('date_payment')->orWhereDate('date_payment', '>=', $since);
            })
            ->orderBy('id')
            ->chunkById(500, function ($destinations) use ($service, $dryRun, &$totals) {
                foreach ($destinations as $destination) {
                    $movements = TreasuryHooks::scheduleDestinationMovements(
                        $destination,
                        TreasuryTransaction::SOURCE_BACKFILL
                    );

                    $this->addTotals($totals, $this->registerMovements($service, $movements, $dryRun));
                }
            });

        return $totals;
    }

    /**
     * @param  array<int, FinancialMovement>  $movements
     * @return array{new: int, existing: int, ignored: int, failed: int}
     */
    private function registerMovements(TreasuryIntegrationService $service, array $movements, bool $dryRun): array
    {
        $result = $this->emptyTotals();

        foreach ($movements as $movement) {
            $existing = $service->alreadyRegistered($movement);

            if ($existing) {
                $result['existing']++;
                $this->line(sprintf(
                    '  = %s %s#%d · %s %s (%s)',
                    $movement->operation,
                    $movement->sourceType,
                    $movement->sourceId,
                    number_format($movement->amount, 2),
                    $movement->currency,
                    $existing->status
                ));
                continue;
            }

            if ($dryRun) {
                $result['new']++;
                $this->line(sprintf(
                    '  + %s %s#%d · %s %s',
                    $movement->operation,
                    $movement->sourceType,
                    $movement->sourceId,
                    number_format($movement->amount, 2),
                    $movement->currency
                ));
                continue;
            }

            try {
                $bridge = $service->register($movement);

                match ($bridge->status) {
                    \Modules\Treasury\Entities\TreasurySourceMovement::STATUS_PROCESSED => $result['new']++,
                    \Modules\Treasury\Entities\TreasurySourceMovement::STATUS_IGNORED => $result['ignored']++,
                    default => $result['failed']++,
                };
            } catch (\Throwable $e) {
                $result['failed']++;
                $this->warn('  ! ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * ¿La venta cobra de forma diferida (crédito, cuotas o cronograma)?
     */
    private function hasDeferredCollection(Sale $sale): bool
    {
        $document = $sale->documents()->orderByDesc('id')->first();

        if ($document && in_array(mb_strtolower(trim((string) $document->forma_pago)), ['credito', 'crédito'], true)) {
            return true;
        }

        if ($sale->schedules()->exists()) {
            return true;
        }

        $documentIds = $sale->documents()->pluck('id');

        return $documentIds->isNotEmpty()
            && SaleDocumentQuota::query()->whereIn('sale_document_id', $documentIds)->exists();
    }

    /**
     * @return array{new: int, existing: int, ignored: int, failed: int}
     */
    private function emptyTotals(): array
    {
        return ['new' => 0, 'existing' => 0, 'ignored' => 0, 'failed' => 0];
    }

    /**
     * @param  array{new: int, existing: int, ignored: int, failed: int}  $totals
     * @param  array{new: int, existing: int, ignored: int, failed: int}  $partial
     */
    private function addTotals(array &$totals, array $partial): void
    {
        foreach ($partial as $key => $value) {
            $totals[$key] += $value;
        }
    }
}
