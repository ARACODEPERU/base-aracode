<?php

namespace Modules\Treasury\Services;

use App\Models\SaleDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Detracciones del IGV.
 *
 * Reglas:
 * - Solo facturas (tipo 01) desde el umbral configurado (S/ 700 por defecto).
 * - El porcentaje por defecto es 12 % y se calcula sobre el total de la
 *   factura (con IGV). Puede guardarse otro (10 % o 4 %) por factura.
 * - El dinero se registra cuando se cobra: la parte detraída entra a la
 *   cuenta «Detracción» y el resto a la cuenta indicada.
 * - Con auto-detracción el cliente paga todo a la cuenta de la empresa y esta
 *   traslada el importe detraído a la cuenta de detracción.
 * - La cuenta de detracción solo paga IGV, libera saldo a una cuenta de la
 *   empresa o recibe un ajuste manual (estos dos últimos son críticos y
 *   requieren la contraseña del usuario).
 */
class TreasuryDetractionService
{
    public const ORIGIN_DETRACTION = 'sale_detraction';

    public const ORIGIN_AUTO_MOVE = 'detraccion_auto';

    public const ORIGIN_ADJUST = 'detraccion_ajuste';

    public const ORIGIN_RELEASE = 'detraccion_liberacion';

    private ?TreasuryAccount $cuenta = null;

    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryCurrencyService $currency,
        private TreasuryAuditService $audit,
        private TreasurySettingsService $settings,
    ) {
    }

    /**
     * Cuenta de detracción (se crea si todavía no existe).
     */
    public function account(): TreasuryAccount
    {
        if ($this->cuenta) {
            return $this->cuenta;
        }

        $this->cuenta = TreasuryAccount::query()->detraction()->first()
            ?? TreasuryAccount::create([
                'type' => TreasuryAccount::TYPE_DETRACTION,
                'label' => 'Detracción',
                'currency_type_id' => 'PEN',
                'opening_balance' => 0,
                'status' => true,
            ]);

        return $this->cuenta;
    }

    /**
     * ¿La factura está sujeta a detracción?
     */
    public function applies(SaleDocument $document): bool
    {
        if ((string) $document->invoice_type_doc !== '01') {
            return false;
        }

        if ((int) ($document->status ?? 1) === 3) {
            return false;
        }

        if (in_array((string) $document->invoice_status, ['Anulada', 'Por anular', 'Rechazada', 'Enviada Por Anular'], true)) {
            return false;
        }

        return $this->documentTotal($document) >= (float) config('treasury.detraction_threshold', 700);
    }

    /**
     * Porcentaje detraído de la factura (12 % por defecto).
     */
    public function percent(SaleDocument $document): float
    {
        $percent = (float) ($document->detraccion_percent ?? 0);

        return $percent > 0 ? $percent : (float) config('treasury.detraction_percent', 12);
    }

    /**
     * Importe detraído de la factura, en su moneda.
     */
    public function amount(SaleDocument $document): float
    {
        $guardado = (float) ($document->detraccion_amount ?? 0);

        if ($guardado > 0) {
            return round($guardado, 2);
        }

        return round($this->documentTotal($document) * $this->percent($document) / 100, 2);
    }

    /**
     * Marca la factura con su estado de detracción. Es lo que pinta la señal
     * «con detracción / auto-detracción» en Ventas.
     */
    public function syncDocument(SaleDocument $document, bool $force = false): void
    {
        if (! Schema::hasColumn('sale_documents', 'detraccion')) {
            return;
        }

        if (! $this->applies($document)) {
            if ((bool) $document->detraccion || (float) $document->detraccion_amount > 0) {
                $document->detraccion = false;
                $document->detraccion_amount = 0;
                $document->detraccion_percent = null;
                $document->save();
            }

            return;
        }

        $percent = $this->percent($document);
        $amount = round($this->documentTotal($document) * $percent / 100, 2);

        if (! $force && (bool) $document->detraccion && (float) $document->detraccion_amount > 0) {
            return;
        }

        $document->detraccion = true;
        $document->detraccion_percent = $percent;
        $document->detraccion_amount = $amount;
        $document->save();
    }

    /**
     * Reparte un cobro entre lo que entra a la cuenta y lo que va a detracción.
     *
     * La detracción se descuenta una sola vez y solo cuando el cobro cubre el
     * total de la factura; en abonos parciales se cobra completo y la
     * detracción se aplica cuando se completa.
     *
     * @return array{net: float, detraction: float, applies: bool, auto: bool, partial: bool}
     */
    public function splitForPayment(SaleDocument $document, float $amount): array
    {
        $amount = round($amount, 2);
        $sinDetraccion = ['net' => $amount, 'detraction' => 0.0, 'applies' => false, 'auto' => false, 'partial' => false];

        if (! $this->applies($document)) {
            return $sinDetraccion;
        }

        $auto = (bool) $document->detraccion_auto;
        $detraction = $this->amount($document);

        // La auto-detracción cobra todo y luego la empresa traslada el importe.
        if ($auto) {
            return ['net' => $amount, 'detraction' => $detraction, 'applies' => true, 'auto' => true, 'partial' => false];
        }

        if ($amount + 0.01 < $this->documentTotal($document)) {
            return ['net' => $amount, 'detraction' => 0.0, 'applies' => true, 'auto' => false, 'partial' => true];
        }

        if ($this->incomeExists($document)) {
            return ['net' => $amount, 'detraction' => 0.0, 'applies' => true, 'auto' => false, 'partial' => false];
        }

        return [
            'net' => round(max(0, $amount - $detraction), 2),
            'detraction' => $detraction,
            'applies' => true,
            'auto' => false,
            'partial' => false,
        ];
    }

    /**
     * Registra el ingreso de la detracción en su cuenta. Idempotente por factura.
     */
    public function registerIncome(SaleDocument $document, string|Carbon $date, ?int $userId = null): ?TreasuryTransaction
    {
        if (! $this->applies($document) || $this->incomeExists($document)) {
            return null;
        }

        $account = $this->account();
        $amount = $this->amountInAccountCurrency($document);

        if ($amount <= 0) {
            return null;
        }

        return $this->ledger->registerIncome(
            $account,
            $date,
            $amount,
            $this->ledger->systemCategoryId('Detracción'),
            self::ORIGIN_DETRACTION,
            (int) $document->id,
            $this->documentNumber($document),
            'Detracción ' . rtrim(rtrim(number_format($this->percent($document), 2, '.', ''), '0'), '.') . ' % de ' . $this->documentNumber($document),
            null,
            TreasuryTransaction::SOURCE_AUTO,
            $userId,
            false,
            null,
            'PEN',
            $this->currency->normalize((string) $document->invoice_type_currency) === 'PEN'
                ? null
                : ($document->exchange_rate !== null ? (float) $document->exchange_rate : null),
        );
    }

    /**
     * Auto-detracción: la empresa mueve el importe detraído desde la cuenta
     * donde entró el cobro hacia la cuenta de detracción. Idempotente.
     */
    public function registerAutoTransfer(
        SaleDocument $document,
        TreasuryAccount $source,
        string|Carbon $date,
        ?int $userId = null,
    ): void {
        if (! $this->applies($document) || $this->autoMoveExists($document)) {
            return;
        }

        if ($source->isDetraction()) {
            return;
        }

        $account = $this->account();
        $amount = $this->amountInAccountCurrency($document);

        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($document, $source, $account, $amount, $date, $userId) {
            $reference = $this->documentNumber($document);
            $description = 'Auto-detracción de ' . $reference;

            $this->ledger->registerExpense(
                $source,
                $date,
                $amount,
                $this->ledger->systemCategoryId('Detracción'),
                self::ORIGIN_AUTO_MOVE,
                (int) $document->id,
                $reference,
                $description,
                null,
                TreasuryTransaction::SOURCE_AUTO,
                $userId,
                false,
                null,
                $this->currency->accountCurrency($source),
            );

            $this->ledger->registerIncome(
                $account,
                $date,
                $amount,
                $this->ledger->systemCategoryId('Detracción'),
                self::ORIGIN_AUTO_MOVE,
                (int) $document->id,
                $reference,
                $description,
                null,
                TreasuryTransaction::SOURCE_AUTO,
                $userId,
                false,
                null,
                'PEN',
                $this->currency->normalize((string) $document->invoice_type_currency) === 'PEN'
                    ? null
                    : ($document->exchange_rate !== null ? (float) $document->exchange_rate : null),
            );
        });
    }

    /**
     * Después de registrar un cobro del comprobante, deja la detracción en su
     * cuenta (ingreso directo o traslado interno según la marca).
     */
    public function afterPayment(
        SaleDocument $document,
        ?TreasuryAccount $account,
        string|Carbon $date,
        ?int $userId = null,
    ): void {
        if (! $this->applies($document)) {
            return;
        }

        if ((bool) $document->detraccion_auto && $account) {
            $this->registerAutoTransfer($document, $account, $date, $userId);

            return;
        }

        $this->registerIncome($document, $date, $userId);
    }

    /**
     * Resumen de la cuenta y de las detracciones registradas.
     *
     * @return array<string, mixed>
     */
    public function summary(int $limit = 60): array
    {
        $account = $this->account();
        $saldo = $this->ledger->accountBalance($account);

        $movimientos = TreasuryTransaction::query()
            ->where('treasury_account_id', $account->id)
            ->active()
            ->when($this->settings->startsOn(), fn ($q, $start) => $q->whereDate('transaction_date', '>=', $start))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $detracciones = TreasuryTransaction::query()
            ->where('treasury_account_id', $account->id)
            ->active()
            ->when($this->settings->startsOn(), fn ($q, $start) => $q->whereDate('transaction_date', '>=', $start))
            ->where('type', TreasuryTransaction::TYPE_INCOME)
            ->whereIn('origin_type', [self::ORIGIN_DETRACTION, self::ORIGIN_AUTO_MOVE])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $facturas = SaleDocument::query()
            ->whereIn('id', $detracciones->pluck('origin_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return [
            'account_id' => $account->id,
            'account_label' => $account->displayName(),
            'currency' => $this->currency->accountCurrency($account),
            'balance' => $saldo,
            'total_detracted' => round((float) TreasuryTransaction::query()
                ->where('treasury_account_id', $account->id)
                ->active()
                ->whereIn('origin_type', [self::ORIGIN_DETRACTION, self::ORIGIN_AUTO_MOVE])
                ->where('type', TreasuryTransaction::TYPE_INCOME)
                ->sum('amount'), 2),
            'total_released' => round((float) TreasuryTransaction::query()
                ->where('treasury_account_id', $account->id)
                ->active()
                ->where('origin_type', self::ORIGIN_RELEASE)
                ->where('type', TreasuryTransaction::TYPE_EXPENSE)
                ->sum('amount'), 2),
            'total_adjustments' => round(
                (float) TreasuryTransaction::query()
                    ->where('treasury_account_id', $account->id)
                    ->active()
                    ->where('origin_type', self::ORIGIN_ADJUST)
                    ->where('type', TreasuryTransaction::TYPE_INCOME)
                    ->sum('amount')
                - (float) TreasuryTransaction::query()
                    ->where('treasury_account_id', $account->id)
                    ->active()
                    ->where('origin_type', self::ORIGIN_ADJUST)
                    ->where('type', TreasuryTransaction::TYPE_EXPENSE)
                    ->sum('amount'),
                2
            ),
            'detractions' => $detracciones->map(function (TreasuryTransaction $movement) use ($facturas) {
                $factura = $facturas->get((int) $movement->origin_id);

                return [
                    'id' => $movement->id,
                    'date' => $movement->transaction_date?->format('Y-m-d'),
                    'document' => $movement->reference,
                    'document_date' => $factura?->invoice_broadcast_date ? substr((string) $factura->invoice_broadcast_date, 0, 10) : null,
                    'client' => $factura?->client_rzn_social,
                    'percent' => $factura ? $this->percent($factura) : null,
                    'amount' => (float) $movement->amount,
                    'auto' => (bool) ($factura?->detraccion_auto),
                    'status' => (string) ($factura?->invoice_status ?: '—'),
                ];
            })->all(),
            'movements' => $movimientos->map(fn (TreasuryTransaction $movement) => [
                'id' => $movement->id,
                'date' => $movement->transaction_date?->format('Y-m-d'),
                'type' => $movement->type,
                'origin' => $movement->origin_type,
                'description' => $movement->description,
                'reference' => $movement->reference,
                'amount' => (float) $movement->amount,
            ])->all(),
        ];
    }

    /**
     * Ajuste manual del saldo de detracción. Crítico: exige confirmación de
     * contraseña en el controlador y deja rastro en auditoría.
     */
    public function adjust(
        string $type,
        float $amount,
        string $date,
        string $reason,
        ?int $userId = null,
    ): TreasuryTransaction {
        $account = $this->account();
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new TreasuryOperationException('El importe del ajuste debe ser mayor que cero.');
        }

        if ($type === TreasuryTransaction::TYPE_EXPENSE) {
            $saldo = $this->ledger->accountBalance($account, Carbon::parse($date)->toDateString());

            if (round($saldo - $amount, 2) < 0) {
                throw new TreasuryOperationException('El ajuste supera el saldo de la cuenta de detracción.');
            }
        }

        $registrar = $type === TreasuryTransaction::TYPE_INCOME
            ? $this->ledger->registerIncome(...)
            : $this->ledger->registerExpense(...);

        $transaction = $registrar(
            $account,
            $date,
            $amount,
            $this->ledger->systemCategoryId('Ajuste de detracción'),
            self::ORIGIN_ADJUST,
            null,
            null,
            'Ajuste manual de detracción: ' . $reason,
            null,
            TreasuryTransaction::SOURCE_MANUAL,
            $userId,
            false,
            null,
            $this->currency->accountCurrency($account),
        );

        if (! $transaction) {
            throw new TreasuryOperationException('No se pudo registrar el ajuste de detracción.');
        }

        $this->audit->log('detraccion', $transaction->id, 'adjustment', [], [
            'type' => $type,
            'amount' => $amount,
            'date' => $date,
            'reason' => $reason,
            'balance_after' => $this->ledger->accountBalance($account),
        ]);

        return $transaction;
    }

    /**
     * Libera el saldo de detracción hacia una cuenta de la empresa.
     * Crítico: exige contraseña y deja dos movimientos enlazados.
     *
     * @return array{expense: TreasuryTransaction, income: TreasuryTransaction}
     */
    public function release(
        TreasuryAccount $destination,
        float $amount,
        string $date,
        ?string $reference = null,
        ?int $userId = null,
    ): array {
        if ($destination->isDetraction()) {
            throw new TreasuryOperationException('La liberación debe ir a una cuenta de la empresa, no a otra cuenta de detracción.');
        }

        $account = $this->account();
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new TreasuryOperationException('El importe a liberar debe ser mayor que cero.');
        }

        $saldo = $this->ledger->accountBalance($account, Carbon::parse($date)->toDateString());

        if (round($saldo - $amount, 2) < 0) {
            throw new TreasuryOperationException('La liberación supera el saldo disponible en la cuenta de detracción.');
        }

        $description = 'Liberación de detracción' . ($reference ? ' · ' . $reference : '');

        return DB::transaction(function () use ($account, $destination, $amount, $date, $reference, $userId, $description) {
            $expense = $this->ledger->registerExpense(
                $account,
                $date,
                $amount,
                $this->ledger->systemCategoryId('Detracción'),
                self::ORIGIN_RELEASE,
                (int) $destination->id,
                $reference,
                $description,
                null,
                TreasuryTransaction::SOURCE_MANUAL,
                $userId,
                false,
                null,
                $this->currency->accountCurrency($account),
            );

            $income = $this->ledger->registerIncome(
                $destination,
                $date,
                $amount,
                $this->ledger->systemCategoryId('Detracción'),
                self::ORIGIN_RELEASE,
                (int) $destination->id,
                $reference,
                $description,
                null,
                TreasuryTransaction::SOURCE_MANUAL,
                $userId,
                false,
                null,
                $this->currency->accountCurrency($destination),
            );

            if (! $expense || ! $income) {
                throw new TreasuryOperationException('No se pudo completar la liberación: revisa la cuenta destino y la fecha.');
            }

            $this->audit->log('detraccion', $income->id, 'released', [], [
                'amount' => $amount,
                'destination' => $destination->displayName(),
                'reference' => $reference,
                'balance_after' => $this->ledger->accountBalance($account),
            ]);

            return ['expense' => $expense, 'income' => $income];
        });
    }

    /**
     * Anula la detracción de un comprobante anulado: los movimientos de la
     * cuenta de detracción y, con auto-detracción, también el traslado y su
     * egreso en la cuenta de la empresa. Deja la factura sin marca.
     */
    public function voidDocument(SaleDocument $document, ?int $userId = null): int
    {
        $movements = TreasuryTransaction::query()
            ->where('origin_id', (int) $document->id)
            ->whereIn('origin_type', [self::ORIGIN_DETRACTION, self::ORIGIN_AUTO_MOVE])
            ->active()
            ->get();

        $count = 0;

        foreach ($movements as $movement) {
            try {
                if ($this->ledger->voidTransaction($movement, $userId)) {
                    $count++;
                }
            } catch (\Throwable $e) {
                // Conciliación cerrada: se conserva el movimiento y su rastro.
                Log::warning('[Treasury] No se pudo anular un movimiento de detracción', [
                    'transaction_id' => $movement->id,
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ((bool) $document->detraccion || (float) $document->detraccion_amount > 0) {
            $document->forceFill([
                'detraccion' => false,
                'detraccion_auto' => false,
                'detraccion_percent' => null,
                'detraccion_amount' => 0,
            ])->save();
        }

        return $count;
    }

    /**
     * Detracción registrada (en la cuenta) de las facturas emitidas en el rango.
     * Es lo que rebaja el IGV por pagar del periodo.
     */
    public function registeredBetween(string $from, string $to, string $currency = 'PEN'): float
    {
        if (! Schema::hasTable('sale_documents')) {
            return 0.0;
        }

        $months = DB::table('treasury_transactions as t')
            ->join('sale_documents as d', 'd.id', '=', 't.origin_id')
            ->where('t.origin_type', self::ORIGIN_DETRACTION)
            ->where('t.type', TreasuryTransaction::TYPE_INCOME)
            ->whereNull('t.voided_at')
            ->whereDate('d.invoice_broadcast_date', '>=', $from)
            ->whereDate('d.invoice_broadcast_date', '<=', $to)
            ->when($this->settings->startsOn(), fn ($q, $start) => $q->whereDate('d.invoice_broadcast_date', '>=', $start))
            ->where(function ($query) {
                $query->whereNull('d.invoice_status')
                    ->orWhereNotIn('d.invoice_status', ['Anulada', 'Por anular', 'Rechazada', 'Enviada Por Anular']);
            })
            ->where(function ($query) {
                $query->whereNull('d.status')->orWhere('d.status', '<>', 3);
            })
            ->whereRaw("UPPER(COALESCE(d.invoice_type_currency, 'PEN')) = ?", [strtoupper($currency)]);

        $inCuenta = (float) (clone $months)->sum('t.amount');

        // Auto-detracción: el traslado interno también deja la detracción en la cuenta.
        $auto = DB::table('treasury_transactions as t')
            ->join('sale_documents as d', 'd.id', '=', 't.origin_id')
            ->where('t.origin_type', self::ORIGIN_AUTO_MOVE)
            ->where('t.type', TreasuryTransaction::TYPE_INCOME)
            ->whereNull('t.voided_at')
            ->whereDate('d.invoice_broadcast_date', '>=', $from)
            ->whereDate('d.invoice_broadcast_date', '<=', $to)
            ->when($this->settings->startsOn(), fn ($q, $start) => $q->whereDate('d.invoice_broadcast_date', '>=', $start))
            ->where(function ($query) {
                $query->whereNull('d.invoice_status')
                    ->orWhereNotIn('d.invoice_status', ['Anulada', 'Por anular', 'Rechazada', 'Enviada Por Anular']);
            })
            ->where(function ($query) {
                $query->whereNull('d.status')->orWhere('d.status', '<>', 3);
            })
            ->whereRaw("UPPER(COALESCE(d.invoice_type_currency, 'PEN')) = ?", [strtoupper($currency)])
            ->sum('t.amount');

        return round($inCuenta + (float) $auto, 2);
    }

    /**
     * Importe detraído llevado a la moneda de la cuenta (PEN por defecto).
     * Una factura en dólares se convierte con su tipo de cambio congelado.
     */
    private function amountInAccountCurrency(SaleDocument $document): float
    {
        $amount = $this->amount($document);
        $currency = $this->currency->normalize((string) $document->invoice_type_currency);

        if ($currency === 'PEN') {
            return $amount;
        }

        $rate = $document->exchange_rate !== null ? (float) $document->exchange_rate : null;

        return $rate && $rate > 0 ? round($amount * $rate, 2) : 0.0;
    }

    private function documentTotal(SaleDocument $document): float
    {
        $total = (float) ($document->overall_total ?? 0);

        if ($total <= 0) {
            $total = (float) ($document->invoice_mto_imp_sale ?? 0);
        }

        return round($total, 2);
    }

    private function documentNumber(SaleDocument $document): string
    {
        return trim((string) $document->invoice_serie . '-' . (string) $document->invoice_correlative) ?: ('#' . $document->id);
    }

    private function incomeExists(SaleDocument $document): bool
    {
        return TreasuryTransaction::query()
            ->active()
            ->where('origin_type', self::ORIGIN_DETRACTION)
            ->where('origin_id', (int) $document->id)
            ->exists();
    }

    private function autoMoveExists(SaleDocument $document): bool
    {
        return TreasuryTransaction::query()
            ->active()
            ->where('origin_type', self::ORIGIN_AUTO_MOVE)
            ->where('origin_id', (int) $document->id)
            ->exists();
    }
}
