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
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Services\FeeCalculator;

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
    public function __construct(
        private PaymentAccountResolver $resolver,
        private TreasurySettingsService $settings,
    ) {
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
        ?int $sourceWalletAccountId = null,
        ?string $currency = null,
        ?float $exchangeRate = null,
        ?int $costCenterId = null,
    ): ?TreasuryTransaction {
        $income = $this->register(
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
            $checkSignature,
            $sourceWalletAccountId,
            $currency,
            $exchangeRate,
            $costCenterId,
        );

        // Si la cuenta cobra comisiones, se registra su egreso (comisión + IGV) en la misma cuenta.
        // El ingreso queda por el total de la compra.
        if ($income) {
            $this->registerCommissionForIncome($account, $income, $date, $userId, $sourceWalletAccountId);
        }

        return $income;
    }

    /**
     * Registra el egreso de comisión (comisión + IGV) de un ingreso ya creado.
     * Nunca lanza: si falla, el ingreso ya registrado se mantiene.
     */
    private function registerCommissionForIncome(
        ?TreasuryAccount $account,
        TreasuryTransaction $income,
        string|Carbon $date,
        ?int $userId,
        ?int $sourceWalletAccountId = null,
    ): void {
        try {
            // La comisión la cobra el medio que recibió el dinero: si entró por una
            // billetera vinculada y cayó en el banco, se usan las tarifas de la billetera.
            $feeAccount = $sourceWalletAccountId
                ? (TreasuryAccount::find($sourceWalletAccountId) ?? $account)
                : $account;

            $sinComision = (float) $feeAccount?->fixed_fee + (float) $feeAccount?->percent_fee <= 0
                && blank($feeAccount?->commission_formula);

            if (! $account || ! $feeAccount || $sinComision) {
                return;
            }

            $calc = app(FeeCalculator::class)->commission(
                (float) $income->amount,
                (float) $feeAccount->fixed_fee,
                (float) $feeAccount->percent_fee,
                (bool) $feeAccount->igv_applies,
                $feeAccount->commission_formula,
            );

            if ($calc['total'] <= 0) {
                return;
            }

            TreasuryTransaction::create([
                'treasury_account_id' => $account->id,
                'transaction_date' => $income->transaction_date,
                'type' => TreasuryTransaction::TYPE_EXPENSE,
                'origin_type' => 'treasury_commission',
                'origin_id' => $income->id,
                'amount' => $calc['total'],
                'fee_amount' => $calc['comision'],
                'igv_amount' => $calc['igv'],
                'description' => 'Comisión por ingreso #' . $income->id,
                'source' => TreasuryTransaction::SOURCE_AUTO,
                'user_id' => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Treasury] No se pudo registrar la comisión del ingreso', [
                'income_id' => $income->id,
                'error' => $e->getMessage(),
            ]);
        }
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
        ?int $sourceWalletAccountId = null,
        ?string $currency = null,
        ?float $exchangeRate = null,
        ?int $costCenterId = null,
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
            $checkSignature,
            $sourceWalletAccountId,
            $currency,
            $exchangeRate,
            $costCenterId,
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
        ?int $sourceWalletAccountId = null,
        ?string $currency = null,
        ?float $exchangeRate = null,
        ?int $costCenterId = null,
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

            if (! config('treasury.allow_future_dates', false) && $dateValue->isAfter(now()->startOfDay())) {
                throw new TreasuryOperationException('No se permiten movimientos con fecha futura.');
            }

            // Periodo cerrado: ni altas, ni ajustes retroactivos.
            app(TreasuryPeriodService::class)->ensureDateIsOpen($dateValue->toDateString());

            // Sobregiro: solo se controla en movimientos manuales de bancos y billeteras.
            if ($source === TreasuryTransaction::SOURCE_MANUAL
                && $type === TreasuryTransaction::TYPE_EXPENSE
                && $account->type !== TreasuryAccount::TYPE_CASH
                && config('treasury.block_negative_balance', true)) {
                $available = $this->accountBalance($account, $dateValue->toDateString());

                if (round($available - $amount, 2) < 0) {
                    throw TreasuryOperationException::negativeBalance($account->displayName(), round($available - $amount, 2));
                }
            }

            $resolvedCurrency = app(TreasuryCurrencyService::class)->normalize($currency ?? $account->currency_type_id);
            $resolvedRate = $resolvedCurrency === 'PEN' ? null : $exchangeRate;
            $amountBase = $resolvedCurrency === 'PEN'
                ? $amount
                : (($resolvedRate !== null && $resolvedRate > 0) ? round($amount * $resolvedRate, 2) : null);

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
                'currency' => $resolvedCurrency,
                'exchange_rate' => $resolvedRate,
                'amount_base' => $amountBase,
                'cost_center_id' => $costCenterId,
                'reference' => $reference,
                'description' => $description,
                'payment_method_id' => $paymentMethodId,
                'source' => $source,
                'user_id' => $userId,
                'source_wallet_account_id' => $sourceWalletAccountId,
            ]);
        } catch (TreasuryOperationException $e) {
            // Bloqueos de periodo, sobregiro o moneda: el llamador decide qué hacer.
            throw $e;
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

        // Antes de la fecha de inicio del módulo no hay saldo que contar.
        $this->settings->applyCutoff($query);

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
     * Saldos por cuenta más el consolidado por moneda. Es lo que usa el
     * dashboard para no sumar soles con dólares.
     *
     * @return array{balances: array<int, array<string, mixed>>, consolidation: array<string, mixed>}
     */
    public function consolidatedBalances(?string $untilDate = null): array
    {
        $balances = $this->balancesByAccount($untilDate);

        return [
            'balances' => $balances,
            'consolidation' => app(TreasuryCurrencyService::class)->consolidate($balances, $untilDate),
        ];
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

        // Una billetera con banco vinculado no tiene saldo propio (su dinero vive en el
        // banco) y el banco suma el saldo de sus billeteras: se usa el saldo visible.
        $totals = $accounts->map(fn (TreasuryAccount $account) => [
            'account' => $account,
            'balance' => $this->displayBalance($account, $untilDate),
        ])->all();

        return $totals;
    }

    /**
     * Saldo que se muestra de una cuenta.
     *
     * Una billetera con banco vinculado no guarda saldo propio: su dinero vive en el
     * banco, así que su saldo visible es 0 y la cuenta bancaria suma los saldos de sus
     * billeteras vinculadas. Así el banco muestra todo lo que llegó por ellas (Yape,
     * Culqi...) sin contarlo dos veces en el total.
     */
    public function displayBalance(TreasuryAccount $account, ?string $untilDate = null): float
    {
        if ($account->type === TreasuryAccount::TYPE_WALLET) {
            return $this->bankForWallet($account) ? 0.0 : $this->accountBalance($account, $untilDate);
        }

        if ($account->type !== TreasuryAccount::TYPE_BANK) {
            return $this->accountBalance($account, $untilDate);
        }

        $total = $this->accountBalance($account, $untilDate);

        foreach ($this->walletsLinkedTo($account) as $wallet) {
            $total += $this->accountBalance($wallet, $untilDate);
        }

        return round($total, 2);
    }

    /** La billetera no muestra saldo porque su banco vinculado tiene cuenta de tesorería. */
    public function hidesBalance(TreasuryAccount $account): bool
    {
        return $account->type === TreasuryAccount::TYPE_WALLET
            && $this->bankForWallet($account) !== null;
    }

    /** Cuenta de tesorería del banco vinculado a la billetera. */
    private function bankForWallet(TreasuryAccount $wallet): ?TreasuryAccount
    {
        $bankAccountId = $wallet->companyBilletera?->bank_account_id;

        if (! $bankAccountId) {
            return null;
        }

        return TreasuryAccount::query()
            ->active()
            ->banks()
            ->where('bank_account_id', (int) $bankAccountId)
            ->first();
    }

    /** Billeteras activas cuyo banco vinculado es esta cuenta bancaria. */
    private function walletsLinkedTo(TreasuryAccount $bank): \Illuminate\Support\Collection
    {
        if (! $bank->bank_account_id) {
            return collect();
        }

        return TreasuryAccount::query()
            ->active()
            ->wallets()
            ->whereHas('companyBilletera', function ($query) use ($bank) {
                $query->where('bank_account_id', (int) $bank->bank_account_id);
            })
            ->get();
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
            ->with(['category', 'paymentMethod', 'costCenter'])
            ->orderBy('transaction_date')
            ->orderBy('id');

        // El libro arranca en la fecha de inicio del módulo.
        $this->settings->applyCutoff($query);

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
                'cost_center_id' => $tx->cost_center_id,
                'cost_center' => $tx->costCenter?->name,
                'amount' => (float) $tx->amount,
                'currency' => $tx->currency ?: 'PEN',
                'amount_base' => $tx->amount_base !== null ? (float) $tx->amount_base : null,
                'running_balance' => round($balance, 2),
                'reference' => $tx->reference,
                'description' => $tx->description,
                'origin_type' => $tx->origin_type,
                'origin_id' => $tx->origin_id,
                'source' => $tx->source,
                'reconciled' => $tx->reconciled_at !== null,
                'locked' => $tx->treasury_reconciliation_id !== null,
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
