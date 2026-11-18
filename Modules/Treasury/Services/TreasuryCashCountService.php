<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryCashCount;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Arqueo y cierre de caja chica.
 *
 * El saldo esperado se calcula con la misma regla del panel de efectivo
 * (apertura + ventas + abonos - egresos de la sesión). El usuario cuenta los
 * billetes y monedas; la diferencia se registra, no se corrige sola. Cerrar la
 * sesión actualiza la caja chica con el dinero contado como saldo final.
 *
 * Nada se edita ni se borra: cada arqueo es un registro nuevo y auditado.
 */
class TreasuryCashCountService
{
    /** Billetes y monedas de curso legal en soles. */
    public const DENOMINATIONS = [200, 100, 50, 20, 10, 5, 2, 1, 0.5, 0.2, 0.1, 0.05];

    public function __construct(
        private TreasuryCashService $cash,
        private TreasuryAuditService $audit,
    ) {
    }

    /**
     * Sesión de caja con su saldo calculado (para el formulario de arqueo).
     *
     * @return array<string, mixed>|null
     */
    public function session(int $pettyCashId): ?array
    {
        return $this->cash->sessionDetail($pettyCashId);
    }

    /**
     * Registra el arqueo y, si se pide, cierra la sesión de caja.
     *
     * @param  array<string, mixed>  $denominations
     *
     * @throws TreasuryOperationException
     */
    public function record(
        int $pettyCashId,
        array $denominations,
        ?string $notes = null,
        bool $closeSession = false,
        ?int $userId = null,
    ): TreasuryCashCount {
        $session = $this->cash->sessionDetail($pettyCashId);

        if (! $session) {
            throw new TreasuryOperationException('La sesión de caja indicada no existe.');
        }

        $counted = $this->cleanDenominations($denominations);

        if ($counted === []) {
            throw new TreasuryOperationException('Registra al menos una denominación con cantidad mayor que cero.');
        }

        $countedAmount = round(array_sum(array_map(
            fn ($denomination, $quantity) => (float) $denomination * (int) $quantity,
            array_keys($counted),
            $counted
        )), 2);

        $expected = round((float) $session['balance'], 2);
        $difference = round($countedAmount - $expected, 2);
        $notes = trim((string) $notes);

        // Un faltante o sobrante debe quedar explicado: es el control del arqueo.
        if (abs($difference) >= 0.01 && mb_strlen($notes) < 5) {
            throw new TreasuryOperationException(
                'El arqueo tiene una diferencia de ' . number_format($difference, 2, '.', '')
                . ': escribe una nota que la explique.'
            );
        }

        $close = $closeSession && (bool) $session['is_open'];
        $cutoff = Carbon::today()->toDateString();

        $count = DB::transaction(function () use ($pettyCashId, $cutoff, $expected, $countedAmount, $difference, $counted, $notes, $close, $userId) {
            $count = TreasuryCashCount::create([
                'petty_cash_id' => $pettyCashId,
                'cutoff_date' => $cutoff,
                'expected_amount' => $expected,
                'counted_amount' => $countedAmount,
                'difference' => $difference,
                'denominations' => $counted,
                'notes' => $notes !== '' ? $notes : null,
                'closed_session' => $close,
                'created_by' => $userId,
            ]);

            if ($close) {
                // Se usa el query builder para no depender de los accessors del
                // modelo de caja chica (que formatean las fechas).
                DB::table('petty_cashes')->where('id', $pettyCashId)->update([
                    'state' => '0',
                    'date_closed' => $cutoff,
                    'time_closed' => now()->format('H:i:s'),
                    'final_balance' => $countedAmount,
                    'updated_at' => now(),
                ]);
            }

            return $count;
        });

        $this->audit->log('cash_count', $count->id, 'recorded', [], [
            'petty_cash_id' => $pettyCashId,
            'expected_amount' => $expected,
            'counted_amount' => $countedAmount,
            'difference' => $difference,
            'closed_session' => $close,
            'notes' => $notes ?: null,
        ], ['critical' => true]);

        return $count->load('creator');
    }

    /**
     * Últimos arqueos registrados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(?int $pettyCashId = null, int $limit = 40): array
    {
        return TreasuryCashCount::query()
            ->with(['creator', 'pettyCash'])
            ->when($pettyCashId, fn ($query) => $query->where('petty_cash_id', $pettyCashId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (TreasuryCashCount $count) => [
                'id' => $count->id,
                'petty_cash_id' => $count->petty_cash_id,
                'session' => $count->pettyCash
                    ? '#' . $count->pettyCash->id . ' · ' . ($count->pettyCash->seller_name ?: 'Caja')
                    : 'Caja #' . $count->petty_cash_id,
                'cutoff_date' => $count->cutoff_date?->format('d/m/Y'),
                'expected_amount' => (float) $count->expected_amount,
                'counted_amount' => (float) $count->counted_amount,
                'difference' => (float) $count->difference,
                'closed_session' => (bool) $count->closed_session,
                'notes' => $count->notes,
                'created_by' => $count->creator?->name,
                'created_at' => $count->created_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /**
     * Deja solo denominaciones válidas con cantidad entera positiva.
     *
     * @param  array<string, mixed>  $denominations
     * @return array<string, int>
     */
    private function cleanDenominations(array $denominations): array
    {
        $clean = [];

        foreach (self::DENOMINATIONS as $denomination) {
            $quantity = (int) ($denominations[(string) $denomination] ?? $denominations[$denomination] ?? 0);

            if ($quantity > 0) {
                $clean[(string) $denomination] = min($quantity, 100000);
            }
        }

        return $clean;
    }
}
