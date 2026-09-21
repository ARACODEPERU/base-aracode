<?php

namespace Modules\Integrationhub\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Integrationhub\Jobs\ProcessCarritoAbandonado;
use Modules\Onlineshop\Entities\OnliCarritoAbandonado;

class CarritoAbandonado extends Command
{
    protected $signature = 'integrationhub:carrito-abandonado';
    protected $description = 'Revisa carritos abandonados con paid=false. Si pasaron 10 min se encola job al queue:work.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Revisando carritos abandonados...');

        $records = OnliCarritoAbandonado::where('paid', false)
            ->whereNull('last_success_at')
            ->where('notification_count', '<', 5)
            ->where('created_at', '<', Carbon::now()->subMinutes(10))
            ->where(function ($q) {
                $q->whereNull('notification_sent_at')
                    ->orWhere('notification_sent_at', '<', Carbon::now()->subMinutes(10));
            })
            ->get();

        $this->info("Encontrados {$records->count()} registros por revisar.");

        foreach ($records as $record) {
            $phone = ($record->phone_country ? ltrim($record->phone_country, '+') : '') . trim((string) $record->phone);

            if ($phone === '') {
                $this->warn("Registro {$record->id}: sin teléfono, no se puede enviar a IntegrationHub.");
                $record->update([
                    'notification_sent_at' => Carbon::now(),
                    'notification_count' => $record->notification_count + 1,
                ]);
                continue;
            }

            // Reservar el registro antes de encolar para evitar jobs duplicados
            // mientras el worker procesa la ejecución de IntegrationHub.
            $record->update(['notification_sent_at' => Carbon::now()]);

            ProcessCarritoAbandonado::dispatch($record->id, $phone);

            $this->info("Job de IntegrationHub encolado para el registro {$record->id}.");
        }

        $this->info('Revisión de carritos abandonados completada. Jobs enviados al queue:work.');
        return 0;
    }
}
