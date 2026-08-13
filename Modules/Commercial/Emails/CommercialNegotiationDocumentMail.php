<?php

namespace Modules\Commercial\Emails;

use App\Models\SaleDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialNegotiationDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $negotiation;

    public $document;

    public $dataFile;

    public function __construct(CommercialNegotiation $negotiation, SaleDocument $document, array $dataFile)
    {
        $this->negotiation = $negotiation;
        $this->document = $document;
        $this->dataFile = $dataFile;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                env('MAIL_FROM_ADDRESS', 'informes@globalcpaperu.com'),
                env('MAIL_FROM_NAME', 'CPA Academy')
            ),
            subject: 'Tu acuerdo y comprobante de pago'
        );
    }

    public function build()
    {
        return $this->view('commercial::emails.commercial-negotiation-document', [
            'negotiation' => $this->negotiation,
            'document' => $this->document,
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
