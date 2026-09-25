<?php

namespace Modules\Sales\Services;

use App\Models\Parameter;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Entities\SaleExchangeRate;

/**
 * Servicio central de tipo de cambio SUNAT.
 *
 * - La consulta al proveedor (Migo) se hace 1 vez al dia por el comando
 *   programado `sales:fetch-exchange-rate`; el valor queda activo todo el dia.
 * - El boton del header "Cambio de moneda" puede re-consultar manualmente
 *   (permiso invo_tipo_cambio) usando el mismo metodo fetchAndStore().
 * - getCurrentRate() jamas bloquea una venta: si no hay TC del dia usa el
 *   ultimo conocido y lo marca como desactualizado (is_stale = true).
 * - El interruptor PTM0004 define si el sistema trabaja con multiples monedas;
 *   desactivado (default) todo opera en soles.
 */
class ExchangeRateService
{
    /** Codigo de parametro del interruptor multi-moneda (tabla parameters). */
    public const MULTI_CURRENCY_PARAMETER = 'PTM0004';

    /** Codigo de parametro del token de Migo (ya usado por ApisnetPeController). */
    public const MIGO_TOKEN_PARAMETER = 'P000023';

    public const BASE_MIGO = 'https://api.migo.pe/api';

    public const SOURCE_MIGO = 'migo';
    public const SOURCE_MANUAL = 'manual';

    /** Moneda base del sistema (todos los precios de productos se guardan en soles). */
    public const BASE_CURRENCY = 'PEN';

    /**
     * Indica si el sistema permite vender en moneda extranjera (PTM0004 activo).
     */
    public function isMultiCurrencyEnabled(): bool
    {
        return (string) Parameter::where('parameter_code', self::MULTI_CURRENCY_PARAMETER)->value('value_default') === '1';
    }

    /**
     * Lista de monedas habilitadas para vender ademas de la base (PEN).
     * Diseno abierto: al agregar otra moneda a la BD basta con sumarla aqui.
     */
    public function getEnabledCurrencies(): array
    {
        $currencies = [[
            'code' => self::BASE_CURRENCY,
            'symbol' => 'S/',
            'label' => 'Soles',
            'base' => true,
        ]];

        if ($this->isMultiCurrencyEnabled()) {
            $usd = $this->getCurrentRate('USD');

            $currencies[] = [
                'code' => 'USD',
                'symbol' => 'US$',
                'label' => 'Dólares Americanos',
                'base' => false,
                'exchange_rate' => $usd ? (float) $usd['rate'] : null,
                'rate_date' => $usd['date'] ?? null,
                'is_stale' => $usd['is_stale'] ?? false,
            ];
        }

        return $currencies;
    }

    /**
     * Consulta el tipo de cambio a Migo (SUNAT) y lo guarda en la tabla.
     *
     * @param  string|null  $date  Fecha Y-m-d; null usa el endpoint /ultimo.
     * @param  User|int|null  $user  Usuario que origino la consulta (para el boton manual).
     * @param  string  $source  migo|manual
     * @return array{success: bool, message: string, data?: array}
     */
    public function fetchAndStore(?string $date = null, $user = null, string $source = self::SOURCE_MIGO): array
    {
        $token = Parameter::where('parameter_code', self::MIGO_TOKEN_PARAMETER)->value('value_default');

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'El token de Migo no está configurado (parámetro P000023). Configúralo en Parámetros del sistema.',
            ];
        }

        $endpoint = $date ? '/v2/tipo-cambio/sunat?fecha='.$date : '/v2/tipo-cambio/sunat/ultimo';

        $client = new Client([
            'base_uri' => self::BASE_MIGO,
            'timeout' => 15,
        ]);

        try {
            $response = $client->get($endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $body['message'] ?? 'Error de la API de Migo (HTTP '.$e->getResponse()->getStatusCode().')';

            Log::warning('ExchangeRateService: Migo rechazo la consulta de tipo de cambio', ['error' => $message]);

            return ['success' => false, 'message' => 'Migo respondió: '.$message];
        } catch (\Exception $e) {
            Log::error('ExchangeRateService: no se pudo contactar a Migo', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'No se pudo conectar con el servicio de tipo de cambio (Migo): '.$e->getMessage()];
        }

        if (empty($data['success']) || empty($data['fecha'])) {
            return [
                'success' => false,
                'message' => 'La respuesta de Migo no contiene un tipo de cambio válido: '.json_encode($data, JSON_UNESCAPED_UNICODE),
            ];
        }

        $rate = SaleExchangeRate::updateOrCreate(
            [
                'currency_code' => $data['moneda'] ?? 'USD',
                'rate_date' => Carbon::parse($data['fecha'])->format('Y-m-d'),
            ],
            [
                'purchase_rate' => (float) $data['precio_compra'],
                'sale_rate' => (float) $data['precio_venta'],
                'source' => $source,
                'fetched_by' => $user instanceof User ? $user->id : $user,
            ]
        );

        return [
            'success' => true,
            'message' => 'Tipo de cambio actualizado correctamente.',
            'data' => $rate->toArray(),
        ];
    }

    /**
     * Devuelve el TC vigente de una moneda: el del dia, o el ultimo conocido
     * marcado como desactualizado. Nunca devuelve null sin informacion si
     * existe historial (para no bloquear ventas).
     *
     * @return array{rate: string, date: string, purchase: string, sale: string, is_stale: bool, source: string}|null
     */
    public function getCurrentRate(string $currencyCode = 'USD'): ?array
    {
        $today = Carbon::today()->format('Y-m-d');

        $rate = SaleExchangeRate::where('currency_code', $currencyCode)
            ->orderByDesc('rate_date')
            ->first();

        if (! $rate) {
            return null;
        }

        $rateDate = Carbon::parse($rate->rate_date);

        return [
            'rate' => $rate->sale_rate,
            'purchase' => $rate->purchase_rate,
            'sale' => $rate->sale_rate,
            'date' => $rateDate->format('Y-m-d'),
            'is_stale' => $rateDate->toDateString() !== $today,
            'source' => $rate->source,
        ];
    }

    /**
     * Convierte un monto en soles a la moneda destino usando el TC vigente.
     * Si la moneda es la base (PEN) devuelve el monto sin cambio.
     */
    public function convertFromBase(float $amountInSoles, string $currencyCode): float
    {
        if ($currencyCode === self::BASE_CURRENCY) {
            return $amountInSoles;
        }

        $rate = $this->getCurrentRate($currencyCode);

        if (! $rate || (float) $rate['rate'] <= 0) {
            throw new \RuntimeException("No hay tipo de cambio vigente para {$currencyCode}. Consulta el tipo de cambio desde el botón del header.");
        }

        return $amountInSoles / (float) $rate['rate'];
    }

    /**
     * Convierte un monto de la moneda destino a soles (para validaciones en
     * soles como el umbral de detraccion, o para registrar ingresos en caja).
     */
    public function convertToBase(float $amountInCurrency, string $currencyCode, ?float $rate = null): float
    {
        if ($currencyCode === self::BASE_CURRENCY) {
            return $amountInCurrency;
        }

        $exchangeRate = $rate ?? (float) ($this->getCurrentRate($currencyCode)['rate'] ?? 0);

        if ($exchangeRate <= 0) {
            throw new \RuntimeException("No hay tipo de cambio vigente para {$currencyCode}.");
        }

        return $amountInCurrency * $exchangeRate;
    }
}
