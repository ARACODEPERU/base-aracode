<?php

namespace Modules\Treasury\Services;

use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Treasury\Entities\TreasuryTransaction;

/**
 * Puntos de enganche de Tesorería con Ventas.
 *
 * Todos son best-effort: si el módulo está desactivado (TREASURY_AUTO_INCOME=false),
 * no existe o algo falla, el flujo de ventas continúa sin interrupciones.
 */
class TreasuryHooks
{
    /**
     * Indica si el enganche automático está activo.
     */
    public static function enabled(): bool
    {
        return (bool) config('treasury.auto_income', true)
            && class_exists(TreasuryLedgerService::class);
    }

    /**
     * Registra los ingresos de una venta según sus métodos de pago.
     */
    public static function recordSaleIncomes(Sale $sale): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            app(TreasuryLedgerService::class)->recordSaleIncomes($sale);
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se registraron los ingresos de la venta', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Anula los ingresos de tesorería de una venta anulada.
     */
    public static function voidSaleIncomes(Sale $sale): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            TreasuryTransaction::query()
                ->where('origin_type', 'sale')
                ->where('origin_id', $sale->id)
                ->whereNull('voided_at')
                ->update(['voided_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se anularon los ingresos de la venta', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Registra el ingreso de un abono de cuota (Cuentas por Cobrar).
     */
    public static function recordQuotaPayment(SalePaymentQuota $payment, SaleDocument $document): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            app(TreasuryLedgerService::class)->recordQuotaPaymentIncome($payment, $document);
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se registró el ingreso del abono de cuota', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
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
            TreasuryTransaction::query()
                ->where('origin_type', 'sale_payment_quota')
                ->where('origin_id', $payment->id)
                ->whereNull('voided_at')
                ->update(['voided_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se anuló el ingreso del abono de cuota', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
