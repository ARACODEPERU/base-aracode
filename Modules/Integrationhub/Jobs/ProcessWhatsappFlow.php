<?php

namespace Modules\Integrationhub\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;

class ProcessWhatsappFlow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public string $name;
    public string $phone;
    public string $email;
    public string $flowId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $name, string $phone, string $email, string $flowId)
    {
        $this->name = $name;
        $this->phone = $phone;
        $this->email = $email;
        $this->flowId = $flowId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hub = app(IntegrationhubController::class);

        try {
            // 1. Crear contacto
            $hub->runEndpoint('create_contact', [
                'phone' => $this->phone,
                'email' => $this->email,
                'first_name' => $this->name,
            ]);

            // 2. Sincronizar campo de correo electrónico en custom fields
            $hub->runEndpoint(
                "set_value_in_custom_fields_for_contact_id",
                [
                    "contact_id" => $this->phone,
                    "custom_field_id" => "539161",
                    "value" => $this->email,
                ],
                [],
                true
            );

            // 3. Iniciar contacto con flow_id
            $hub->runEndpoint('Inicio_contacto_con_flow_id', [
                'flow_id'    => $this->flowId,
                'contact_id' => ltrim($this->phone, '+'),
            ]);
        } catch (\Throwable $th) {
            IntegrationError::create([
                'message' => 'ProcessWhatsappFlow: ' . $th->getMessage(),
                'source'  => 'ProcessWhatsappFlow',
            ]);
        }
    }
}
