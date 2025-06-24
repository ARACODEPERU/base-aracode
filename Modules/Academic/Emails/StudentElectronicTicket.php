<?php

namespace Modules\Academic\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

class StudentElectronicTicket extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $from_mail = $this->data['from_mail'];
        $from_name = $this->data['from_name'];
        $title = $this->data['from_name'];

        return new Envelope(
            from: new Address($from_mail, $from_name),
            subject: $title,
        );
    }

    public function build()
    {
        return $this->view('academic::emails.student-electronic-ticket', [
            'data' => $this->data
        ]);
    }
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // El PDF siempre se adjunta. Es buena práctica verificar si existe.
        // Asumimos que $this->data['file_path'] y $this->data['file_name'] siempre están presentes.
        if (isset($this->data['file_path']) && file_exists($this->data['file_path'])) {
            $attachments[] = Attachment::fromPath($this->data['file_path'])
                                    ->as($this->data['file_name']);
        }

        // El XML se adjunta CONDICIONALMENTE (solo si existe su ruta y el archivo físico)
        if (isset($this->data['xml_file_path']) && file_exists($this->data['xml_file_path'])) {
            $attachments[] = Attachment::fromPath($this->data['xml_file_path'])
                                    ->as($this->data['xml_file_name']);
        }

        return $attachments;
    }
}
