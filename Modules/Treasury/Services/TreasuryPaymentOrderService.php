<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Órdenes de pago de Tesorería.
 *
 * El dinero sale del libro recién al pagar, y ese pago pasa por la capa de
 * integración (idempotencia por clave única, reversión y auditoría). Crear la
 * orden no mueve saldos.
 */
class TreasuryPaymentOrderService
{
    public function __construct(
        private TreasuryIntegrationService $integration,
        private TreasuryCurrencyService $currency,
        private TreasuryAuditService $audit,
    ) {
    }

    /**
     * Crea la orden. Es idempotente cuando llega `idempotency_key`: el mismo
     * formulario enviado dos veces devuelve la orden ya creada.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): TreasuryPaymentOrder
    {
        $key = $data['idempotency_key'] ?? null;

        if ($key) {
            $existing = TreasuryPaymentOrder::query()->where('idempotency_key', $key)->first();

            if ($existing) {
                return $existing;
            }
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new TreasuryOperationException('El monto de la orden debe ser mayor que cero.');
        }

        $currency = $this->currency->normalize($data['currency'] ?? null);
        $account = ! empty($data['treasury_account_id'])
            ? TreasuryAccount::query()->active()->find($data['treasury_account_id'])
            : null;

        if ($account && $this->currency->accountCurrency($account) !== $currency) {
            throw TreasuryOperationException::currencyMismatch(
                $this->currency->accountCurrency($account),
                $currency
            );
        }

        $threshold = (float) config('treasury.approval_threshold', 0);
        $status = $threshold > 0 && $amount >= $threshold
            ? TreasuryPaymentOrder::STATUS_PENDING
            : TreasuryPaymentOrder::STATUS_APPROVED;

        $order = DB::transaction(function () use ($data, $userId, $amount, $currency, $status, $key) {
            $order = TreasuryPaymentOrder::create([
                'idempotency_key' => $key,
                'type' => $data['type'] ?? TreasuryPaymentOrder::TYPE_PURCHASE,
                'supplier_id' => $data['supplier_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'description' => $data['description'],
                'treasury_category_id' => $data['treasury_category_id'] ?? null,
                'currency' => $currency,
                'amount' => $amount,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'scheduled_date' => $data['scheduled_date'] ?? null,
                'treasury_account_id' => $data['treasury_account_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'status' => $status,
                'recurring_id' => $data['recurring_id'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $order->code = 'OP-' . now()->format('Y') . '-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
            $order->save();

            return $order;
        });

        $this->audit->log('payment_order', $order->id, 'created', [], [
            'code' => $order->code,
            'type' => $order->type,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
        ]);

        return $order;
    }

    public function approve(TreasuryPaymentOrder $order, ?int $userId = null): TreasuryPaymentOrder
    {
        if ($order->status === TreasuryPaymentOrder::STATUS_APPROVED) {
            return $order;
        }

        if (! $order->canBeApproved()) {
            throw TreasuryOperationException::invalidStatus($order->statusLabel(), 'aprobar');
        }

        if (config('treasury.require_different_approver', true) && $order->created_by === $userId) {
            throw new TreasuryOperationException('Quien creó la orden no puede aprobarla: se requiere otro usuario.');
        }

        $order->status = TreasuryPaymentOrder::STATUS_APPROVED;
        $order->approved_by = $userId;
        $order->approved_at = now();
        $order->save();

        $this->audit->log('payment_order', $order->id, 'approved', [
            'status' => TreasuryPaymentOrder::STATUS_PENDING,
        ], [
            'status' => TreasuryPaymentOrder::STATUS_APPROVED,
        ]);

        return $order;
    }

    public function reject(TreasuryPaymentOrder $order, string $reason, ?int $userId = null): TreasuryPaymentOrder
    {
        if (! $order->canBeApproved()) {
            throw TreasuryOperationException::invalidStatus($order->statusLabel(), 'rechazar');
        }

        $order->status = TreasuryPaymentOrder::STATUS_REJECTED;
        $order->rejected_reason = $reason;
        $order->approved_by = $userId;
        $order->approved_at = now();
        $order->save();

        $this->audit->log('payment_order', $order->id, 'rejected', [], ['reason' => $reason]);

        return $order;
    }

    /**
     * Ejecuta el pago: escribe el egreso en el libro y marca la orden.
     * Idempotente: si ya está pagada devuelve la orden sin volver a escribir.
     */
    public function pay(
        TreasuryPaymentOrder $order,
        ?int $userId = null,
        ?string $date = null,
        ?int $accountId = null,
    ): TreasuryPaymentOrder {
        if ($order->status === TreasuryPaymentOrder::STATUS_PAID) {
            return $order;
        }

        if (! $order->canBePaid()) {
            throw TreasuryOperationException::invalidStatus($order->statusLabel(), 'pagar');
        }

        $account = TreasuryAccount::query()
            ->active()
            ->find($accountId ?: $order->treasury_account_id);

        if (! $account) {
            throw new TreasuryOperationException('La orden no tiene una cuenta de tesorería activa para pagar.');
        }

        $currency = $this->currency->normalize($order->currency);
        $accountCurrency = $this->currency->accountCurrency($account);

        if ($accountCurrency !== $currency) {
            throw TreasuryOperationException::currencyMismatch($accountCurrency, $currency);
        }

        $payDate = $date ?: now()->toDateString();
        $rate = $currency === 'PEN'
            ? null
            : ($this->currency->rate($currency, $payDate)['rate'] ?? null);

        $movement = new FinancialMovement(
            module: 'treasury',
            sourceType: 'payment_order',
            sourceId: (int) $order->id,
            operation: 'paid',
            type: TreasurySourceMovement::TYPE_EXPENSE,
            amount: (float) $order->amount,
            date: $payDate,
            paymentMethodId: $order->payment_method_id,
            currency: $currency,
            exchangeRate: $rate,
            reference: $order->code,
            description: $order->description,
            treasuryAccountId: $account->id,
            userId: $userId,
            meta: [
                'category_id' => $order->treasury_category_id,
                'cost_center_id' => $order->cost_center_id,
                'supplier_id' => $order->supplier_id,
                'payment_order_code' => $order->code,
            ],
            source: 'manual',
        );

        $bridge = $this->integration->register($movement);

        if ($bridge->status !== TreasurySourceMovement::STATUS_PROCESSED) {
            throw new TreasuryOperationException(
                'No se pudo pagar la orden: ' . ($bridge->error_message ?: 'el libro no registró el egreso.')
            );
        }

        $order->status = TreasuryPaymentOrder::STATUS_PAID;
        $order->paid_by = $userId;
        $order->paid_at = Carbon::parse($payDate)->endOfDay();
        $order->treasury_transaction_id = $bridge->treasury_transaction_id;
        $order->treasury_account_id = $account->id;
        $order->save();

        $this->audit->log('payment_order', $order->id, 'paid', [
            'status' => TreasuryPaymentOrder::STATUS_APPROVED,
        ], [
            'status' => TreasuryPaymentOrder::STATUS_PAID,
            'treasury_transaction_id' => $bridge->treasury_transaction_id,
            'account' => $account->displayName(),
        ]);

        return $order;
    }

