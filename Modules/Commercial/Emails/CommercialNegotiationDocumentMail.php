<?php

namespace Modules\Commercial\Emails;

use App\Models\SaleDocument;
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
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialNegotiationDocumentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public CommercialNegotiation $negotiation;
    public SaleDocument $document;
    /** @var array<string, mixed> */
    public array $dataFile;
    /** @var array<string, string>|null */
    public ?array $credentials;
    public int $tries = 3;
    public array $backoff = [60, 300];

    /**
     * @param array<string, mixed> $dataFile
     * @param array<string, string>|null $credentials
     */
    public function __construct(CommercialNegotiation $negotiation, SaleDocument $document, array $dataFile, ?array $credentials = null)
    {
        $this->negotiation = $negotiation;
        $this->document = $document;
        $this->dataFile = $dataFile;
        $this->credentials = $credentials;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address('informes@globalcpaperu.com'), MailSender::name()),
            replyTo: MailSender::replyTo(
                $this->negotiation->client?->email,
                $this->negotiation->client?->full_name,
            ),
            subject: '¡Tu inscripción ha sido confirmada! 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'commercial::emails.commercial-negotiation-document',
            with: [
                'negotiation' => $this->negotiation,
                'document' => $this->document,
                'credentials' => $this->credentials,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        $pdf = $this->dataFile['pdf'] ?? null;

        if (! is_array($pdf) || empty($pdf['filePath'])) {
            throw new \RuntimeException('El PDF del comprobante es obligatorio para enviar el correo.');
        }

        $attachments[] = MailAttachmentResolver::required(
            (string) $pdf['filePath'],
            (string) ($pdf['fileName'] ?? 'comprobante.pdf'),
            $this->diskForPath((string) $pdf['filePath']),
        );

        $xml = $this->dataFile['xml'] ?? null;

        if (is_array($xml) && ! empty($xml['filePath'])) {
            $optional = MailAttachmentResolver::optional(
                (string) $xml['filePath'],
                (string) ($xml['fileName'] ?? 'comprobante.xml'),
                $this->diskForPath((string) $xml['filePath']),
            );

            if ($optional instanceof Attachment) {
                $attachments[] = $optional;
            }
        }

        return $attachments;
    }

    public function failed(\Throwable $exception): void
    {
        $negotiation = $this->negotiation->fresh();

        if ($negotiation) {
            $progress = $negotiation->process_progress ?? [];
            $progress = array_values(array_filter($progress, fn ($step) => $step !== 'email'));

            $negotiation->update([
                'email_sent_at' => null,
                'process_progress' => $progress,
            ]);
        }

        Log::error('CommercialNegotiationDocumentMail failed', [
            'negotiation_id' => $this->negotiation->getKey(),
            'document_id' => $this->document->getKey(),
            'message' => $exception->getMessage(),
        ]);
    }

    private function diskForPath(string $path): string
    {
        return str_contains(str_replace('\\', '/', $path), '/storage/app/') ? 'local' : 'public';
    }
}
