<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Entities\TreasuryTransfer;
use Modules\Treasury\Entities\TreasuryTransferProfile;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Transferencias entre cuentas de Tesorería.
 *
 * Reglas:
 * - La comisión del destino siempre se cobra sobre el monto acreditado.
 * - La comisión del origen se cobra solo cuando la transferencia es entre
 *   cuentas propias (siempre lo son aquí) y el usuario la activa.
 * - Las comisiones entre cuentas propias NO llevan IGV; junto con el ITF se
 *   registran como egresos propios con su categoría: son gastos, no parte del
 *   monto movido.
 * - El ITF se aplica salvo que el check "cuenta propia libre de ITF" esté
 *   activo para ese par.
 * - Si origen y destino tienen monedas distintas se exige tipo de cambio
 *   SUNAT y se registra el monto convertido en cada cuenta. Sin TC no se
 *   transfiere: nunca se copia el mismo número a dos monedas.
 * - La elección de comisiones e ITF se guarda por par origen-destino.
 */
class TreasuryTransferService
{
    public function __construct(
        private FeeCalculator $fees,
        private TreasuryCurrencyService $currency,
        private TreasuryAuditService $audit,
    ) {
    }

    /**
     * Calcula la transferencia sin guardarla (para el desglose en pantalla).
     *
     * @return array<string, float|bool|string|null>
     */
    public function preview(
        float $amount,
        float $fromFixedFee,
        float $fromPercentFee,
        float $toFixedFee,
        float $toPercentFee,
        bool $ownAccountItfFree,
        bool $chargeOriginFee,
        ?string $fromCurrency = null,
        ?string $toCurrency = null,
        ?float $exchangeRate = null,
    ): array {
        $fromCurrency = $this->currency->normalize($fromCurrency);
        $toCurrency = $this->currency->normalize($toCurrency ?? $fromCurrency);

        $rate = $fromCurrency === $toCurrency
            ? 1.0
            : ($exchangeRate ?: ($this->currency->rate($fromCurrency === 'PEN' ? $toCurrency : $fromCurrency)['rate'] ?? null));

        $destinationAmount = $fromCurrency === $toCurrency
            ? round($amount, 2)
            : $this->currency->convert($amount, $fromCurrency, $toCurrency);

        $conversionMissing = $destinationAmount === null;
        $destinationAmount = $destinationAmount ?? round($amount, 2);

        // Las comisiones entre bancos o cuentas propias no generan IGV.
        $fromFee = $chargeOriginFee
            ? $this->fees->commission($amount, $fromFixedFee, $fromPercentFee, false)
            : ['comision' => 0.0, 'igv' => 0.0, 'total' => 0.0];
        $toFee = $this->fees->commission($destinationAmount, $toFixedFee, $toPercentFee, false);

        // ITF: se calcula sobre el monto que sale de la cuenta origen.
        $itf = $ownAccountItfFree ? 0.0 : $this->fees->itf($amount);

        $net = round($destinationAmount - $toFee['total'], 2);

        return [
            'monto' => round($amount, 2),
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'tipo_cambio' => $rate !== null ? round((float) $rate, 4) : null,
            'monto_destino' => $destinationAmount,
            'comision_origen' => round($fromFee['comision'], 2),
            // Se mantiene en 0: la comisión de transferencia no causa IGV.
            'igv_origen' => 0.0,
            'comision_destino' => round($toFee['comision'], 2),
            'igv_destino' => 0.0,
            'gastos_origen' => round($fromFee['total'] + $itf, 2),
            'gastos_destino' => round($toFee['total'], 2),
            'itf' => round($itf, 2),
            'neto_recibido' => max(0, $net),
            'conversion_faltante' => $conversionMissing,
        ];
    }

    /**
     * Registra la transferencia: guarda el perfil del par y crea los dos
     * movimientos. El descuento del origen y el abono del destino ocurren en
     * la misma transacción de base de datos.
     *
     * @param  array{from_fixed_fee: float, from_percent_fee: float, to_fixed_fee: float, to_percent_fee: float, own_account_itf_free: bool, charge_origin_fee: bool, exchange_rate?: float|null}  $data
     */
    public function transfer(
        TreasuryAccount $from,
        TreasuryAccount $to,
        float $amount,
        string $date,
        array $data,
        ?string $reference = null,
        ?int $userId = null,
    ): TreasuryTransfer {
        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('La cuenta origen y destino no pueden ser la misma.');
        }

