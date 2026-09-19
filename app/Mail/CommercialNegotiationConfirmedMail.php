<?php

namespace App\Mail;

use App\Support\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialNegotiationConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $negotiation;
    public $client;

    /** @var int Número máximo de intentos, compatible con el worker general. */
    public $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public $backoff = [60, 300];

    public function __construct(CommercialNegotiation $negotiation, $client)
    {
        $this->negotiation = $negotiation;
        $this->client = $client;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                MailSender::address('contacto@globalcpa.com'),
                MailSender::name()
            ),
            subject: 'Negociacion confirmada por el cliente - ' . config('app.name'),
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
