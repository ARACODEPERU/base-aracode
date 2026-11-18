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
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;

/**
 * Aviso al alumno de que su suscripción mensual se renovó correctamente.
 *
 * Lo envía SubscriptionRecurringNotifier al registrar un cobro autorizado.
 */
class SubscriptionRenewed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public AcaSubscriptionPreapproval $preapproval,
        public AcaSubscriptionPayment $payment,
        public string $studentName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            subject: '✅ Tu suscripción académica se renovó',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'academic::emails.subscription-renewed',
            with: [
                'preapproval' => $this->preapproval,
                'payment' => $this->payment,
                'studentName' => $this->studentName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
