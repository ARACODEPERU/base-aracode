<?php

namespace Modules\Sales\Console;

use Illuminate\Console\Command;
use Modules\Sales\Services\ExchangeRateService;

/**
 * Consulta el tipo de cambio oficial de SUNAT (via Migo) una vez al dia.
 * El valor queda guardado en sales_exchange_rates y activo todo el dia
 * para ventas y facturacion electronica en dolares.
 */
class FetchExchangeRate extends Command
{
    protected $signature = 'sales:fetch-exchange-rate {--date= : Fecha específica Y-m-d (por defecto consulta el último publicado)}';

    protected $description = 'Consulta y guarda el tipo de cambio SUNAT del día (via Migo) para facturación en dólares';

    public function handle(ExchangeRateService $service): int
    {
        $this->info('Consultando tipo de cambio SUNAT via Migo...');

        $result = $service->fetchAndStore($this->option('date'));

        if ($result['success']) {
            $data = $result['data'];

            $this->info("✔ Guardado: {$data['currency_code']} {$data['rate_date']} compra={$data['purchase_rate']} venta={$data['sale_rate']} (fuente: {$data['source']})");

            return Command::SUCCESS;
        }

        $this->error('✘ '.$result['message']);

        return Command::FAILURE;
    }
}
