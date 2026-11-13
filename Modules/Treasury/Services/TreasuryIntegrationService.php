<?php

namespace Modules\Treasury\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransaction;

/**
 * Capa de integración entre los módulos que mueven dinero y el libro de
 * tesorería.
 *
 * Responsabilidades:
 *  - Garantizar idempotencia por clave única en base de datos (no en PHP).
 *  - Resolver la cuenta de tesorería y validar moneda/tipo de cambio.
 *  - Registrar el movimiento con monto positivo y trazabilidad del origen.
 *  - Dejar rastro de lo que no se pudo registrar (failed) o no aplica (ignored).
 *  - Revertir sin duplicar y sin permitir reversiones repetidas.
 *
 * Nunca crea un segundo libro: escribe en treasury_transactions a través de
 * TreasuryLedgerService.
 */
class TreasuryIntegrationService
{
    public function __construct(
        private TreasuryLedgerService $ledger,
        private PaymentAccountResolver $resolver,
    ) {
    }

    /**
     * Registra una operación financiera. Si la clave de idempotencia ya existe
     * devuelve la fila existente sin volver a tocar el libro.
     */
    public function register(FinancialMovement $movement): TreasurySourceMovement
    {
        $this->assertValid($movement);

        $key = $this->idempotencyKey($movement);

        $existing = TreasurySourceMovement::query()->where('idempotency_key', $key)->first();

        if ($existing) {
            return $existing;
        }

        $amount = round($movement->amount, 2);
        $currency = strtoupper(trim($movement->currency ?: 'PEN'));
        $rate = $movement->exchangeRate !== null ? round((float) $movement->exchangeRate, 4) : null;
        $date = $movement->date instanceof Carbon ? $movement->date->copy() : Carbon::parse($movement->date);

        $amountBase = $currency === 'PEN'
            ? $amount
            : (($rate !== null && $rate > 0) ? round($amount * $rate, 2) : null);

        try {
            $bridge = TreasurySourceMovement::create([
                'module' => $movement->module,
                'source_type' => $movement->sourceType,
                'source_id' => $movement->sourceId,
                'source_line' => $movement->sourceLine,
                'operation' => $movement->operation,
                'type' => $movement->type,
                'status' => TreasurySourceMovement::STATUS_PENDING,
                'amount' => $amount,
                'currency' => $currency,
                'exchange_rate' => $currency === 'PEN' ? null : $rate,
                'amount_base' => $amountBase ?? $amount,
                'movement_date' => $date->toDateString(),
                'payment_method_id' => $movement->paymentMethodId,
                'treasury_account_id' => $movement->treasuryAccountId,
                'reversal_of_id' => $movement->reversalOfId,
                'idempotency_key' => $key,
                'reference' => $movement->reference,
                'description' => $movement->description,
                'source' => $movement->source,
                'meta' => $movement->meta ?: null,
                'user_id' => $movement->userId,
            ]);
        } catch (QueryException $e) {
            // Carrera entre dos procesos con el mismo movimiento: la clave
            // única es la que manda y se devuelve la fila que ganó.
            $existing = TreasurySourceMovement::query()->where('idempotency_key', $key)->first();

            if ($existing) {
                return $existing;
            }

            throw $e;
        }

        if ($amountBase === null) {
            return $this->markFailed($bridge, "Tipo de cambio no disponible para la moneda {$currency}.");
        }

        return $this->process($bridge);
    }

    /**
     * Devuelve la fila puente ya existente para ese movimiento (o null).
     * Sirve al informe de diferencias del backfill, que no debe escribir.
     */
    public function alreadyRegistered(FinancialMovement $movement): ?TreasurySourceMovement
    {
        return TreasurySourceMovement::query()
            ->where('idempotency_key', $this->idempotencyKey($movement))
            ->first();
    }

