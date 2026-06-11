<?php

namespace Modules\Integrationhub\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
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
            ->where('created_at', '<', Carbon::now()->subMinutes(10))
            ->where(function ($q) {
                $q->whereNull('notification_sent_at')
                    ->orWhere('notification_sent_at', '<', Carbon::now()->subMinutes(10));
            })
            ->get();

        $this->info("Encontrados {$records->count()} registros por revisar.");

        foreach ($records as $record) {
            $recipientEmail = $record->email;
            $recipientEmail = "notiene@correo.com";

            if (!$recipientEmail) {
                $this->warn("Registro {$record->id}: sin email, no se puede enviar correo.");
                $record->update([
                    'notification_sent_at' => Carbon::now(),
                    'notification_count' => $record->notification_count + 1,
                ]);
                continue;
            }

            $phone = ($record->phone_country ? ltrim($record->phone_country, '+') : '') . $record->phone;

           //Activar esto y mas adelante pasar el id de plantilla
            // ProcessCarritoAbandonado::dispatch(
            //     $record->id,
            //     $phone
            // );

            $this->info("Job encolado para {$recipientEmail} (registro {$record->id}).");
        }

        $this->info('Revisión de carritos abandonados completada. Jobs enviados al queue:work.');
        return 0;
    }
}
