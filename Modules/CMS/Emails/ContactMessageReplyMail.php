<?php

namespace Modules\CMS\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Respuesta que el equipo escribe desde el panel (CMS > Mensajes de Contacto)
 * y que se envia al correo de la persona que lleno el formulario.
 */
class ContactMessageReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int Intentos, compatibles con el worker general. */
    public int $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public array $backoff = [60, 300];

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

    /**
     * El correo va encolado, asi que el encolado ya no dice nada sobre el envio
     * real: el mensaje queda marcado como respondido en el panel y si el SMTP
     * falla tras los 3 intentos el fallo solo se veria como un "FAIL" del worker.
     * Aqui queda registrado con el correo del destinatario.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('CMS Contacto: no se pudo enviar la respuesta a ' . ($this->original['email'] ?? 'destinatario desconocido') . '.', [
            'error' => $e->getMessage(),
        ]);
    }
}
