<?php

namespace App\Mail;

use App\Models\Person;
use App\Support\MailSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Enlace de recuperacion de contraseña para un alumno, enviado desde el panel.
 *
 * Faltaba por completo en este proyecto: AcaStudentController::sendPasswordRecoveryMail
 * ya lo encolaba, asi que la clase no se encontraba y el catch (\Exception) no
 * atrapaba el \Error de "class not found": la accion devolvia un 500.
 *
 * La URL firmada se construye en el controlador y se pasa ya hecha, de modo que
 * un reintento no emite un enlace distinto al que se le envio al alumno.
 */
class StudentPasswordRecoveryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int Intentos, compatibles con el worker general. */
    public int $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public array $backoff = [60, 300];

    public Person $person;

    /** @var string URL firmada y temporal del formulario de nueva contraseña. */
    public string $resetUrl;

    public function __construct(Person $person, string $resetUrl)
    {
        $this->person = $person;
        $this->resetUrl = $resetUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            subject: 'Recuperar contraseña - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student_password_recovery',
            with: [
                'person' => $this->person,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * El correo va encolado: el panel ya respondio "enviado", asi que un fallo
     * tras los 3 intentos tiene que quedar registrado con el alumno afectado.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('No se pudo enviar el correo de recuperación de contraseña del alumno.', [
            'person_id' => $this->person->id ?? null,
            'email' => $this->person->email ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
