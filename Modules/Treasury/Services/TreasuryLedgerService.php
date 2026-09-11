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
            $userId
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
            $userId
        );
    }

    /**
     * Registro genérico con guardas: cuenta activa, monto > 0, idempotencia
     * por firma cuando hay origen. Nunca lanza: falla en silencio con log
     * para no romper el flujo de ventas que lo invoca.
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
            if ($originType && $originId) {
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
     * Registra los ingresos de una venta leyendo el JSON `sales.payments`
     * ({type: payment_method_id, amount, reference}).
     * Crea un ingreso por cada pago mapeable a una cuenta de tesorería.
     */
    public function recordSaleIncomes(Sale $sale): int
    {
        $payments = $this->normalizeSalePayments($sale->payments);

        if ($payments->isEmpty()) {
            return 0;
        }

        $categoryId = $this->systemCategoryId('Cobro de venta');
        $count = 0;

        foreach ($payments as $payment) {
            $account = $this->resolver->resolveForMethod($payment['payment_method_id']);

            if (! $account) {
                continue;
            }

            $created = $this->registerIncome(
                $account,
                $sale->sale_date ?? now(),
                (float) $payment['amount'],
                $categoryId,
                'sale',
                $sale->id,
                $payment['reference'],
                'Cobro de venta',
                $payment['payment_method_id'],
                TreasuryTransaction::SOURCE_AUTO,
                $sale->user_id,
            );

            if ($created) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Registra el ingreso de un abono de cuota (Cuentas por Cobrar).
     */
    public function recordQuotaPaymentIncome(SalePaymentQuota $payment, SaleDocument $document): ?TreasuryTransaction
    {
        $account = $this->resolver->resolveForMethod($payment->payment_method_id);

        if (! $account) {
            return null;
        }

        $sale = $document->sale;

        return $this->registerIncome(
            $account,
            $payment->payment_date ?? now(),
            (float) $payment->amount_applied,
            $this->systemCategoryId('Abono de cuota'),
            'sale_payment_quota',
            $payment->id,
            $payment->reference,
            'Abono de cuota' . ($sale ? ' - Venta #' . $sale->id : ''),
            $payment->payment_method_id,
            TreasuryTransaction::SOURCE_AUTO,
            auth()->id(),
        );
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
            ->filter(fn ($payment) => $payment['amount'] > 0);
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
