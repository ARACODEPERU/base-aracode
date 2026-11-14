<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Entities\TreasuryTransfer;
use Modules\Treasury\Entities\TreasuryTransferProfile;

/**
 * Transferencias entre cuentas de Tesorería.
 *
 * Reglas:
 * - La comisión del destino siempre se cobra (solo nos interesa cuando el dinero ingresa).
 * - La comisión del origen se cobra solo cuando la transferencia es entre cuentas propias.
 * - El ITF (0.005 % desde S/ 1000) se aplica salvo que el check
 *   "transferencia de mi propia cuenta libre de ITF" esté activo para ese par.
 * - La elección del check y las comisiones se guardan por par origen-destino.
 */
class TreasuryTransferService
{
    public function __construct(private FeeCalculator $fees)
    {
    }

    /**
     * Calcula la transferencia sin guardarla (para mostrar el desglose en pantalla).
     *
     * @return array<string, float|bool>
     */
    public function preview(
        float $amount,
        float $fromFixedFee,
        float $fromPercentFee,
        float $toFixedFee,
        float $toPercentFee,
        bool $ownAccountItfFree,
        bool $chargeOriginFee,
    ): array {
        $fromFee = $chargeOriginFee
            ? $this->fees->commission($amount, $fromFixedFee, $fromPercentFee, false)['total']
            : 0.0;
        $toFee = $this->fees->commission($amount, $toFixedFee, $toPercentFee, false)['total'];

        // ITF: se calcula sobre el monto transferido; el check lo exonera solo entre cuentas propias
        $itf = $ownAccountItfFree ? 0.0 : $this->fees->itf($amount);

        $net = round($amount - $fromFee - $itf - $toFee, 2);

        return [
            'monto' => round($amount, 2),
            'comision_origen' => round($fromFee, 2),
            'comision_destino' => round($toFee, 2),
            'itf' => round($itf, 2),
            'neto_recibido' => max(0, $net),
        ];
    }

    /**
     * Registra la transferencia: guarda el perfil del par y crea los dos movimientos.
     *
     * @param  array{from_fixed_fee: float, from_percent_fee: float, to_fixed_fee: float, to_percent_fee: float, own_account_itf_free: bool, charge_origin_fee: bool} $data
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

        // Comisión del origen solo entre cuentas propias: aquí todas las cuentas son del mismo titular
        $chargeOriginFee = (bool) ($data['charge_origin_fee'] ?? false);
        $preview = $this->preview(
            $amount,
            (float) $data['from_fixed_fee'],
            (float) $data['from_percent_fee'],
            (float) $data['to_fixed_fee'],
            (float) $data['to_percent_fee'],
            (bool) $data['own_account_itf_free'],
            $chargeOriginFee,
        );

        return DB::transaction(function () use ($from, $to, $amount, $date, $data, $reference, $userId, $preview) {
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
                'transfer_date' => $date,
                'amount' => $amount,
                'from_fixed_fee' => $data['charge_origin_fee'] ? $data['from_fixed_fee'] : 0,
                'from_percent_fee' => $data['charge_origin_fee'] ? $data['from_percent_fee'] : 0,
                'to_fixed_fee' => $data['to_fixed_fee'],
                'to_percent_fee' => $data['to_percent_fee'],
                'itf_applies' => ! $data['own_account_itf_free'],
                'itf_amount' => $preview['itf'],
                'net_received' => $preview['neto_recibido'],
                'reference' => $reference,
                'user_id' => $userId,
            ]);

            // Salida desde el origen: monto + comisión de origen + ITF
            TreasuryTransaction::create([
                'treasury_account_id' => $from->id,
                'transaction_date' => $date,
                'type' => TreasuryTransaction::TYPE_EXPENSE,
                'origin_type' => 'treasury_transfer',
                'origin_id' => $transfer->id,
                'transfer_id' => $transfer->id,
                'amount' => round($amount + $preview['comision_origen'] + $preview['itf'], 2),
                'fee_amount' => $preview['comision_origen'],
                'itf_amount' => $preview['itf'],
                'reference' => $reference,
                'description' => 'Transferencia a ' . $to->displayName(),
                'source' => TreasuryTransaction::SOURCE_MANUAL,
                'user_id' => $userId,
            ]);

            // Entrada en el destino: monto menos comisión de destino
            TreasuryTransaction::create([
                'treasury_account_id' => $to->id,
                'transaction_date' => $date,
                'type' => TreasuryTransaction::TYPE_INCOME,
                'origin_type' => 'treasury_transfer',
                'origin_id' => $transfer->id,
                'transfer_id' => $transfer->id,
                'amount' => $preview['neto_recibido'],
                'fee_amount' => $preview['comision_destino'],
                'reference' => $reference,
                'description' => 'Transferencia desde ' . $from->displayName(),
                'source' => TreasuryTransaction::SOURCE_MANUAL,
                'user_id' => $userId,
            ]);

            return $transfer;
        });
    }
}
