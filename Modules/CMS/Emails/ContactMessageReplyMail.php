<?php

namespace Modules\CMS\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Respuesta que el equipo escribe desde el panel (CMS > Mensajes de Contacto)
 * y que se envia al correo de la persona que lleno el formulario.
 */
class ContactMessageReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * El asunto va en $subjectLine y no en $subject porque Mailable ya declara
     * esa propiedad para el asunto del mensaje que se esta construyendo.
     *
     * @param  array{name: string, email: string, service: ?string, message: string, created_at: ?string}  $original
     */
    public function __construct(
        public string $subjectLine,
        public string $body,
        public array $original,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            // Si la persona responde este correo, cae en el buzon de contacto.
            // Va dentro de un array a proposito: Laravel recorre to/cc/bcc/replyTo
            // esperando una lista, y un Address suelto revienta al hidratar el envelope.
            replyTo: [
                new Address(
                    config('mail.admin_email', 'contacto@aracodeperu.com'),
                    config('mail.from.name', 'ARACODE Smart Solutions'),
                ),
            ],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'cms::emails.contact-message-reply',
            with: [
                'body' => $this->body,
                'original' => $this->original,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
