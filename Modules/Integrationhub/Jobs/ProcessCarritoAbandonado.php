<?php

namespace Modules\Integrationhub\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Onlineshop\Entities\OnliCarritoAbandonado;

class ProcessCarritoAbandonado implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $recordId;
    public string $phone;

    public function __construct(int $recordId, string $phone)
    {
        $this->recordId = $recordId;
        $this->phone = $phone;
    }

    public function handle(): void
    {
        try {
            $hub = app(IntegrationhubController::class);

            $hub->runEndpoint('create_contact', [
                'phone' => $this->phone,
                'email' => 'nohay@correo.com',
                'first_name' => "Usuario",
                'custom_fields' => [
                '691589' => 'no',               //var1
                '990426' => 'tiene',            //var2
                '91972' => 'correo',            //var3
                '412686' => 'ni nombre',        //var4
            ],
            ], [], true);

            // $hub->runEndpoint(
            //     "set_value_in_custom_fields_for_contact_id",
            //     [
            //         // Valores requeridos
            //     "contact_id" => $this->phone,
            //     "custom_field_id" => "539161", //el id de custom_field "Correo_electronico"
            //     "value" => "El correo iría aqui"
            //     ], [], true);

            //aqui agregar codigo de enviar plantilla endopoint flowid


            $record = OnliCarritoAbandonado::find($this->recordId);
            if ($record) {
                $record->update([
                    'notification_sent_at' => Carbon::now(),
                    'notification_count' => $record->notification_count + 1,
                ]);
            }
        } catch (\Throwable $th) {
            IntegrationError::create([
                'message' => 'ProcessCarritoAbandonado: ' . $th->getMessage(),
                'source' => 'ProcessCarritoAbandonado',
            ]);
        }
    }
}
