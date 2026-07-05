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
    public ?string $role;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $email, string $name, ?string $role = null)
    {
        $this->phone = $phone;
        $this->email = $email;
        $this->name = $name;
        $this->role = $role;
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
            $flowId = '1780844285916';

            // Si la persona tiene rol Docente, usar el flow_id específico para docentes sino se presume es ALumno
            if ($this->role === 'Docente') {
                $flowId = IntegrationFlowId::where('key', 'birthday_greeting_teacher')->value('flow_id') ?? '1782495662257';
            }else{
                $flowId = IntegrationFlowId::where('key', 'birthday_greeting')->value('flow_id') ?? '1780844285916';
            }

            // 2. Crear contacto en la API externa e Iniciar flujo de WhatsApp de cumpleaños
            $hub->runEndpoint('create_contact', [
                'phone' => $this->phone,
                'email' => $this->email,
                'first_name' => $this->name,
                'actions' => [
                    [
                        'action' => 'set_field_value',
                        'field_name' => 'Correo_electronico',
                        'value' => $this->email,
                    ],
                    [
                        'action' => 'send_flow',
                        'flow_id' => $flowId
                    ],
                ],
            ], [], true);


            // 3. Iniciar flujo de WhatsApp de cumpleaños

            // $hub->runEndpoint('Inicio_contacto_con_flow_id', [
            //     'flow_id'    => $flowId,
            //     'contact_id' => ltrim($this->phone, '+'),
            // ], [], false);
        } catch (\Throwable $th) {
            IntegrationError::create([
                'message' => 'ProcessBirthdayWhatsapp: ' . $th->getMessage(),
                'source' => 'ProcessBirthdayWhatsapp',
            ]);
        }
    }
}
