<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BlogSubscriberAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int Intentos, compatibles con el worker general. */
    public int $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public array $backoff = [60, 300];

    public string $name;
    public string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo suscriptor al blog - ' . $this->email,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.blog-subscriber-admin',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
