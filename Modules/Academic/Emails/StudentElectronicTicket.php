<?php

namespace Modules\Academic\Emails;

use App\Support\MailAttachmentResolver;
use App\Support\MailSender;
use Modules\Onlineshop\Entities\OnliSale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\SaleDocument;

class StudentElectronicTicket extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var array<string, mixed> */
    public array $data;

    public int $tries = 3;

    public array $backoff = [60, 300];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            replyTo: MailSender::replyTo($this->data['for_mail'] ?? null, $this->data['for_name'] ?? null),
            subject: (string) ($this->data['title'] ?? 'Comprobante de pago'),
        );
    }

    public function content(): Content
    {
        $sale = null;

        if (! empty($this->data['document_id'])) {
            $sale = SaleDocument::with('items')->find($this->data['document_id']);
        }

        return new Content(
            view: 'academic::emails.student-electronic-ticket',
            with: [
                'data' => $this->data,
                'sale' => $sale,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        $pdf = MailAttachmentResolver::required(
            (string) ($this->data['file_path'] ?? ''),
            (string) ($this->data['file_name'] ?? 'comprobante.pdf'),
            $this->diskForPath($this->data['file_path'] ?? null),
        );
        $attachments[] = $pdf;

        $xml = MailAttachmentResolver::optional(
            $this->data['xml_file_path'] ?? null,
            $this->data['xml_file_name'] ?? null,
            $this->diskForPath($this->data['xml_file_path'] ?? null),
        );

        if ($xml instanceof Attachment) {
            $attachments[] = $xml;
        }

        return $attachments;
    }

    public function failed(\Throwable $exception): void
    {
        if (! empty($this->data['onlisale_id'])) {
            OnliSale::whereKey($this->data['onlisale_id'])->update(['email_sent' => false]);
        }

        Log::error('StudentElectronicTicket failed', [
            'document_id' => $this->data['document_id'] ?? null,
            'onli_sale_id' => $this->data['onlisale_id'] ?? null,
            'recipient' => $this->data['for_mail'] ?? null,
            'message' => $exception->getMessage(),
        ]);
    }

    private function diskForPath(?string $path): string
    {
        return is_string($path) && str_contains(str_replace('\\', '/', $path), '/storage/app/')
            ? 'local'
            : 'public';
    }
}
