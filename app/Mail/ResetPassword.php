<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

class ResetPassword extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int Intentos, compatibles con el worker general. */
    public int $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public array $backoff = [60, 300];

    /**
     * Create a new message instance.
     */
    public $user;

    /**
     * Enlace de restablecimiento, resuelto ANTES de encolar.
     *
     * El token se crea una sola vez: si se generara en build()/content(), cada
     * reintento del worker emitiria un token nuevo e invalidaria el enlace que ya
     * se le envio al usuario.
     */
    public string $url;

    /**
     * Vista del correo. Por defecto es la genérica; el flujo de recuperación de
     * organizadores pasa la variante de Pichanguero solo cuando PW00001 = 2.
     */
    public string $vista;

    public function __construct($user, ?string $vista = null)
    {
        $this->user = $user;
        $this->vista = $vista ?: 'emails.reset_password';
        $this->url = route('password.reset', Password::createToken($user));
    }

    /**
     * true cuando el correo usa la identidad de Pichanguero.
     */
    private function esPichanguero(): bool
    {
        return $this->vista === 'emails.reset_password_pichanguero';
    }

    /**
     * Asunto del correo según su variante.
     */
    private function asunto(): string
    {
        return $this->esPichanguero()
            ? 'Restablece tu contraseña · Pichanguero'
            : 'Restablecer Contraseña';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->asunto(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function build()
    {
        return $this->view($this->vista)
            ->subject($this->asunto())
            ->with([
                'url' => $this->url,
                'user' => $this->user
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
