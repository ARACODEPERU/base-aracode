<?php

namespace Modules\Academic\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Support\MailSender;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class SubscriptionExpired extends Mailable implements ShouldQueue
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
    public int $tries = 3;
    public array $backoff = [60, 300];

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            subject: '🔔 Tu suscripción académica ha expirado',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'academic::emails.subscription-expired',
            with: [
                'subscription' => $this->subscription,
                'studentName' => $this->studentName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
