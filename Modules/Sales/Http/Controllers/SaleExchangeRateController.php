<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sales\Services\ExchangeRateService;

/**
 * Tipo de cambio SUNAT (via Migo).
 * - GET  /sale/exchange-rates/current : estado actual (para el modal del header y los puntos de venta)
 * - POST /sale/exchange-rates/fetch   : re-consulta a SUNAT y guarda (boton "Consultar a SUNAT")
 * Ambas rutas requieren el permiso invo_tipo_cambio.
 */
class SaleExchangeRateController extends Controller
{
    public function __construct(private readonly ExchangeRateService $service)
    {
    }

    /**
     * Estado actual del tipo de cambio + interruptor multi-moneda.
     * Lo consume el modal del header y las vistas de venta para el recálculo.
     */
    public function current(Request $request)
    {
        $currencies = $request->get('currency', 'USD');
        $currencyCodes = is_array($currencies) ? $currencies : [$currencies];

        $rates = [];
        foreach ($currencyCodes as $code) {
            if ($code === ExchangeRateService::BASE_CURRENCY) {
                continue;
            }

            $rate = $this->service->getCurrentRate($code);
            if ($rate) {
                $rates[$code] = $rate;
            }
        }

        return response()->json([
            'success' => true,
            'multi_currency_enabled' => $this->service->isMultiCurrencyEnabled(),
            'rates' => $rates,
        ]);
    }

    /**
     * Consulta a SUNAT (Migo) y guarda el resultado. Boton "Consultar a SUNAT".
     */
    public function fetch(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $result = $this->service->fetchAndStore(
            $validated['date'] ?? null,
            Auth::id(),
            ExchangeRateService::SOURCE_MANUAL
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
