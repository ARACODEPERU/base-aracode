<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use App\Support\MailSender;


class ConfirmPurchaseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int Intentos, compatibles con el worker general. */
    public int $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public array $backoff = [60, 300];

    public $data;
    public $dni;
    /**
     * Create a new message instance.
     */
    public function __construct($data, $dni = "")
    {
        $this->data = $data;
        $this->dni = $dni;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address('contacto@globalcpa.com'), MailSender::name()),
            subject: 'Gracias por estar con nosotros - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'layouts.email_gratitude',
            with: [
                'data' => $this->data,
                'dni' => $this->dni,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