    /**
     * Procesa (o reintenta) una fila puente: resuelve cuenta, convierte y crea
     * el movimiento del libro. Es seguro llamarlo varias veces.
     */
    public function process(TreasurySourceMovement $bridge): TreasurySourceMovement
    {
        if (in_array($bridge->status, $this->finalStatuses(), true)) {
            return $bridge;
        }

        if ($bridge->status === TreasurySourceMovement::STATUS_PROCESSING && ! $bridge->isStale()) {
            // Otro proceso lo tiene tomado: no interferir.
            return $bridge;
        }

        try {
            return DB::transaction(function () use ($bridge) {
                $locked = TreasurySourceMovement::query()->lockForUpdate()->find($bridge->id);

                if (! $locked) {
                    return $bridge;
                }

                if (in_array($locked->status, $this->finalStatuses(), true)) {
                    return $locked;
                }

                $locked->status = TreasurySourceMovement::STATUS_PROCESSING;
                $locked->error_message = null;
                $locked->save();

                $account = $this->resolveAccount($locked);

                if (! $account) {
                    $locked->status = TreasurySourceMovement::STATUS_IGNORED;
                    $locked->error_message = 'Sin cuenta de tesorería aplicable: método excluido, sin mapeo o cuenta inactiva.';
                    $locked->processed_at = now();
                    $locked->save();

                    return $locked;
                }

                $amount = $this->ledgerAmount($locked, $account);

                if ($amount === null) {
                    $locked->status = TreasurySourceMovement::STATUS_FAILED;
                    $locked->error_message = "La cuenta {$account->displayName()} no admite la moneda {$locked->currency}.";
                    $locked->save();

                    return $locked;
                }

                $transaction = $this->writeToLedger($locked, $account, $amount);

                if (! $transaction) {
                    $locked->status = TreasurySourceMovement::STATUS_FAILED;
                    $locked->error_message = 'El libro de tesorería no creó el movimiento.';
                    $locked->save();

                    return $locked;
                }

                $locked->treasury_account_id = $account->id;
                $locked->treasury_transaction_id = $transaction->id;
                $locked->status = TreasurySourceMovement::STATUS_PROCESSED;
                $locked->processed_at = now();
                $locked->error_message = null;
                $locked->save();

                return $locked;
            });
        } catch (\Throwable $e) {
            Log::error('[Treasury] Falló el procesamiento del movimiento de integración', [
                'movement_id' => $bridge->id,
                'origin' => $bridge->source_type . '#' . $bridge->source_id,
                'operation' => $bridge->operation,
                'error' => $e->getMessage(),
            ]);

            return $this->markFailed($bridge, $e->getMessage());
        }
    }

    /**
     * Reasigna una cuenta explícita a una operación que quedó sin registrar
     * (ignorada o fallida) y la reprocesa.
     *
     * Es el camino de la vinculación manual de cobros: el método de pago no
     * tenía cuenta (efectivo, giro) y el usuario indica dónde entró el dinero.
     * No duplica: la clave única de la fila puente ya existe. Nunca toca una
     * operación ya procesada o revertida.
     */
    public function assignAccount(
        TreasurySourceMovement $bridge,
        TreasuryAccount $account,
        ?int $userId = null,
    ): TreasurySourceMovement {
        if (in_array($bridge->status, [
            TreasurySourceMovement::STATUS_PROCESSED,
            TreasurySourceMovement::STATUS_REVERSED,
        ], true)) {
            return $bridge;
        }

        $meta = $bridge->meta ?: [];
        $meta['assigned_by'] = $userId;
        $meta['assigned_at'] = now()->toIso8601String();
        $meta['assigned_from_status'] = $bridge->status;

        $bridge->treasury_account_id = $account->id;
        $bridge->status = TreasurySourceMovement::STATUS_PENDING;
        $bridge->error_message = null;
        $bridge->processed_at = null;
        $bridge->meta = $meta;
        $bridge->save();

        return $this->process($bridge);
    }

