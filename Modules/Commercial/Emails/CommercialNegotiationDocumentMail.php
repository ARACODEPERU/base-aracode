<?php

namespace Modules\Commercial\Emails;

use App\Models\SaleDocument;
use App\Support\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialNegotiationDocumentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $negotiation;

    public $document;

    public $dataFile;

    public $credentials;

    /** @var int Número máximo de intentos, compatible con el worker general. */
    public $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public $backoff = [60, 300];

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
            from: new Address(
                MailSender::address('informes@globalcpaperu.com'),
                MailSender::name()
            ),
            subject: '¡Tu inscripción ha sido confirmada! 🎉'
        );
    }

    public function build()
    {
        return $this->view('commercial::emails.commercial-negotiation-document', [
            'negotiation' => $this->negotiation,
            'document' => $this->document,
            'credentials' => $this->credentials,
        ]);
    }

    /**
     * Si el worker agota los intentos, permite reintentar el paso desde Commercial
     * en lugar de dejarlo marcado como enviado indefinidamente.
     */
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
            'message' => $exception->getMessage(),
        ]);
    }

    public function attachments(): array
    {
        $attachments = [];

        $pdf = $this->dataFile['pdf'] ?? null;

        if (isset($pdf['filePath']) && file_exists($pdf['filePath'])) {
            $attachments[] = Attachment::fromPath($pdf['filePath'])->as($pdf['fileName']);
        }

        $xml = $this->dataFile['xml'] ?? null;

        if (isset($xml['filePath']) && file_exists($xml['filePath'])) {
            $attachments[] = Attachment::fromPath($xml['filePath'])->as($xml['fileName']);
        }

        return $attachments;
    }
}
