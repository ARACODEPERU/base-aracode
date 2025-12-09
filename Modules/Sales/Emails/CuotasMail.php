<?php

namespace Modules\Sales\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class CuotasMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $person;
    public $cronograma;
    /**
     * Create a new message instance.
     */
    public function __construct($data, $person = "", $cronograma = null)
    {
        $this->data = $data;
        $this->person = $person;
        $this->cronograma = $cronograma;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_FROM_ADDRESS', 'informes@globalcpaperu.com'),
                env('MAIL_FROM_NAME', 'CPA Academy')
            ),
            subject: 'Gracias por estar con nosotros - ' . env('APP_NAME', 'CPA Academy'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'layouts.email_gratitude_cuotas',
            with: [
                'data' => $this->data,
                'person' => $this->person,
                'cronograma' => $this->cronograma,
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
