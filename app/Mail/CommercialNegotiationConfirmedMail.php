<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialNegotiationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $negotiation;
    public $client;

    public function __construct(CommercialNegotiation $negotiation, $client)
    {
        $this->negotiation = $negotiation;
        $this->client = $client;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_FROM_ADDRESS', 'contacto@globalcpa.com'),
                env('MAIL_FROM_NAME', 'CPA Academy')
            ),
            subject: 'Negociacion confirmada por el cliente - ' . env('APP_NAME', 'Global CPA'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commercial_negotiation_confirmed',
            with: [
                'negotiation' => $this->negotiation,
                'client' => $this->client,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
