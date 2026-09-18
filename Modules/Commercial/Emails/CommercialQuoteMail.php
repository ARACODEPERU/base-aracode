<?php

namespace Modules\Commercial\Emails;

use App\Support\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialQuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $negotiation;

    public function __construct(CommercialNegotiation $negotiation)
    {
        $this->negotiation = $negotiation;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                MailSender::address('contacto@globalcpa.com'),
                MailSender::name()
            ),
            subject: 'Tu cotizacion: ' . $this->negotiation->title . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'commercial::emails.commercial-quote',
            with: [
                'negotiation' => $this->negotiation,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
