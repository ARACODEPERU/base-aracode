<?php

namespace Modules\Treasury\Services;

use App\Models\Sale;
use App\Models\SaleDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Entities\SalePaymentQuota;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryCategory;
use Modules\Treasury\Entities\TreasuryTransaction;

/**
 * Servicio central del libro de bancos.
 *
 * El saldo NUNCA se guarda: se calcula como
 * opening_balance + SUM(ingresos activos) - SUM(egresos activos).
 *
 * Los movimientos originados por otros módulos entran por
 * TreasuryIntegrationService; aquí solo queda el registro de bajo nivel.
 */
class TreasuryLedgerService
{
    public function __construct(private PaymentAccountResolver $resolver)
    {
    }

    /**
     * Registra un ingreso. Devuelve la transacción creada o null si la
     * cuenta no aplica / el movimiento ya existe (idempotencia).
     */
    public function registerIncome(
        ?TreasuryAccount $account,
        string|Carbon $date,
        float $amount,
        ?int $categoryId = null,
        ?string $originType = null,
        ?int $originId = null,
        ?string $reference = null,
        ?string $description = null,
        ?int $paymentMethodId = null,
        string $source = TreasuryTransaction::SOURCE_MANUAL,
        ?int $userId = null,
        bool $checkSignature = true,
    ): ?TreasuryTransaction {
        return $this->register(
            $account,
            TreasuryTransaction::TYPE_INCOME,
            $date,
            $amount,
            $categoryId,
            $originType,
            $originId,
            $reference,
            $description,
            $paymentMethodId,
            $source,
            $userId,
            $checkSignature
        );
    }

    /**
     * Registra un egreso (contratos, honorarios, planillas, comisiones bancarias, otros).
     */
    public function registerExpense(
        ?TreasuryAccount $account,
        string|Carbon $date,
        float $amount,
        ?int $categoryId = null,
        ?string $originType = null,
        ?int $originId = null,
        ?string $reference = null,
        ?string $description = null,
        ?int $paymentMethodId = null,
        string $source = TreasuryTransaction::SOURCE_MANUAL,
        ?int $userId = null,
        bool $checkSignature = true,
    ): ?TreasuryTransaction {
        return $this->register(
            $account,
            TreasuryTransaction::TYPE_EXPENSE,
            $date,
            $amount,
            $categoryId,
            $originType,
            $originId,
            $reference,
            $description,
            $paymentMethodId,
            $source,
            $userId,
            $checkSignature
        );
    }