        // La cuenta de detracción solo paga IGV o libera saldo con contraseña.
        if ($from->isDetraction() || $to->isDetraction()) {
            throw new \InvalidArgumentException('La cuenta de detracción no participa en transferencias. Usa «Liberar a cuenta» en su pantalla.');
        }

        $fromCurrency = $this->currency->accountCurrency($from);
        $toCurrency = $this->currency->accountCurrency($to);
        $dateValue = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Periodo cerrado: la transferencia tampoco entra.
        app(TreasuryPeriodService::class)->ensureDateIsOpen($dateValue->toDateString());

        if (! config('treasury.allow_future_dates', false) && $dateValue->isAfter(now()->startOfDay())) {
            throw new TreasuryOperationException('No se permiten transferencias con fecha futura.');
        }

        $rate = null;

        if ($fromCurrency !== $toCurrency) {
            $rate = isset($data['exchange_rate']) && (float) $data['exchange_rate'] > 0
                ? (float) $data['exchange_rate']
                : $this->currency->requireRate($fromCurrency, $toCurrency, $dateValue->toDateString());
        }

        $chargeOriginFee = (bool) ($data['charge_origin_fee'] ?? false);
        $preview = $this->preview(
            $amount,
            (float) $data['from_fixed_fee'],
            (float) $data['from_percent_fee'],
            (float) $data['to_fixed_fee'],
            (float) $data['to_percent_fee'],
            (bool) $data['own_account_itf_free'],
            $chargeOriginFee,
            $fromCurrency,
            $toCurrency,
            $rate,
        );

        // Lo que sale de la cuenta origen: monto + gastos del origen (comisión + ITF).
        $totalOut = round($amount + $preview['gastos_origen'], 2);

        if ($from->type !== TreasuryAccount::TYPE_CASH && config('treasury.block_negative_balance', true)) {
            $available = app(TreasuryLedgerService::class)->accountBalance($from, $dateValue->toDateString());

            if (round($available - $totalOut, 2) < 0) {
                throw TreasuryOperationException::negativeBalance(
                    $from->displayName(),
                    round($available - $totalOut, 2)
                );
            }
        }

