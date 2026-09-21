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

class ConfirmPurchaseSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public mixed $data;
    public int $tries = 3;
    public array $backoff = [60, 300];

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            subject: 'Confirmación de compra de suscripción - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'academic::emails.subscription_gratitude',
            with: ['data' => $this->data],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
