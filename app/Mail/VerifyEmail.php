<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * URL firmada, resuelta ANTES de encolar: la URL es temporal y un reintento
     * no debe emitir un enlace distinto al que ya se envio al usuario.
     */
    public string $url;

    /** @var int Intentos, compatibles con el worker general. */
    public int $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public array $backoff = [60, 300];

    public function __construct($user)
    {
        $this->user = $user;
        $this->url = $user->verificationUrl();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifica tu correo electrónico',
        );
    }

    /**
     * Get the message content definition.
     */
    public function build()
    {
        return $this->view('emails.verify')
            ->subject('Verifica tu correo electrónico')
            ->with([
                'url' => $this->url,
            ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
