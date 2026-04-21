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

class ThankYouAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $person;

    public function __construct($person)
    {
        $this->person = $person instanceof Person ? $person : Person::find($person);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_FROM_ADDRESS', 'informes@globalcpaperu.com'),
                env('MAIL_FROM_NAME', 'CPA Academy')
            ),
            subject: 'Gracias por estar con nosotros - ' . env('APP_NAME', 'Global CPA'),
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