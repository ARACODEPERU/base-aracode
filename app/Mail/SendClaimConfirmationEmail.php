<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class SendClaimConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'), // Usa config() en lugar de env()
                config('mail.from.name')
            ),
            subject: 'Confirmación de Recepción de Reclamo - Folio: ' . $this->data->composite_code,
        );
    }

    public function build()
    {
        return $this->view('emails.claim_confirmation', [
            'data' => $this->data
        ]);
    }

    public function attachments(): array
    {
        return [];
    }
}
