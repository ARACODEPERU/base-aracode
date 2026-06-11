<?php

namespace Modules\Integrationhub\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TESTBORRAME extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct() {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TESTBORRAME',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'integrationhub::emails.testborrame',
            with: [
                'testMessage' => 'TESTBORRAME',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
