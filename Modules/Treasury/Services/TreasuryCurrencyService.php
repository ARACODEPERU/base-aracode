<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Modules\Sales\Services\ExchangeRateService;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Regla de moneda del módulo:
 *
 *  - Todo registro sin moneda explícita es PEN (decisión del proyecto).
 *  - La moneda de una cuenta sale de `currency_type_id`; si falta, PEN.
 *  - Nunca se suma dinero de monedas distintas sin etiqueta: para consolidar
 *    se convierte a soles con el tipo de cambio SUNAT y se informa la fecha.
 */
class TreasuryCurrencyService
{
    public function base(): string
    {
        return (string) (config('treasury.base_currency') ?: 'PEN');
    }

    public function normalize(?string $currency): string
    {
        $value = strtoupper(trim((string) $currency));

        return $value !== '' ? $value : $this->base();
    }

    public function accountCurrency(?TreasuryAccount $account): string
    {
        return $this->normalize($account?->currency_type_id);
    }

    /**
     * Tipo de cambio vigente para una fecha (SUNAT vía el servicio de Ventas).
     *
     * @return array{rate: float, date: ?string, is_stale: bool, source: ?string}|null
     */
    public function rate(string $currency, ?string $date = null): ?array
    {
        $currency = $this->normalize($currency);

        if ($currency === $this->base()) {
            return ['rate' => 1.0, 'date' => $date ?: now()->toDateString(), 'is_stale' => false, 'source' => 'base'];
        }

        try {
            $service = app(ExchangeRateService::class);
            $data = $date
                ? $service->getCurrentRateForDate($currency, $date)
                : $service->getCurrentRate($currency);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $data || (float) ($data['rate'] ?? 0) <= 0) {
            return null;
        }

        return [
            'rate' => (float) $data['rate'],
            'date' => $data['date'] ?? null,
            'is_stale' => (bool) ($data['is_stale'] ?? false),
            'source' => $data['source_label'] ?? null,
        ];
    }

    /**
     * Convierte un monto entre dos monedas usando el TC SUNAT.
     * Devuelve null si no hay tasa disponible.
     */
    public function convert(float $amount, string $from, string $to, ?string $date = null): ?float
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        if ($from === $to) {
            return round($amount, 2);
        }

        if ($from === $this->base()) {
            $rate = $this->rate($to, $date);

            return $rate && $rate['rate'] > 0 ? round($amount / $rate['rate'], 2) : null;
        }

        if ($to === $this->base()) {
            $rate = $this->rate($from, $date);

            return $rate && $rate['rate'] > 0 ? round($amount * $rate['rate'], 2) : null;
        }

        // Cruzada: se pasa por soles.
        $inBase = $this->convert($amount, $from, $this->base(), $date);

        return $inBase === null ? null : $this->convert($inBase, $this->base(), $to, $date);
    }

    /**
     * Exige una tasa utilizable para operar (transferencias entre monedas).
     */
    public function requireRate(string $from, string $to, ?string $date = null): float
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        if ($from === $to) {
            return 1.0;
        }

        $currency = $from === $this->base() ? $to : $from;
        $data = $this->rate($currency, $date);

        if (! $data) {
            throw TreasuryOperationException::missingRate($from, $to);
        }

        $maxAge = (int) config('treasury.max_rate_age_days', 3);

        if ($maxAge > 0 && $data['is_stale'] && $data['date']) {
            $age = Carbon::parse($data['date'])->diffInDays(now());

            if ($age > $maxAge) {
                throw TreasuryOperationException::staleRate($currency, $maxAge);
            }
        }

        return $data['rate'];
    }

    /**
     * Equivalente en soles. Si no hay TC, devuelve null (nunca inventa).
     */
    public function toBase(float $amount, string $currency, ?string $date = null): ?float
    {
        return $this->convert($amount, $currency, $this->base(), $date);
    }

    /**
     * Agrupa saldos por moneda y calcula el consolidado en soles.
     *
     * @param  array<int, array{account: TreasuryAccount, balance: float}>  $balances
     * @return array{groups: array<int, array<string, mixed>>, consolidated: float, rates: array<int, array<string, mixed>>, incomplete: bool}
     */
    public function consolidate(array $balances, ?string $until = null): array
    {
        $groups = [];
        $consolidated = 0.0;
        $rates = [];
        $incomplete = false;

        foreach ($balances as $row) {
            $account = $row['account'];
            $currency = $this->accountCurrency($account);
            $balance = round((float) $row['balance'], 2);

            if (! isset($groups[$currency])) {
                $groups[$currency] = [
                    'currency' => $currency,
                    'total' => 0.0,
                    'accounts' => 0,
                ];
            }

            $groups[$currency]['total'] = round($groups[$currency]['total'] + $balance, 2);
            $groups[$currency]['accounts']++;

            if ($currency === $this->base()) {
                $consolidated += $balance;
                continue;
            }

            $rate = $this->rate($currency, $until);
            $rates[$currency] = $rate + ['currency' => $currency];

            if (! $rate) {
                $incomplete = true;
                continue;
            }

            $consolidated += $balance * $rate['rate'];
        }

        return [
            'groups' => array_values($groups),
            'consolidated' => round($consolidated, 2),
            'rates' => array_values($rates),
            'incomplete' => $incomplete,
        ];
    }
}
