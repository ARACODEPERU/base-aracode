<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Entities\TreasuryTransfer;
use Modules\Treasury\Entities\TreasuryWalletTransfer;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Traslado del dinero de una billetera digital a su banco vinculado.
 *
 * Si la billetera está configurada como inmediata, el ingreso se registra
 * directamente en el banco (lo hace la integración). Si tarda N días hábiles,
 * el ingreso queda en la cuenta de la billetera y aquí se encola la
 * transferencia; el comando treasury:run-wallet-transfers la ejecuta en la
 * fecha acordada.
 */
class TreasuryWalletTransferService
{
    /**
     * Encola el traslado de un ingreso recibido en la billetera.
     */
    public function schedule(
        TreasurySourceMovement $bridge,
        TreasuryAccount $wallet,
        TreasuryAccount $bank,
        float $amount,
    ): ?TreasuryWalletTransfer {
        $company = $wallet->companyBilletera;

        $days = (int) ($company?->bank_transfer_days ?? 0);

        if (! $company || $days <= 0 || $amount <= 0) {
            return null;
        }

        $date = $bridge->movement_date
            ? Carbon::parse($bridge->movement_date)
            : now();

        return TreasuryWalletTransfer::firstOrCreate(
            ['source_bridge_id' => $bridge->id],
            [
                'company_billetera_id' => $company->id,
                'wallet_account_id' => $wallet->id,
                'bank_account_id' => $bank->id,
                'amount' => round($amount, 2),
                'currency' => strtoupper((string) ($wallet->currency_type_id ?: 'PEN')) ?: 'PEN',
                'scheduled_date' => $this->addBusinessDays($date, $days)->toDateString(),
                'status' => TreasuryWalletTransfer::STATUS_PENDING,
                'reference' => $bridge->reference,
                'user_id' => $bridge->user_id,
            ]
        );
    }

    /**
     * Suma días hábiles (sin sábados ni domingos). No contempla feriados.
     */
    public function addBusinessDays(Carbon|string $date, int $days): Carbon
    {
        $result = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date))->startOfDay();
        $added = 0;

        while ($added < max(0, $days)) {
            $result->addDay();

            if (! $result->isWeekend()) {
                $added++;
            }
        }

        return $result;
    }

    /**
     * Cola de traslados pendientes de billetera a banco.
     *
     * La usan el Dashboard y la Previsión para mostrar el dinero que ya está
     * cobrado en la billetera y todavía no ha llegado al banco. No altera
     * saldos: el importe ya está contado en la cuenta de la billetera.
     *
     * @return array{count: int, total: float, rows: array<int, array<string, mixed>>, groups: array<int, array<string, mixed>>, next_date: ?string}
     */
    public function pending(
        ?string $currency = null,
        ?string $from = null,
        ?string $until = null,
        int $limit = 50,
    ): array {
        $items = TreasuryWalletTransfer::query()
            ->with(['walletAccount.companyBilletera.billetera', 'bankAccount.bankAccount.bank'])
            ->where('status', TreasuryWalletTransfer::STATUS_PENDING)
            ->when($currency, fn ($query) => $query->where('currency', strtoupper($currency)))
            ->when($from, fn ($query) => $query->whereDate('scheduled_date', '>=', $from))
            ->when($until, fn ($query) => $query->whereDate('scheduled_date', '<=', $until))
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        $rows = $items->map(fn (TreasuryWalletTransfer $item) => [
            'id' => $item->id,
            'wallet' => $item->walletAccount?->displayName(),
            'bank' => $item->bankAccount?->displayName(),
            'amount' => round((float) $item->amount, 2),
            'currency' => strtoupper((string) ($item->currency ?: 'PEN')) ?: 'PEN',
            'scheduled_date' => $item->scheduled_date?->toDateString(),
            'days' => $item->scheduled_date ? (int) today()->diffInDays($item->scheduled_date, false) : null,
            'overdue' => $item->scheduled_date ? $item->scheduled_date->lt(today()) : false,
            'reference' => $item->reference,
        ])->values()->all();

        $groups = $items
            ->groupBy(fn (TreasuryWalletTransfer $item) => strtoupper((string) ($item->currency ?: 'PEN')) ?: 'PEN')
            ->map(fn ($rows, $code) => [
                'currency' => $code,
                'count' => $rows->count(),
                'total' => round((float) $rows->sum('amount'), 2),
            ])
            ->values()
            ->all();

        return [
            'count' => $items->count(),
            'total' => round((float) $items->sum('amount'), 2),
            'rows' => $rows,
            'groups' => $groups,
            'next_date' => $items->first()?->scheduled_date?->toDateString(),
        ];
    }

    /**
     * Ejecuta las transferencias cuya fecha hábil ya llegó.
     *
     * @return array{processed: int, failed: int}
     */
    public function runDue(int $limit = 200): array
    {
        $pendientes = TreasuryWalletTransfer::query()
            ->where('status', TreasuryWalletTransfer::STATUS_PENDING)
            ->whereDate('scheduled_date', '<=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $result = ['processed' => 0, 'failed' => 0];

        foreach ($pendientes as $item) {
            try {
                $this->execute($item);
                $result['processed']++;
            } catch (\Throwable $e) {
                $item->forceFill([
                    'status' => TreasuryWalletTransfer::STATUS_FAILED,
                    'error' => mb_substr($e->getMessage(), 0, 1000),
                ])->save();

                Log::warning('[Treasury] No se pudo transferir el saldo de la billetera al banco', [
                    'wallet_transfer_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);

                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Crea la transferencia real billetera -> banco y cierra la fila de cola.
     */
    public function execute(TreasuryWalletTransfer $item): TreasuryTransfer
    {
        if ($item->status !== TreasuryWalletTransfer::STATUS_PENDING) {
            throw new TreasuryOperationException('Esta transferencia ya fue procesada.');
        }

        $wallet = TreasuryAccount::query()->active()->find($item->wallet_account_id);
        $bank = TreasuryAccount::query()->active()->find($item->bank_account_id);

        if (! $wallet || ! $bank) {
            throw new TreasuryOperationException('La cuenta de la billetera o del banco ya no está activa.');
        }

        // Se registra con la fecha real de ejecución: si el cron no corrió el
        // día programado, el traslado se hace hoy y el saldo ya está disponible.
        $transfer = app(TreasuryTransferService::class)->transfer(
            $wallet,
            $bank,
            (float) $item->amount,
            now()->toDateString(),
            [
                'from_fixed_fee' => 0,
                'from_percent_fee' => 0,
                'to_fixed_fee' => 0,
                'to_percent_fee' => 0,
                'own_account_itf_free' => true,
                'charge_origin_fee' => false,
            ],
            $item->reference ?: 'Traslado de billetera a banco',
            $item->user_id,
        );

        $item->forceFill([
            'status' => TreasuryWalletTransfer::STATUS_PROCESSED,
            'treasury_transfer_id' => $transfer->id,
            'executed_at' => now(),
            'error' => null,
        ])->save();

        return $transfer;
    }
}
