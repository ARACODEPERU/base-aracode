<?php

namespace Modules\Treasury\Services;

use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Entities\SaleDocumentQuota;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Sales\Entities\SalePaymentSchedule;
use Modules\Sales\Entities\SalePaymentScheduleDestination;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;

/**
 * Puntos de enganche de Tesorería con Ventas.
 *
 * Traduce los hechos de Ventas a movimientos financieros y los entrega a la
 * capa de integración (TreasuryIntegrationService), que es la única que escribe
 * en el libro. Los constructores de movimientos (saleIncomeMovements, etc.) son
 * puros: los reutiliza también el backfill para que un histórico y un registro
 * en línea compartan exactamente la misma clave de idempotencia.
 *
 * Todos los métodos de registro son best-effort: si el módulo está desactivado
 * (TREASURY_AUTO_INCOME=false) o algo falla, la venta continúa y el fallo queda
 * registrado en la tabla puente para reintento administrativo.
 */
class TreasuryHooks
{
    /**
     * Indica si el enganche automático está activo.
     */
    public static function enabled(): bool
    {
        return (bool) config('treasury.auto_income', true)
            && class_exists(TreasuryIntegrationService::class);
    }

    /**
     * Movimientos de una venta según su JSON `payments`
     * ({type: payment_method_id, amount, reference}).
     *
     * @return array<int, FinancialMovement>
     */
    public static function saleIncomeMovements(Sale $sale, string $source = TreasuryTransaction::SOURCE_AUTO): array
    {
        $ledger = app(TreasuryLedgerService::class);
        $payments = $ledger->normalizeSalePayments($sale->payments)->values();

        if ($payments->isEmpty()) {
            return [];
        }

        $document = $sale->documents()->orderByDesc('id')->first();
        [$currency, $rate] = $ledger->resolveDocumentCurrency($document);

        $movements = [];

        foreach ($payments as $index => $payment) {
            $movements[] = new FinancialMovement(
                module: TreasurySourceMovement::MODULE_SALES,
                sourceType: TreasurySourceMovement::SOURCE_SALE,
                sourceId: (int) $sale->id,
                operation: 'sale_income',
                type: TreasurySourceMovement::TYPE_INCOME,
                amount: (float) $payment['amount'],
                date: $sale->sale_date ?? now(),
                sourceLine: (int) $index,
                paymentMethodId: $payment['payment_method_id'],
                currency: $currency,
                exchangeRate: $rate,
                reference: self::withExchangeRateNote($payment['reference'], $currency, $rate),
                description: 'Cobro de venta',
                userId: $sale->user_id ? (int) $sale->user_id : null,
                meta: [
                    'sale_id' => $sale->id,
                    'document_id' => $document?->id,
                ],
                source: $source,
            );
        }

        return $movements;
    }

