<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;


class ConfirmPurchaseMail extends Mailable
{
    use Queueable, SerializesModels;

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
            from: new Address(
                env('MAIL_FROM_ADDRESS', 'contacto@globalcpa.com'),
                env('MAIL_FROM_NAME', 'CPA Academy')
            ),
            subject: 'Gracias por estar con nosotros - ' . env('APP_NAME', 'Global CPA'),
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
