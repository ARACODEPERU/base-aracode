<?php

namespace Modules\Academic\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SubscriptionExpired extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription; // Objeto de suscripción para pasar datos a la vista del correo
    public $studentName;  // Nombre del estudiante

    /**
     * Create a new message instance.
     */
    public function __construct($subscription, $studentName)
    {
        $this->subscription = $subscription;
        $this->studentName = $studentName;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('🔔 Tu suscripción académica ha expirado') // Asunto del correo
                    ->view('academic::emails.subscription-expired'); // Ruta de la vista del correo dentro del módulo
    }
}