    /**
     * Movimiento de un abono de cuota (Cuentas por Cobrar).
     *
     * @return array<int, FinancialMovement>
     */
    public static function quotaPaymentMovements(
        SalePaymentQuota $payment,
        SaleDocument $document,
        string $source = TreasuryTransaction::SOURCE_AUTO,
    ): array {
        [$currency, $rate] = app(TreasuryLedgerService::class)->resolveDocumentCurrency($document);

        // La cuota a la que se aplicó el abono da el "5 de 10" de la descripción.
        $quota = $payment->sale_document_quota_id
            ? SaleDocumentQuota::find($payment->sale_document_quota_id)
            : null;
        $totalQuotas = $quota
            ? SaleDocumentQuota::where('sale_document_id', $quota->sale_document_id)->count()
            : null;

        return [new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE_PAYMENT_QUOTA,
            sourceId: (int) $payment->id,
            operation: 'quota_payment',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: (float) $payment->amount_applied,
            date: $payment->payment_date ?? now(),
            paymentMethodId: $payment->payment_method_id ? (int) $payment->payment_method_id : null,
            currency: $currency,
            exchangeRate: $rate,
            reference: self::withExchangeRateNote($payment->reference, $currency, $rate),
            description: self::installmentDescription($document, $quota?->quota_number, $totalQuotas, 'Abono de cuota'),
            userId: auth()->id(),
            meta: [
                'sale_id' => $document->sale_id,
                'document_id' => $document->id,
                'quota_id' => $payment->sale_document_quota_id,
                'quota_number' => $quota?->quota_number,
                'total_quotas' => $totalQuotas,
                'due_date' => $quota?->due_date ? substr((string) $quota->due_date, 0, 10) : null,
                'document_number' => self::documentNumber($document),
            ],
            source: $source,
        )];
    }

    /**
     * Movimiento de un cobro registrado en el cronograma de pagos.
     *
     * @return array<int, FinancialMovement>
     */
    public static function scheduleDestinationMovements(
        SalePaymentScheduleDestination $destination,
        string $source = TreasuryTransaction::SOURCE_AUTO,
    ): array {
        $document = $destination->document_id ? SaleDocument::find($destination->document_id) : null;
        $sale = $destination->sale_id ? Sale::find($destination->sale_id) : null;

        if (! $document && $sale) {
            $document = $sale->documents()->orderByDesc('id')->first();
        }

        [$currency, $rate] = app(TreasuryLedgerService::class)->resolveDocumentCurrency($document);

        // Número de cuota del cronograma y total de cuotas de la venta.
        $schedule = $destination->schedule_id
            ? SalePaymentSchedule::find($destination->schedule_id)
            : null;
        $totalInstallments = $schedule
            ? SalePaymentSchedule::where('sale_id', $schedule->sale_id)->count()
            : null;

        return [new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE_SCHEDULE_DESTINATION,
            sourceId: (int) $destination->id,
            operation: 'installment_payment',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: (float) $destination->amount,
            date: $destination->date_payment ?? now(),
            paymentMethodId: $destination->method_id ? (int) $destination->method_id : null,
            currency: $currency,
            exchangeRate: $rate,
            reference: self::withExchangeRateNote($destination->reference, $currency, $rate),
            description: self::installmentDescription($document, $schedule?->installment_number, $totalInstallments, 'Cobro de cronograma'),
            userId: $destination->user_id ? (int) $destination->user_id : auth()->id(),
            meta: [
                'sale_id' => $destination->sale_id,
                'document_id' => $destination->document_id,
                'schedule_id' => $destination->schedule_id,
                'installment_number' => $schedule?->installment_number,
                'total_installments' => $totalInstallments,
                'due_date' => $schedule?->payment_date ? substr((string) $schedule->payment_date, 0, 10) : null,
                'document_number' => self::documentNumber($document),
            ],
            source: $source,
        )];
    }

    /**
     * Registra los ingresos de una venta según sus métodos de pago.
     */
    public static function recordSaleIncomes(Sale $sale): int
    {
        if (! self::enabled()) {
            return 0;
        }

        return self::dispatchAll(self::saleIncomeMovements($sale));
    }

    /**
     * Ingreso de una venta cobrada en una cuenta de tesorería concreta.
     *
     * Las negociaciones comerciales no eligen un método de pago de ventas: el
     * medio acordado (billetera con enlace, banco o caja) es el que dice dónde
     * entró el dinero, así que el movimiento se registra contra esa cuenta.
     */
    public static function recordSaleIncomeInAccount(
        Sale $sale,
        float $amount,
        TreasuryAccount $account,
        ?string $reference = null,
        ?int $documentId = null,
    ): ?TreasuryTransaction {
        if (! self::enabled() || $amount <= 0) {
            return null;
        }

        $ledger = app(TreasuryLedgerService::class);
        $document = $documentId
            ? SaleDocument::find($documentId)
            : $sale->documents()->orderByDesc('id')->first();

        [$currency, $rate] = $ledger->resolveDocumentCurrency($document);

        return self::dispatch(new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE,
            sourceId: (int) $sale->id,
            operation: 'sale_income',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: $amount,
            date: $sale->sale_date ?? now(),
            sourceLine: 0,
            paymentMethodId: null,
            currency: $currency,
            exchangeRate: $rate,
            reference: self::withExchangeRateNote($reference, $currency, $rate),
            description: 'Cobro de venta',
            treasuryAccountId: (int) $account->id,
            userId: $sale->user_id ? (int) $sale->user_id : null,
            meta: [
                'sale_id' => $sale->id,
                'document_id' => $document?->id,
            ],
            source: TreasuryTransaction::SOURCE_AUTO,
        ));
    }

    /**
     * Registra el ingreso de un abono de cuota (Cuentas por Cobrar).
     */
    public static function recordQuotaPayment(SalePaymentQuota $payment, SaleDocument $document): ?TreasuryTransaction
    {
        if (! self::enabled()) {
            return null;
        }

        return self::dispatch(self::quotaPaymentMovements($payment, $document)[0]);
    }

    /**
     * Registra el cobro de una cuota del cronograma.
     */
    public static function recordScheduleDestination(SalePaymentScheduleDestination $destination): ?TreasuryTransaction
    {
        if (! self::enabled()) {
            return null;
        }

        return self::dispatch(self::scheduleDestinationMovements($destination)[0]);
    }

    /**
     * Registra la cuota inicial realmente recibida en una venta al crédito.
     */
    public static function recordDownPayment(SaleDocument $document, array $payment, int $line = 0): ?TreasuryTransaction
    {
        if (! self::enabled()) {
            return null;
        }

        [$currency, $rate] = app(TreasuryLedgerService::class)->resolveDocumentCurrency($document);

        return self::dispatch(new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE_DOCUMENT,
            sourceId: (int) $document->id,
            operation: 'down_payment',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: (float) ($payment['amount'] ?? 0),
            date: $document->invoice_broadcast_date ?? now(),
            sourceLine: $line,
            paymentMethodId: isset($payment['type']) ? (int) $payment['type'] : null,
            currency: $currency,
            exchangeRate: $rate,
            reference: self::withExchangeRateNote($payment['reference'] ?? null, $currency, $rate),
            description: 'Cuota inicial' . ($document->sale_id ? ' - Venta #' . $document->sale_id : ''),
            userId: auth()->id(),
            meta: ['sale_id' => $document->sale_id, 'document_id' => $document->id],
        ));
    }

    /**
     * Registra el dinero recibido al saldar un documento (pago total).
     */
    public static function recordFullPayment(
        SaleDocument $document,
        float $amount,
        ?int $paymentMethodId,
        ?string $reference = null,
        ?string $date = null,
    ): ?TreasuryTransaction {
        if (! self::enabled() || $amount <= 0) {
            return null;
        }

        [$currency, $rate] = app(TreasuryLedgerService::class)->resolveDocumentCurrency($document);

        return self::dispatch(new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE_DOCUMENT,
            sourceId: (int) $document->id,
            operation: 'full_payment',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: $amount,
            date: $date ?? now(),
            paymentMethodId: $paymentMethodId,
            currency: $currency,
            exchangeRate: $rate,
            reference: self::withExchangeRateNote($reference, $currency, $rate),
            description: 'Pago total de deuda' . ($document->sale_id ? ' - Venta #' . $document->sale_id : ''),
            userId: auth()->id(),
            meta: ['sale_id' => $document->sale_id, 'document_id' => $document->id],
        ));
    }

    /**
     * Registra manualmente el cobro de un comprobante que no generó ninguna
     * operación (venta creada sin medio de pago) contra una cuenta explícita.
     *
     * La cuenta se fija en el movimiento, así que no depende del mapeo del
     * método de pago. Queda con source "manual" para distinguirlo del
     * automático y se revierte igual al anular el comprobante.
     */
    public static function recordManualDocumentIncome(
        SaleDocument $document,
        TreasuryAccount $account,
        ?int $paymentMethodId = null,
        ?float $amount = null,
        ?string $date = null,
        ?string $reference = null,
    ): ?TreasurySourceMovement {
        if (! self::enabled()) {
            return null;
        }

        $amount = $amount ?? (float) ($document->overall_total ?: $document->invoice_mto_imp_sale ?: 0);

        if ($amount <= 0) {
            return null;
        }

        [$currency, $rate] = app(TreasuryLedgerService::class)->resolveDocumentCurrency($document);

        return self::dispatch(new FinancialMovement(
            module: TreasurySourceMovement::MODULE_SALES,
            sourceType: TreasurySourceMovement::SOURCE_SALE_DOCUMENT,
            sourceId: (int) $document->id,
            operation: 'manual_income',
            type: TreasurySourceMovement::TYPE_INCOME,
            amount: $amount,
            date: $date ?: ($document->invoice_broadcast_date ?? now()),
            sourceLine: 0,
            paymentMethodId: $paymentMethodId,
            currency: $currency,
            exchangeRate: $rate,
            reference: self::withExchangeRateNote($reference, $currency, $rate),
            description: 'Cobro vinculado manualmente ' . trim((string) $document->invoice_serie . '-' . (string) $document->invoice_correlative),
            treasuryAccountId: $account->id,
            userId: auth()->id(),
            meta: [
                'sale_id' => $document->sale_id,
                'document_id' => $document->id,
                'manual_link' => true,
            ],
            source: TreasuryTransaction::SOURCE_MANUAL,
        ));
    }

    /**
     * Anula los ingresos de tesorería de una venta anulada y de todo lo
     * cobrado a su nombre (cuotas, cronograma, cuota inicial, pago total).
     */
    public static function voidSaleIncomes(Sale $sale): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            $service = app(TreasuryIntegrationService::class);
            $userId = auth()->id();

            $service->reverseOrigin(
                TreasurySourceMovement::MODULE_SALES,
                TreasurySourceMovement::SOURCE_SALE,
                (int) $sale->id,
                'Venta anulada',
                $userId
            );

            foreach ($sale->documents()->pluck('id') as $documentId) {
                $service->reverseOrigin(
                    TreasurySourceMovement::MODULE_SALES,
                    TreasurySourceMovement::SOURCE_SALE_DOCUMENT,
                    (int) $documentId,
                    'Venta anulada',
                    $userId
                );

                $quotaIds = SaleDocumentQuota::query()
                    ->where('sale_document_id', $documentId)
                    ->pluck('id');

                foreach ($quotaIds as $quotaId) {
                    $paymentIds = SalePaymentQuota::query()
                        ->where('sale_document_quota_id', $quotaId)
                        ->pluck('id');

                    foreach ($paymentIds as $paymentId) {
                        $service->reverseOrigin(
                            TreasurySourceMovement::MODULE_SALES,
                            TreasurySourceMovement::SOURCE_SALE_PAYMENT_QUOTA,
                            (int) $paymentId,
                            'Venta anulada',
                            $userId
                        );
                    }
                }
            }

            $destinationIds = SalePaymentScheduleDestination::query()
                ->where('sale_id', $sale->id)
                ->pluck('id');

            foreach ($destinationIds as $destinationId) {
                $service->reverseOrigin(
                    TreasurySourceMovement::MODULE_SALES,
                    TreasurySourceMovement::SOURCE_SALE_SCHEDULE_DESTINATION,
                    (int) $destinationId,
                    'Venta anulada',
                    $userId
                );
            }
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se anularon los ingresos de la venta', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Anula los ingresos de tesorería de un comprobante anulado (boleta o
     * factura) y de lo cobrado a su nombre: cuota inicial, pago total y abonos
     * de cuota. El mecanismo y el motivo quedan en la reversión para auditoría.
     *
     * Los cobros del cronograma no se tocan aquí: pertenecen a la venta y no al
     * comprobante.
     */
    public static function voidDocumentIncomes(
        SaleDocument $document,
        ?string $reason,
        string $mechanism = 'Anulación del comprobante',
    ): void {
        if (! self::enabled()) {
            return;
        }

        try {
            $service = app(TreasuryIntegrationService::class);
            $userId = auth()->id();
            $note = self::reversalReason($mechanism, $reason);

            $service->reverseOrigin(
                TreasurySourceMovement::MODULE_SALES,
                TreasurySourceMovement::SOURCE_SALE_DOCUMENT,
                (int) $document->id,
                $note,
                $userId
            );

            // El cobro al contado se registra contra la venta, no contra el
            // comprobante: se revierte solo cuando este es el último
            // comprobante de venta vigente de esa venta.
            if ($document->sale_id && in_array((string) $document->invoice_type_doc, ['01', '03'], true)) {
                $otroVigente = SaleDocument::query()
                    ->where('sale_id', $document->sale_id)
                    ->whereKeyNot($document->id)
                    ->whereIn('invoice_type_doc', ['01', '03'])
                    ->where('status', '!=', 3)
                    ->exists();

                if (! $otroVigente) {
                    $service->reverseOrigin(
                        TreasurySourceMovement::MODULE_SALES,
                        TreasurySourceMovement::SOURCE_SALE,
                        (int) $document->sale_id,
                        $note,
                        $userId
                    );
                }
            }

            $quotaIds = SaleDocumentQuota::query()
                ->where('sale_document_id', $document->id)
                ->pluck('id');

            foreach ($quotaIds as $quotaId) {
                $paymentIds = SalePaymentQuota::query()
                    ->where('sale_document_quota_id', $quotaId)
                    ->pluck('id');

                foreach ($paymentIds as $paymentId) {
                    $service->reverseOrigin(
                        TreasurySourceMovement::MODULE_SALES,
                        TreasurySourceMovement::SOURCE_SALE_PAYMENT_QUOTA,
                        (int) $paymentId,
                        $note,
                        $userId
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se anularon los ingresos del comprobante', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Motivo legible de la reversión: mecanismo + motivo del usuario.
     */
    private static function reversalReason(string $mechanism, ?string $reason): string
    {
        $detail = trim((string) $reason);

        return mb_substr(trim($mechanism . ($detail !== '' ? ': ' . $detail : '')), 0, 200);
    }

    /**
     * Anula el ingreso de tesorería de un abono de cuota eliminado.
     */
    public static function voidQuotaPayment(SalePaymentQuota $payment): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            app(TreasuryIntegrationService::class)->reverseOrigin(
                TreasurySourceMovement::MODULE_SALES,
                TreasurySourceMovement::SOURCE_SALE_PAYMENT_QUOTA,
                (int) $payment->id,
                'Abono de cuota eliminado',
                auth()->id()
            );
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se anuló el ingreso del abono de cuota', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Anula el ingreso de tesorería de un cobro del cronograma eliminado.
     */
    public static function voidScheduleDestination(SalePaymentScheduleDestination $destination): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            app(TreasuryIntegrationService::class)->reverseOrigin(
                TreasurySourceMovement::MODULE_SALES,
                TreasurySourceMovement::SOURCE_SALE_SCHEDULE_DESTINATION,
                (int) $destination->id,
                'Cobro de cronograma eliminado',
                auth()->id()
            );
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se anuló el ingreso del cronograma', [
                'destination_id' => $destination->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<int, FinancialMovement>  $movements
     */
    private static function dispatchAll(array $movements): int
    {
        $count = 0;

        foreach ($movements as $movement) {
            if (self::dispatch($movement)) {
                $count++;
            }
        }

        return $count;
    }

    private static function dispatch(FinancialMovement $movement): ?TreasuryTransaction
    {
        try {
            return app(TreasuryIntegrationService::class)->register($movement)->treasuryTransaction;
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se registró el movimiento financiero', [
                'origin' => $movement->sourceType . '#' . $movement->sourceId,
                'operation' => $movement->operation,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Anota el tipo de cambio usado en la referencia, para poder auditar la
     * conversión de un comprobante en moneda extranjera.
     */
    private static function withExchangeRateNote(?string $reference, string $currency, ?float $rate): ?string
    {
        if (strtoupper($currency) === 'PEN' || $rate === null || $rate <= 0) {
            return $reference;
        }

        $clean = trim((string) $reference);
        $formatted = rtrim(rtrim(number_format($rate, 4, '.', ''), '0'), '.');

        return ($clean !== '' ? $clean . ' · ' : '') . sprintf('TC %s %s', strtoupper($currency), $formatted);
    }

    /**
     * Descripción visible de un cobro de cuota: "Cuota 5 de 10 · B001-123 · Curso".
     * Sin número de cuota (pago inicial) se usa el texto de respaldo.
     */
    private static function installmentDescription(
        ?SaleDocument $document,
        ?int $number,
        ?int $total,
        string $fallback,
    ): string {
        $parts = [];

        if ($number !== null && $number > 0) {
            $parts[] = 'Cuota ' . $number . ($total ? ' de ' . $total : '');
        } else {
            $parts[] = $fallback;
        }

        if ($documentNumber = self::documentNumber($document)) {
            $parts[] = $documentNumber;
        }

        if ($concept = self::documentConcept($document)) {
            $parts[] = $concept;
        }

        // La columna description del libro es de 255 caracteres.
        return mb_substr(implode(' · ', $parts), 0, 250);
    }

    /** Número visible del comprobante (serie-correlativo). */
    private static function documentNumber(?SaleDocument $document): ?string
    {
        if (! $document) {
            return null;
        }

        $serie = trim((string) $document->invoice_serie);
        $correlativo = trim((string) $document->invoice_correlative);

        return $serie !== '' && $correlativo !== '' ? $serie . '-' . $correlativo : null;
    }

    /** Curso o suscripción que se paga, según los ítems del comprobante. */
    private static function documentConcept(?SaleDocument $document): ?string
    {
        if (! $document) {
            return null;
        }

        $names = $document->items
            ->pluck('decription_product')
            ->filter()
            // Las descripciones de los ítems traen saltos de línea.
            ->map(fn ($name) => trim(preg_replace('/\s+/u', ' ', (string) $name)))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return null;
        }

        return $names->count() > 2
            ? $names->count() . ' items'
            : mb_substr($names->implode(', '), 0, 120);
    }
}
