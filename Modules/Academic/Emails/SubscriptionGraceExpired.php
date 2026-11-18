<?php

namespace Modules\Academic\Emails;

use App\Support\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;

/**
 * Aviso de suspensión: terminaron los días de gracia sin cobro aprobado.
 *
 * El alumno conserva su historial, pero necesita renovar manualmente para
 * volver a acceder a los contenidos.
 */
class SubscriptionGraceExpired extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public AcaSubscriptionPreapproval $preapproval,
        public string $studentName,
        public int $graceDays = 3,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            subject: '🔒 Tu suscripción académica quedó suspendida',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'academic::emails.subscription-grace-expired',
            with: [
                'preapproval' => $this->preapproval,
                'studentName' => $this->studentName,
                'graceDays' => $this->graceDays,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
