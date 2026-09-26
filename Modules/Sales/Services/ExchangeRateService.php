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

    /** Codigo de parametro del token de decolecta.com (RENIEC/SUNAT, P000012). */
    public const DECOLECTA_TOKEN_PARAMETER = 'P000012';

    public const BASE_MIGO = 'https://api.migo.pe/api';
    public const BASE_DECOLECTA = 'https://api.decolecta.com';

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
     * Si Migo no responde, rechaza el token ("Unauthenticated.") o devuelve
     * una respuesta invalida, se reintenta automaticamente con decolecta.com
     * (token P000012) informandolo en el message de la respuesta.
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
            // Sin token de Migo configurado: intentar directamente con decolecta.
            return $this->fetchFromDecolectaAndStore($date, $user);
        }

        $endpoint = $date ? '/v2/tipo-cambio/sunat?fecha='.$date : '/v2/tipo-cambio/sunat/ultimo';

        // URL completa: Guzzle con base_uri sin slash final y ruta absoluta
        // descarta el segmento /api del host (RFC 3986), dejando de golpear
        // https://api.migo.pe/api/v2/... y produciendo 404 "Recurso no encontrado".
        $client = new Client(['timeout' => 15]);

        try {
            $response = $client->get(self::BASE_MIGO.$endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $body['message'] ?? 'Error de la API de Migo (HTTP '.$e->getResponse()->getStatusCode().')';

            Log::warning('ExchangeRateService: Migo rechazo la consulta de tipo de cambio, se intenta con decolecta.com', ['error' => $message]);

            return $this->fetchFromDecolectaAndStore($date, $user, 'primera consulta no se encontro cambios en MIGO.PE');
        } catch (\Exception $e) {
            Log::error('ExchangeRateService: no se pudo contactar a Migo, se intenta con decolecta.com', ['error' => $e->getMessage()]);

            return $this->fetchFromDecolectaAndStore($date, $user, 'primera consulta no se encontro cambios en MIGO.PE');
        }

        // Respuesta invalida de Migo (token rechazado devuelve "Unauthenticated."):
        // usar la alternativa decolecta.com.
        if (empty($data['success']) || empty($data['fecha'])) {
            Log::warning('ExchangeRateService: respuesta de Migo invalida, se intenta con decolecta.com', ['respuesta' => $data]);

            return $this->fetchFromDecolectaAndStore($date, $user, 'primera consulta no se encontro cambios en MIGO.PE');
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
            'message' => 'Tipo de cambio actualizado correctamente. Fuente: '.self::friendlySource($source),
            'data' => $rate->toArray(),
            'source_label' => self::friendlySource($source),
        ];
    }

    /**
     * Consulta el tipo de cambio SUNAT a decolecta.com (alternativa cuando Migo
     * no responde o rechaza el token) y lo guarda en la tabla.
     *
     * curl -H 'Accept: application/json' -H "Authorization: Bearer $TOKEN" \
     *   https://api.decolecta.com/v1/tipo-cambio/sunat?date=2024-03-18
     *
     * @param  string|null  $date  Fecha Y-m-d; null usa el dia actual.
     * @param  mixed  $user  Usuario que origino la consulta (para el boton manual).
     * @param  string  $notaInicial  Prefijo informativo cuando la falla de Migo ya se detecto antes.
     * @return array{success: bool, message: string, data?: array}
     */
    public function fetchFromDecolectaAndStore(?string $date = null, $user = null, string $notaInicial = ''): array
    {
        $prefix = $notaInicial !== '' ? $notaInicial.', ' : '';

        $token = Parameter::where('parameter_code', self::DECOLECTA_TOKEN_PARAMETER)->value('value_default');

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'No hay alternativa disponible: falta el token de decolecta.com (parámetro P000012) y Migo no respondió.',
            ];
        }

        $endpoint = $date ? '/v1/tipo-cambio/sunat?date='.$date : '/v1/tipo-cambio/sunat';

        // URL completa por la misma razon que en fetchAndStore(): evitar la
        // union base_uri+ruta de Guzzle.
        $client = new Client(['timeout' => 15]);

        try {
            $response = $client->get(self::BASE_DECOLECTA.$endpoint, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            $message = $body['message'] ?? 'Error de la API de decolecta.com (HTTP '.$e->getResponse()->getStatusCode().')';

            Log::error('ExchangeRateService: decolecta.com rechazo la consulta', ['error' => $message]);

            return ['success' => false, 'message' => $prefix.'decolecta.com respondió: '.$message];
        } catch (\Exception $e) {
            Log::error('ExchangeRateService: no se pudo contactar a decolecta.com', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => $prefix.'No se pudo conectar con la alternativa decolecta.com: '.$e->getMessage()];
        }

        if (empty($data['sell_price']) || empty($data['date'])) {
            return [
                'success' => false,
                'message' => $prefix.'La respuesta de decolecta.com no contiene un tipo de cambio válido: '.json_encode($data, JSON_UNESCAPED_UNICODE),
            ];
        }

        $rate = SaleExchangeRate::updateOrCreate(
            [
                'currency_code' => $data['base_currency'] ?? 'USD',
                'rate_date' => Carbon::parse($data['date'])->format('Y-m-d'),
            ],
            [
                'purchase_rate' => (float) $data['buy_price'],
                'sale_rate' => (float) $data['sell_price'],
                'source' => 'decolecta',
                'fetched_by' => $user instanceof User ? $user->id : $user,
            ]
        );

        return [
            'success' => true,
            'message' => $prefix.'haciendo consulta alternativa a SUNAT en decolecta.com, tipo de cambio guardado correctamente.',
            'data' => $rate->toArray(),
            'source_label' => self::friendlySource('decolecta'),
        ];
    }

    /**
     * Devuelve el TC vigente de una moneda: el del dia, o el ultimo conocido
     * marcado como desactualizado. Nunca devuelve null sin informacion si
     * existe historial (para no bloquear ventas).
     *
     * @return array{rate: string, date: string, purchase: string, sale: string, is_stale: bool, source: string, source_label: string}|null
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
            'source_label' => self::friendlySource($rate->source),
        ];
    }

    /**
     * Etiqueta legible del origen del dato, para mostrar al usuario de donde
     * viene la informacion (SUNAT via Migo, SUNAT via decolecta.com, etc.).
     */
    public static function friendlySource(?string $source): string
    {
        return match ($source) {
            self::SOURCE_MIGO => 'SUNAT vía Migo (migo.pe)',
            'decolecta' => 'SUNAT vía decolecta.com',
            self::SOURCE_MANUAL => 'SUNAT vía Migo (consulta manual)',
            default => (string) $source,
        };
    }

    /**
     * TC vigente EN una fecha dada (el ultimo publicado con fecha <= a esa dia).
     * Sirve para comprobantes que se emiten dias despues de la compra: se usa
     * el TC que estaba activo cuando el cliente pago.
     *
     * @return array{rate: string, date: string, purchase: string, sale: string, is_stale: bool, source: string, source_label: string}|null
     */
    public function getCurrentRateForDate(string $currencyCode, ?string $date = null): ?array
    {
        $target = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $rate = SaleExchangeRate::where('currency_code', $currencyCode)
            ->whereDate('rate_date', '<=', $target)
            ->orderByDesc('rate_date')
            ->first();

        // Si no hay registro historico anterior a esa fecha, usar el ultimo conocido.
        if (! $rate) {
            return $this->getCurrentRate($currencyCode);
        }

        return [
            'rate' => $rate->sale_rate,
            'purchase' => $rate->purchase_rate,
            'sale' => $rate->sale_rate,
            'date' => Carbon::parse($rate->rate_date)->toDateString(),
            'is_stale' => Carbon::parse($rate->rate_date)->toDateString() !== $target,
            'source' => $rate->source,
            'source_label' => self::friendlySource($rate->source),
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
