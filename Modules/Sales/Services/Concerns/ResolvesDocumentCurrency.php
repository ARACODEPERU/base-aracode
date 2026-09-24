<?php

namespace Modules\Sales\Services\Concerns;

use App\Models\Parameter;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Services\ExchangeRateService;

/**
 * Resuelve la moneda del comprobante y la conversion de precios.
 *
 * Reglas:
 * - La moneda base del sistema es PEN: todos los precios de productos se
 *   guardan en soles y llegan del frontend en soles.
 * - El interruptor PTM0004 (tabla parameters) define si se permite vender
 *   en otra moneda. Si esta desactivado y llega currency != PEN se rechaza
 *   con error de validacion (el frontend tampoco lo permite).
 * - El TC vigente lo resuelve el SERVIDOR (ExchangeRateService), nunca el
 *   cliente, para que los calculos de facturacion electronica sean exactos.
 * - La conversion de cada linea la hace QuickSaleItemCalculator sobre el
 *   precio unitario ya convertido a la moneda del comprobante.
 */
trait ResolvesDocumentCurrency
{
    /**
     * Resuelve [currency, exchangeRate] a partir del request.
     *
     * @return array{0: string, 1: float|null} codigo de moneda y TC aplicado (null en PEN)
     */
    protected function resolveDocumentCurrency(?\Illuminate\Http\Request $request = null, array $input = []): array
    {
        $currency = strtoupper((string) ($input['currency'] ?? ($request?->input('currency') ?? 'PEN')));

        if ($currency === ExchangeRateService::BASE_CURRENCY) {
            return [ExchangeRateService::BASE_CURRENCY, null];
        }

        $service = app(ExchangeRateService::class);

        if (! $service->isMultiCurrencyEnabled()) {
            throw ValidationException::withMessages([
                'currency' => 'El sistema está configurado para operar solo en soles (parámetro PTM0004 desactivado).',
            ]);
        }

        $rate = $service->getCurrentRate($currency);

        if (! $rate || (float) $rate['rate'] <= 0) {
            throw ValidationException::withMessages([
                'currency' => "No hay tipo de cambio vigente para {$currency}. Usa el botón «Cambio de moneda» del header para consultarlo a SUNAT.",
            ]);
        }

        return [$currency, (float) $rate['rate']];
    }

    /**
     * Convierte un precio unitario en soles a la moneda del comprobante.
     */
    protected function convertUnitPrice(float $priceInSoles, ?float $exchangeRate): float
    {
        if ($exchangeRate === null || $exchangeRate <= 0) {
            return $priceInSoles;
        }

        return $priceInSoles / $exchangeRate;
    }

    /**
     * Umbral de detraccion (S/ 700) evaluado en soles: convierte el total
     * del comprobante a soles con el TC aplicado.
     */
    protected function totalInSoles(float $totalInCurrency, ?float $exchangeRate): float
    {
        if ($exchangeRate === null || $exchangeRate <= 0) {
            return $totalInCurrency;
        }

        return $totalInCurrency * $exchangeRate;
    }

    /**
     * Contexto de moneda para las vistas de venta (combo y TC vigente).
     */
    protected function getCurrencyContext(): array
    {
        $service = app(ExchangeRateService::class);

        return [
            'enabled' => $service->isMultiCurrencyEnabled(),
            'currencies' => $service->getEnabledCurrencies(),
        ];
    }

    /**
     * Descripcion de moneda para la leyenda en letras.
     */
    protected function currencyLetterDescription(string $currency): string
    {
        return $currency === 'USD' ? 'DÓLARES AMERICANOS' : 'SOLES';
    }
}
