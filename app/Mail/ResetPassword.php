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

    public function __construct($user)
    {
        $this->user = $user;
        $this->url = route('password.reset', Password::createToken($user));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Restablecer Contraseña',
        );
    }

    /**
     * Get the message content definition.
     */
    public function build()
    {
        return $this->view('emails.reset_password')
            ->subject('Restablecer Contraseña')
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
