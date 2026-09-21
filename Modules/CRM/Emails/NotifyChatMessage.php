<?php

namespace Modules\CRM\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use App\Support\MailSender;

class NotifyChatMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public int $tries = 3;
    public array $backoff = [60, 300];

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            subject: '✉️ Nuevo mensaje de ' . ($this->data['fullName'] ?? 'un alumno'),
        );
    }

    public function build()
    {
        return $this->view('crm::mails.notify-chat-message', [
            'data' => $this->data
        ]);
    }
}