    /**
     * Registro genérico con guardas: cuenta activa, monto > 0, idempotencia
     * por firma cuando hay origen. Nunca lanza: falla en silencio con log
     * para no romper el flujo de ventas que lo invoca.
     *
     * $checkSignature = false lo usa la capa de integración, que garantiza la
     * idempotencia con su propia clave única (la firma histórica bloqueaba dos
     * pagos del mismo importe en la misma fecha).
     */
    public function register(
        ?TreasuryAccount $account,
        string $type,
        string|Carbon $date,
        float $amount,
        ?int $categoryId = null,
        ?string $originType = null,
        ?int $originId = null,
        ?string $reference = null,
        ?string $description = null,
        ?int $paymentMethodId = null,
        string $source = TreasuryTransaction::SOURCE_MANUAL,
        ?int $userId = null,
        bool $checkSignature = true,
    ): ?TreasuryTransaction {
        try {
            if (! $account || ! $account->status) {
                return null;
            }

            $amount = round((float) $amount, 2);

            if ($amount <= 0) {
                return null;
            }

            $dateValue = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

            // Idempotencia: si ya existe el mismo movimiento del mismo origen, no duplicar
            if ($checkSignature && $originType && $originId) {
                if (TreasuryTransaction::signatureExists(
                    $originType,
                    $originId,
                    $type,
                    $dateValue->toDateString(),
                    $amount
                )) {
                    return null;
                }
            }

            return TreasuryTransaction::create([
                'treasury_account_id' => $account->id,
                'transaction_date' => $dateValue->toDateString(),
                'type' => $type,
                'treasury_category_id' => $categoryId,
                'origin_type' => $originType,
                'origin_id' => $originId,
                'amount' => $amount,
                'reference' => $reference,
                'description' => $description,
                'payment_method_id' => $paymentMethodId,
                'source' => $source,
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se pudo registrar el movimiento', [
                'account_id' => $account?->id,
                'type' => $type,
                'amount' => $amount,
                'origin' => $originType ? $originType . '#' . $originId : null,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Anula un movimiento (no se borra: trazabilidad).
     */
    public function voidTransaction(TreasuryTransaction $transaction, ?int $userId = null): bool
    {
        return $transaction->void($userId);
    }

    /**
     * @deprecated La entrada es TreasuryHooks / TreasuryIntegrationService.
     * Se conserva por compatibilidad y delega en la capa de integración.
     */
    public function recordSaleIncomes(Sale $sale): int
    {
        return TreasuryHooks::recordSaleIncomes($sale);
    }

    /**
     * Moneda y tipo de cambio del documento de venta. Devuelve ['PEN', null]
     * cuando no hay documento o el comprobante está en soles.
     *
     * @return array{0: string, 1: ?float}
     */
    public function resolveDocumentCurrency(?SaleDocument $document): array
    {
        if (! $document) {
            return ['PEN', null];
        }

        $currency = strtoupper((string) $document->invoice_type_currency);

        if ($currency === '' || $currency === 'PEN') {
            return ['PEN', null];
        }

        $exchangeRate = $document->exchange_rate !== null ? (float) $document->exchange_rate : null;

        if ($exchangeRate === null || $exchangeRate <= 0) {
            // Respaldo: TC vigente del servicio (no debe bloquear el registro).
            try {
                $current = app(\Modules\Sales\Services\ExchangeRateService::class)->getCurrentRate($currency);
                $exchangeRate = $current ? (float) $current['rate'] : null;
            } catch (\Throwable $e) {
                $exchangeRate = null;
            }
        }

        return [$currency, $exchangeRate];
    }

    /**
     * @deprecated La entrada es TreasuryHooks / TreasuryIntegrationService.
     * Se conserva por compatibilidad y delega en la capa de integración.
     */
    public function recordQuotaPaymentIncome(SalePaymentQuota $payment, SaleDocument $document): ?TreasuryTransaction
    {
        return TreasuryHooks::recordQuotaPayment($payment, $document);
    }

    /**
     * Saldo calculado de una cuenta (opening + ingresos - egresos, sin anulados).
     */
    public function accountBalance(TreasuryAccount $account, ?string $untilDate = null): float
    {
        $query = $account->transactions()->active();

        if ($untilDate) {
            $query->whereDate('transaction_date', '<=', $untilDate);
        }

        $totals = (clone $query)->selectRaw("
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS incomes,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expenses
        ")->first();

        return round((float) $account->opening_balance + (float) $totals->incomes - (float) $totals->expenses, 2);
    }

    /**
     * Saldos de todas las cuentas activas en una sola consulta.
     *
     * @return array<int, array{account: TreasuryAccount, balance: float, incomes: float, expenses: float}>
     */
    public function balancesByAccount(?string $untilDate = null): array
    {
        $accounts = TreasuryAccount::query()->active()->with(['bankAccount.bank', 'companyBilletera.billetera'])->get();

        if ($accounts->isEmpty()) {
            return [];
        }

        $totals = $accounts->map(fn (TreasuryAccount $account) => [
            'account' => $account,
            'balance' => $this->accountBalance($account, $untilDate),
        ])->all();

        return $totals;
    }

    /**
     * Estado de cuenta con saldo corrido por fila.
     *
     * @return array<int, array<string, mixed>>
     */
    public function statement(TreasuryAccount $account, ?string $fromDate = null, ?string $untilDate = null, int $limit = 500): array
    {
        $openingUntilPrevious = $fromDate
            ? $this->accountBalance($account, Carbon::parse($fromDate)->subDay()->toDateString())
            : 0.0;

        $query = $account->transactions()
            ->active()
            ->with(['category', 'paymentMethod'])
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($fromDate) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }
        if ($untilDate) {
            $query->whereDate('transaction_date', '<=', $untilDate);
        }

        $balance = $openingUntilPrevious;

        return $query->limit($limit)->get()->map(function (TreasuryTransaction $tx) use (&$balance) {
            $balance += $tx->type === TreasuryTransaction::TYPE_INCOME
                ? (float) $tx->amount
                : -1 * (float) $tx->amount;

            return [
                'id' => $tx->id,
                'date' => $tx->transaction_date->format('Y-m-d'),
                'type' => $tx->type,
                'category' => $tx->category?->name,
                'category_color' => $tx->category?->color,
                'amount' => (float) $tx->amount,
                'running_balance' => round($balance, 2),
                'reference' => $tx->reference,
                'description' => $tx->description,
                'origin_type' => $tx->origin_type,
                'origin_id' => $tx->origin_id,
                'source' => $tx->source,
                'reconciled' => $tx->reconciled_at !== null,
                'voided' => $tx->voided_at !== null,
            ];
        })->all();
    }

    /**
     * Normaliza el JSON `sales.payments` a [{payment_method_id, amount, reference}].
     * Tolerante a las variantes históricas (type como id numérico).
     *
     * @return \Illuminate\Support\Collection<int, array{payment_method_id: ?int, amount: float, reference: ?string}>
     */
    public function normalizeSalePayments(mixed $payments): \Illuminate\Support\Collection
    {
        if (is_string($payments)) {
            $payments = json_decode($payments, true);
        }

        if (! is_array($payments)) {
            return collect();
        }

        return collect($payments)
            ->filter(fn ($payment) => is_array($payment) && isset($payment['amount']))
            ->map(fn ($payment) => [
                'payment_method_id' => isset($payment['type']) && is_numeric($payment['type'])
                    ? (int) $payment['type']
                    : (isset($payment['payment_method_id']) && is_numeric($payment['payment_method_id'])
                        ? (int) $payment['payment_method_id']
                        : null),
                'amount' => (float) $payment['amount'],
                'reference' => $payment['reference'] ?? null,
            ])
            ->filter(fn ($payment) => $payment['amount'] > 0)
            ->values();
    }

    /**
     * Id de una categoría del sistema por nombre (crea los ids tras el seeder).
     */
    public function systemCategoryId(string $name): ?int
    {
        $category = TreasuryCategory::query()
            ->where('name', $name)
            ->first();

        return $category?->id;
    }
}