    /**
     * Revierte el movimiento original: anula su fila del libro (se conserva con
     * voided_at) y deja una fila puente de reversión enlazada. No crea un
     * segundo ingreso ni permite revertir dos veces.
     */
    public function reverse(TreasurySourceMovement $original, ?string $reason = null, ?int $userId = null): ?TreasurySourceMovement
    {
        if ($original->status === TreasurySourceMovement::STATUS_REVERSED) {
            return null;
        }

        return DB::transaction(function () use ($original, $reason, $userId) {
            $locked = TreasurySourceMovement::query()->lockForUpdate()->find($original->id);

            if (! $locked || $locked->status === TreasurySourceMovement::STATUS_REVERSED) {
                return null;
            }

            if ($locked->treasury_transaction_id) {
                $transaction = TreasuryTransaction::query()->find($locked->treasury_transaction_id);

                if ($transaction && ! $transaction->voided_at) {
                    $transaction->void($userId);
                }
            }

            $reversal = TreasurySourceMovement::query()->firstOrCreate(
                ['idempotency_key' => $this->idempotencyKeyForReversal($locked)],
                [
                    'module' => $locked->module,
                    'source_type' => $locked->source_type,
                    'source_id' => $locked->source_id,
                    'source_line' => $locked->source_line,
                    'operation' => $locked->operation . '_reversal',
                    'type' => $locked->type === TreasurySourceMovement::TYPE_INCOME
                        ? TreasurySourceMovement::TYPE_EXPENSE
                        : TreasurySourceMovement::TYPE_INCOME,
                    'status' => TreasurySourceMovement::STATUS_PROCESSED,
                    'amount' => $locked->amount,
                    'currency' => $locked->currency,
                    'exchange_rate' => $locked->exchange_rate,
                    'amount_base' => $locked->amount_base,
                    'movement_date' => now()->toDateString(),
                    'payment_method_id' => $locked->payment_method_id,
                    'treasury_account_id' => $locked->treasury_account_id,
                    'treasury_transaction_id' => $locked->treasury_transaction_id,
                    'reversal_of_id' => $locked->id,
                    'reference' => $locked->reference,
                    'description' => Str::limit('Reversión: ' . ($reason ?: $locked->description ?: $locked->operation), 250),
                    'source' => $locked->source,
                    'meta' => [
                        'reason' => $reason,
                        'original_status' => $locked->status,
                        'original_operation' => $locked->operation,
                    ],
                    'processed_at' => now(),
                    'user_id' => $userId,
                ]
            );

            $locked->status = TreasurySourceMovement::STATUS_REVERSED;
            $locked->save();

            return $reversal;
        });
    }

    /**
     * Revierte todos los movimientos vigentes de un origen concreto.
     * Devuelve cuántos se revirtieron.
     */
    public function reverseOrigin(
        string $module,
        string $sourceType,
        int $sourceId,
        ?string $reason = null,
        ?int $userId = null,
    ): int {
        $movements = TreasurySourceMovement::query()
            ->where('module', $module)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereNull('reversal_of_id')
            ->whereIn('status', [TreasurySourceMovement::STATUS_PROCESSED, TreasurySourceMovement::STATUS_IGNORED])
            ->get();

        $reversed = 0;

        foreach ($movements as $movement) {
            if ($this->reverse($movement, $reason, $userId)) {
                $reversed++;
            }
        }

        return $reversed;
    }

    /**
     * Registra una devolución efectiva de dinero (sale dinero de la cuenta).
     * Distinto de reverse(): aquí el egreso es real, no una anulación.
     */
    public function registerRefund(FinancialMovement $movement): TreasurySourceMovement
    {
        return $this->register(
            $movement->asType(TreasurySourceMovement::TYPE_EXPENSE, $movement->operation ?: 'refund')
        );
    }

    /**
     * Reintenta los movimientos fallidos o pendientes (recuperación
     * administrativa). Devuelve el detalle por estado final.
     *
     * @return array{processed: int, ignored: int, failed: int}
     */
    public function retryPending(int $limit = 200, ?int $movementId = null): array
    {
        $query = TreasurySourceMovement::query()
            ->whereIn('status', [
                TreasurySourceMovement::STATUS_FAILED,
                TreasurySourceMovement::STATUS_PENDING,
            ])
            ->orderBy('id')
            ->limit($limit);

        if ($movementId) {
            $query->whereKey($movementId);
        }

        $result = ['processed' => 0, 'ignored' => 0, 'failed' => 0];

        foreach ($query->get() as $bridge) {
            $processed = $this->process($bridge);

            match ($processed->status) {
                TreasurySourceMovement::STATUS_PROCESSED => $result['processed']++,
                TreasurySourceMovement::STATUS_IGNORED => $result['ignored']++,
                default => $result['failed']++,
            };
        }

        return $result;
    }

