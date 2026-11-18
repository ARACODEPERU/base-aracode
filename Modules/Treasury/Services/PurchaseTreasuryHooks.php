<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Facades\Log;
use Modules\Purchases\Entities\PurcDocument;
use Modules\Treasury\Entities\TreasurySourceMovement;

/**
 * Puntos de enganche de Tesorería con Compras.
 *
 * Una compra pagada al proveedor es una salida real de dinero: cada medio de
 * pago del formulario se registra como egreso en la cuenta que le corresponde
 * (la misma resolución método de pago → cuenta que usa Ventas). El efectivo se
 * controla en Caja Chica, así que queda fuera del libro bancario.
 *
 * El registro es best-effort: si algo falla, la compra continúa y el fallo
 * queda en la tabla puente para su reintento administrativo.
 */
class PurchaseTreasuryHooks
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
     * Movimientos de egreso de una compra según sus pagos
     * ({type: payment_method_id, amount, reference}).
     *
     * @param  array<int, array<string, mixed>>  $payments
     * @return array<int, FinancialMovement>
     */
    public static function purchaseExpenseMovements(PurcDocument $purchase, array $payments): array
    {
        $payments = app(TreasuryLedgerService::class)->normalizeSalePayments($payments)->values();

        if ($payments->isEmpty()) {
            return [];
        }

        $currency = strtoupper((string) ($purchase->currency_type_id ?: 'PEN')) ?: 'PEN';
        $date = $purchase->date_of_issue ?: now();
        $provider = self::providerName($purchase);
        $number = self::documentNumber($purchase);

        $description = 'Compra'
            . ($number ? ' ' . $number : '')
            . ($provider ? ' · ' . $provider : '');

        $movements = [];

        foreach ($payments as $index => $payment) {
            $movements[] = new FinancialMovement(
                module: TreasurySourceMovement::MODULE_PURCHASES,
                sourceType: TreasurySourceMovement::SOURCE_PURCHASE_DOCUMENT,
                sourceId: (int) $purchase->id,
                operation: 'purchase_payment',
                type: TreasurySourceMovement::TYPE_EXPENSE,
                amount: (float) $payment['amount'],
                date: $date,
                sourceLine: (int) $index,
                paymentMethodId: $payment['payment_method_id'],
                currency: $currency,
                exchangeRate: null,
                reference: $payment['reference'],
                description: mb_substr($description, 0, 250),
                userId: $purchase->user_id ? (int) $purchase->user_id : auth()->id(),
                meta: [
                    'purchase_id' => $purchase->id,
                    'provider_id' => $purchase->provider_id,
                    'document_number' => $number,
                ],
            );
        }

        return $movements;
    }

    /**
     * Registra los egresos de una compra recién guardada.
     * Devuelve cuántos movimientos entraron al libro.
     *
     * @param  array<int, array<string, mixed>>  $payments
     */
    public static function recordPurchaseExpenses(PurcDocument $purchase, array $payments): int
    {
        if (! self::enabled()) {
            return 0;
        }

        $count = 0;

        foreach (self::purchaseExpenseMovements($purchase, $payments) as $movement) {
            try {
                $bridge = app(TreasuryIntegrationService::class)->register($movement);

                if ($bridge->status === TreasurySourceMovement::STATUS_PROCESSED) {
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::warning('[Treasury] No se registró el egreso de la compra', [
                    'purchase_id' => $purchase->id,
                    'operation' => $movement->operation,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Anula los egresos de tesorería de una compra anulada.
     */
    public static function voidPurchaseExpenses(PurcDocument $purchase, ?string $reason = null): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            app(TreasuryIntegrationService::class)->reverseOrigin(
                TreasurySourceMovement::MODULE_PURCHASES,
                TreasurySourceMovement::SOURCE_PURCHASE_DOCUMENT,
                (int) $purchase->id,
                $reason ?: 'Compra anulada',
                auth()->id()
            );
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se anularon los egresos de la compra', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Número visible del documento de compra (serie-correlativo). */
    private static function documentNumber(PurcDocument $purchase): ?string
    {
        $serie = trim((string) $purchase->serie);
        $number = trim((string) $purchase->number);

        return $serie !== '' && $number !== '' ? $serie . '-' . $number : null;
    }

    /** Razón social del proveedor de la compra. */
    private static function providerName(PurcDocument $purchase): ?string
    {
        $name = trim((string) $purchase->provider?->full_name);

        return $name !== '' ? $name : null;
    }
}