    /**
     * Anula la orden. Si ya estaba pagada, revierte el egreso por la capa de
     * integración (nunca borra el movimiento).
     */
    public function void(TreasuryPaymentOrder $order, ?int $userId = null, ?string $reason = null): TreasuryPaymentOrder
    {
        if ($order->status === TreasuryPaymentOrder::STATUS_VOIDED) {
            return $order;
        }

        if ($order->status === TreasuryPaymentOrder::STATUS_PAID) {
            $bridge = TreasurySourceMovement::query()
                ->where('module', 'treasury')
                ->where('source_type', 'payment_order')
                ->where('source_id', $order->id)
                ->whereNull('reversal_of_id')
                ->first();

            if (! $bridge) {
                throw new TreasuryOperationException('La orden está pagada pero no se encontró su movimiento de origen.');
            }

            $reversal = $this->integration->reverse($bridge, $reason ?: 'Anulación de orden ' . $order->code, $userId);

            if (! $reversal) {
                throw TreasuryOperationException::reconciled();
            }
        }

        $order->status = TreasuryPaymentOrder::STATUS_VOIDED;
        $order->voided_at = now();
        $order->voided_by = $userId;
        $order->save();

        $this->audit->log('payment_order', $order->id, 'voided', [], [
            'reason' => $reason,
        ]);

        return $order;
    }
}
