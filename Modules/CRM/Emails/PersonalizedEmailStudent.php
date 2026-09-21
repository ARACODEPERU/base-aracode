<?php

namespace Modules\CRM\Emails;

use App\Support\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PersonalizedEmailStudent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var array<string, mixed> */
    protected array $data;

    public int $tries = 3;
    public array $backoff = [60, 300];

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            replyTo: MailSender::replyTo($this->data['from_mail'] ?? null, $this->data['from_name'] ?? null),
            subject: (string) ($this->data['title'] ?? 'Mensaje de CPA Academy'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'crm::mails.personalize-email-student',
            with: ['data' => $this->data],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
