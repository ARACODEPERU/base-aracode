<?php

namespace Modules\CRM\Emails;

use App\Support\MailAttachmentResolver;
use App\Support\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClientHelpEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var array<int, mixed> */
    protected array $data;

    public int $tries = 3;
    public array $backoff = [60, 300];

    /** @param array<int, mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        $conversation = $this->data[0] ?? null;
        $message = $this->data[1] ?? null;
        $name = $this->data[2] ?? null;

        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            replyTo: MailSender::replyTo($message?->email_from, $name),
            subject: (string) ($conversation?->title ?? 'Respuesta de soporte'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'crm::mails.client-help-mail',
            with: ['data' => $this->data],
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        $messageAttachments = $this->data[1]?->attachments ?? [];

        foreach ((array) $messageAttachments as $file) {
            $path = is_array($file) ? ($file['path'] ?? null) : null;
            $name = is_array($file) ? ($file['file_name'] ?? basename((string) $path)) : null;

            if (! $path) {
                continue;
            }

            $attachment = MailAttachmentResolver::optional($path, $name, 'public');

            if ($attachment instanceof Attachment) {
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ClientHelpEmail failed', [
            'recipient' => $this->data[1]?->email_for ?? null,
            'message' => $exception->getMessage(),
        ]);
    }
}
