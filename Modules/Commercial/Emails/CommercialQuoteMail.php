<?php

namespace Modules\Commercial\Emails;

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
                env('MAIL_FROM_ADDRESS', 'contacto@globalcpa.com'),
                env('MAIL_FROM_NAME', 'CPA Academy')
            ),
            subject: 'Tu cotizacion: ' . $this->negotiation->title . ' - ' . env('APP_NAME', 'Global CPA'),
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