    /**
     * Crea el movimiento en el libro. La idempotencia la garantiza esta capa
     * (clave única puente), por eso se omite la firma histórica del libro, que
     * bloqueaba dos pagos iguales del mismo día.
     */
    private function writeToLedger(
        TreasurySourceMovement $bridge,
        TreasuryAccount $account,
        float $amount,
    ): ?TreasuryTransaction {
        $arguments = [
            $account,
            $bridge->movement_date->toDateString(),
            $amount,
            $this->categoryId($bridge),
            $bridge->source_type,
            (int) $bridge->source_id,
            $bridge->reference,
            $bridge->description,
            $bridge->payment_method_id,
            $bridge->source,
            $bridge->user_id,
            false, // sin firma: la clave única de la puente ya evita duplicados
        ];

        return $bridge->type === TreasurySourceMovement::TYPE_INCOME
            ? $this->ledger->registerIncome(...$arguments)
            : $this->ledger->registerExpense(...$arguments);
    }

    private function resolveAccount(TreasurySourceMovement $bridge): ?TreasuryAccount
    {
        if ($bridge->treasury_account_id) {
            return TreasuryAccount::query()->active()->find($bridge->treasury_account_id);
        }

        if (! $bridge->payment_method_id) {
            return null;
        }

        return $this->resolver->resolveForMethod((int) $bridge->payment_method_id);
    }

    /**
     * Monto a escribir en el libro según la moneda de la cuenta.
     * No se mezclan monedas: si no hay conversión posible, no se registra.
     */
    private function ledgerAmount(TreasurySourceMovement $bridge, TreasuryAccount $account): ?float
    {
        $accountCurrency = strtoupper((string) ($account->currency_type_id ?: 'PEN'));
        $currency = strtoupper((string) $bridge->currency);

        if ($accountCurrency === $currency) {
            return round((float) $bridge->amount, 2);
        }

        if ($accountCurrency === 'PEN') {
            return round((float) $bridge->amount_base, 2);
        }

        return null;
    }

    private function categoryId(TreasurySourceMovement $bridge): ?int
    {
        $name = match ($bridge->operation) {
            'sale_income', 'full_payment', 'down_payment' => 'Cobro de venta',
            'quota_payment', 'installment_payment' => 'Abono de cuota',
            default => $bridge->type === TreasurySourceMovement::TYPE_INCOME ? 'Otros ingresos' : 'Otros egresos',
        };

        return $this->ledger->systemCategoryId($name);
    }

    /**
     * @return array<int, string>
     */
    private function finalStatuses(): array
    {
        return [
            TreasurySourceMovement::STATUS_PROCESSED,
            TreasurySourceMovement::STATUS_IGNORED,
            TreasurySourceMovement::STATUS_REVERSED,
        ];
    }

    private function markFailed(TreasurySourceMovement $bridge, string $message): TreasurySourceMovement
    {
        try {
            TreasurySourceMovement::query()->whereKey($bridge->id)->update([
                'status' => TreasurySourceMovement::STATUS_FAILED,
                'error_message' => Str::limit($message, 900),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[Treasury] No se pudo marcar como fallido el movimiento', [
                'movement_id' => $bridge->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $bridge->refresh();
    }

    private function assertValid(FinancialMovement $movement): void
    {
        if (! in_array($movement->type, [TreasurySourceMovement::TYPE_INCOME, TreasurySourceMovement::TYPE_EXPENSE], true)) {
            throw new InvalidArgumentException("Tipo de movimiento inválido: {$movement->type}");
        }

        if ($movement->amount <= 0) {
            throw new InvalidArgumentException('El monto del movimiento debe ser mayor que cero.');
        }

        if ($movement->sourceId <= 0) {
            throw new InvalidArgumentException('El movimiento requiere un origen identificable.');
        }

        foreach (['module' => $movement->module, 'sourceType' => $movement->sourceType, 'operation' => $movement->operation] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException("El campo {$field} del movimiento es obligatorio.");
            }
        }
    }

    private function idempotencyKey(FinancialMovement $movement): string
    {
        return implode('|', [
            $movement->module,
            $movement->sourceType,
            $movement->sourceId,
            $movement->sourceLine ?? '-',
            $movement->operation,
            $movement->reversalOfId ?? '-',
        ]);
    }

    private function idempotencyKeyForReversal(TreasurySourceMovement $original): string
    {
        return implode('|', [
            $original->module,
            $original->source_type,
            $original->source_id,
            $original->source_line ?? '-',
            $original->operation . '_reversal',
            $original->id,
        ]);
    }
}
