<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
use App\Models\Person;
use App\Support\MailSender;

class ThankYouAccessMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int Intentos, compatibles con el worker general. */
    public int $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public array $backoff = [60, 300];

    public $person;

    public function __construct($person)
    {
        $this->person = $person instanceof Person ? $person : Person::find($person);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address('informes@globalcpaperu.com'), MailSender::name()),
            subject: 'Gracias por estar con nosotros - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'layouts.email_gratitude_access',
            with: [
                'person' => $this->person,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}