        return DB::transaction(function () use ($from, $to, $amount, $dateValue, $data, $reference, $userId, $preview, $totalOut) {
            // Se guarda la elección del par para proponerla en la siguiente transferencia
            TreasuryTransferProfile::updateOrCreate(
                ['from_account_id' => $from->id, 'to_account_id' => $to->id],
                [
                    'from_fixed_fee' => $data['from_fixed_fee'],
                    'from_percent_fee' => $data['from_percent_fee'],
                    'to_fixed_fee' => $data['to_fixed_fee'],
                    'to_percent_fee' => $data['to_percent_fee'],
                    'own_account_itf_free' => (bool) $data['own_account_itf_free'],
                ]
            );

            $transfer = TreasuryTransfer::create([
                'from_account_id' => $from->id,
                'to_account_id' => $to->id,
                'transfer_date' => $dateValue->toDateString(),
                'amount' => $amount,
                'from_currency' => $preview['from_currency'],
                'to_currency' => $preview['to_currency'],
                'exchange_rate' => $preview['tipo_cambio'],
                'amount_destination' => $preview['neto_recibido'],
                'from_fixed_fee' => $data['charge_origin_fee'] ? $data['from_fixed_fee'] : 0,
                'from_percent_fee' => $data['charge_origin_fee'] ? $data['from_percent_fee'] : 0,
                'to_fixed_fee' => $data['to_fixed_fee'],
                'to_percent_fee' => $data['to_percent_fee'],
                'from_fee_amount' => $preview['comision_origen'],
                'from_fee_igv' => $preview['igv_origen'],
                'to_fee_amount' => $preview['comision_destino'],
                'to_fee_igv' => $preview['igv_destino'],
                'itf_applies' => ! $data['own_account_itf_free'],
                'itf_amount' => $preview['itf'],
                'net_received' => $preview['neto_recibido'],
                'reference' => $reference,
                'user_id' => $userId,
            ]);

            $amountBaseOut = $preview['from_currency'] === 'PEN'
                ? $totalOut
                : $this->currency->toBase($totalOut, $preview['from_currency'], $dateValue->toDateString());

            $ledger = app(TreasuryLedgerService::class);
            $feeCategoryId = $ledger->systemCategoryId('Comisión/mantenimiento bancario');
            $itfCategoryId = $ledger->systemCategoryId('ITF');

            // Salida desde el origen: solo el monto transferido. Los gastos van aparte.
            TreasuryTransaction::create([
                'treasury_account_id' => $from->id,
                'transaction_date' => $dateValue->toDateString(),
                // La hora de la transferencia acompaña a la fecha en el libro.
                'transaction_time' => $dateValue->isToday() ? now()->format('H:i:s') : null,
                'type' => TreasuryTransaction::TYPE_EXPENSE,
                'treasury_category_id' => null,
                'origin_type' => 'treasury_transfer',
                'origin_id' => $transfer->id,
                'transfer_id' => $transfer->id,
                'amount' => round($amount, 2),
                'currency' => $preview['from_currency'],
                'exchange_rate' => $preview['tipo_cambio'],
                'amount_base' => $preview['from_currency'] === 'PEN'
                    ? round($amount, 2)
                    : $this->currency->toBase($amount, $preview['from_currency'], $dateValue->toDateString()),
                'reference' => $reference,
                'description' => 'Transferencia a ' . $to->displayName(),
                'source' => TreasuryTransaction::SOURCE_MANUAL,
                'user_id' => $userId,
            ]);

            // Comisión del origen: un egreso propio del gasto, sin IGV.
            if ($preview['gastos_origen'] - $preview['itf'] > 0) {
                $feeTotal = round($preview['comision_origen'], 2);

                TreasuryTransaction::create([
                    'treasury_account_id' => $from->id,
                    'transaction_date' => $dateValue->toDateString(),
                    // La hora de la transferencia acompaña a la fecha en el libro.
                    'transaction_time' => $dateValue->isToday() ? now()->format('H:i:s') : null,
                    'type' => TreasuryTransaction::TYPE_EXPENSE,
                    'treasury_category_id' => $feeCategoryId,
                    'origin_type' => 'treasury_transfer_fee',
                    'origin_id' => $transfer->id,
                    'transfer_id' => $transfer->id,
                    'amount' => $feeTotal,
                    'currency' => $preview['from_currency'],
                    'exchange_rate' => $preview['tipo_cambio'],
                    'amount_base' => $preview['from_currency'] === 'PEN'
                        ? $feeTotal
                        : $this->currency->toBase($feeTotal, $preview['from_currency'], $dateValue->toDateString()),
                    'fee_amount' => $preview['comision_origen'],
                    'igv_amount' => 0,
                    'reference' => $reference,
                    'description' => 'Comisión de transferencia a ' . $to->displayName(),
                    'source' => TreasuryTransaction::SOURCE_MANUAL,
                    'user_id' => $userId,
                ]);
            }

            // ITF: gasto tributario del origen, con su propia categoría.
            if ($preview['itf'] > 0) {
                TreasuryTransaction::create([
                    'treasury_account_id' => $from->id,
                    'transaction_date' => $dateValue->toDateString(),
                    // La hora de la transferencia acompaña a la fecha en el libro.
                    'transaction_time' => $dateValue->isToday() ? now()->format('H:i:s') : null,
                    'type' => TreasuryTransaction::TYPE_EXPENSE,
                    'treasury_category_id' => $itfCategoryId,
                    'origin_type' => 'treasury_transfer_itf',
                    'origin_id' => $transfer->id,
                    'transfer_id' => $transfer->id,
                    'amount' => $preview['itf'],
                    'currency' => $preview['from_currency'],
                    'exchange_rate' => $preview['tipo_cambio'],
                    'amount_base' => $preview['from_currency'] === 'PEN'
                        ? $preview['itf']
                        : $this->currency->toBase($preview['itf'], $preview['from_currency'], $dateValue->toDateString()),
                    'itf_amount' => $preview['itf'],
                    'reference' => $reference,
                    'description' => 'ITF de transferencia a ' . $to->displayName(),
                    'source' => TreasuryTransaction::SOURCE_MANUAL,
                    'user_id' => $userId,
                ]);
            }

            $amountBaseIn = $preview['to_currency'] === 'PEN'
                ? $preview['monto_destino']
                : $this->currency->toBase($preview['monto_destino'], $preview['to_currency'], $dateValue->toDateString());

            // Entrada en el destino por el monto convertido completo.
            TreasuryTransaction::create([
                'treasury_account_id' => $to->id,
                'transaction_date' => $dateValue->toDateString(),
                // La hora de la transferencia acompaña a la fecha en el libro.
                'transaction_time' => $dateValue->isToday() ? now()->format('H:i:s') : null,
                'type' => TreasuryTransaction::TYPE_INCOME,
                'origin_type' => 'treasury_transfer',
                'origin_id' => $transfer->id,
                'transfer_id' => $transfer->id,
                'amount' => $preview['monto_destino'],
                'currency' => $preview['to_currency'],
                'exchange_rate' => $preview['tipo_cambio'],
                'amount_base' => $amountBaseIn,
                'reference' => $reference,
                'description' => 'Transferencia desde ' . $from->displayName(),
                'source' => TreasuryTransaction::SOURCE_MANUAL,
                'user_id' => $userId,
            ]);

            // Comisión del destino: gasto separado que explica el neto recibido.
            if ($preview['gastos_destino'] > 0) {
                TreasuryTransaction::create([
                    'treasury_account_id' => $to->id,
                    'transaction_date' => $dateValue->toDateString(),
                    // La hora de la transferencia acompaña a la fecha en el libro.
                    'transaction_time' => $dateValue->isToday() ? now()->format('H:i:s') : null,
                    'type' => TreasuryTransaction::TYPE_EXPENSE,
                    'treasury_category_id' => $feeCategoryId,
                    'origin_type' => 'treasury_transfer_fee',
                    'origin_id' => $transfer->id,
                    'transfer_id' => $transfer->id,
                    'amount' => $preview['gastos_destino'],
                    'currency' => $preview['to_currency'],
                    'exchange_rate' => $preview['tipo_cambio'],
                    'amount_base' => $preview['to_currency'] === 'PEN'
                        ? $preview['gastos_destino']
                        : $this->currency->toBase($preview['gastos_destino'], $preview['to_currency'], $dateValue->toDateString()),
                    'fee_amount' => $preview['comision_destino'],
                    'igv_amount' => 0,
                    'reference' => $reference,
                    'description' => 'Comisión de recepción desde ' . $from->displayName(),
                    'source' => TreasuryTransaction::SOURCE_MANUAL,
                    'user_id' => $userId,
                ]);
            }

            $this->audit->log('transfer', $transfer->id, 'created', [], [
                'from' => $from->displayName(),
                'to' => $to->displayName(),
                'amount' => $amount,
                'from_currency' => $preview['from_currency'],
                'to_currency' => $preview['to_currency'],
                'destination_amount' => $preview['neto_recibido'],
            ]);

            return $transfer;
        });
    }

    /**
     * Anula una transferencia completa: los dos movimientos y la cabecera.
     * No se borra nada; si alguna pata está conciliada, no se permite.
     */
    public function void(TreasuryTransfer $transfer, ?int $userId = null): bool
    {
        if ($transfer->voided_at) {
            return true;
        }

        return DB::transaction(function () use ($transfer, $userId) {
            $transactions = TreasuryTransaction::query()
                ->where('transfer_id', $transfer->id)
                ->whereNull('voided_at')
                ->get();

            foreach ($transactions as $transaction) {
                if ($transaction->isLocked()) {
                    throw TreasuryOperationException::reconciled();
                }

                $transaction->void($userId);
            }

            $transfer->voided_at = now();
            $transfer->voided_by = $userId;
            $transfer->save();

            $this->audit->log('transfer', $transfer->id, 'voided', [], [
                'transactions' => $transactions->pluck('id')->all(),
            ]);

            return true;
        });
    }
}
