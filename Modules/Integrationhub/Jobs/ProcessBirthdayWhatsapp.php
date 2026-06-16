<?php

namespace Modules\Integrationhub\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\Integrationhub\Emails\BirthdayGreetingMail;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Entities\IntegrationFlowId;

class ProcessBirthdayWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $phone;
    public string $email;
    public string $name;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $email, string $name)
    {
        $this->phone = $phone;
        $this->email = $email;
        $this->name = $name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hub = app(IntegrationhubController::class);

        try {
            // 1. Enviar correo de felicitación de cumpleaños
            //Mail::to($this->email)->send(new BirthdayGreetingMail($this->name));

            // 2. Crear contacto en la API externa
            $hub->runEndpoint('create_contact', [
                'phone' => $this->phone,
                'email' => $this->email,
                'first_name' => $this->name,
            ], [], true);

            $hub->runEndpoint(
                "set_value_in_custom_fields_for_contact_id",
                [
                    // Valores requeridos
                "contact_id" => $this->phone,
                "custom_field_id" => "539161", //el id de custom_field "Correo_electronico"
                "value" => $this->email
                ], [], true);

            // 3. Iniciar flujo de WhatsApp de cumpleaños
            $flowId = IntegrationFlowId::where('key', 'birthday_greeting')->value('flow_id') ?? '1780844285916';
            $hub->runEndpoint('Inicio_contacto_con_flow_id', [
                'flow_id'    => $flowId,
                'contact_id' => ltrim($this->phone, '+'),
            ], [], false);
        } catch (\Throwable $th) {
            IntegrationError::create([
                'message' => 'ProcessBirthdayWhatsapp: ' . $th->getMessage(),
                'source' => 'ProcessBirthdayWhatsapp',
            ]);
        }
    }
}
