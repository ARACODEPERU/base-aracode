<?php

namespace App\Mail;

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
 * Aviso interno: alguien dejo sus datos para descargar un brochure.
 *
 * Se envia al equipo de Ventas desde CmsSubscriberController. Faltaba por
 * completo en este proyecto: el controlador ya lo encolaba, asi que la clase no
 * se encontraba y el catch vacio de ese punto se tragaba el fallo en silencio
 * (nadie de Ventas se enteraba de las descargas).
 */
class NotificacionDescarga_brochure extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @var int Intentos, compatibles con el worker general. */
    public int $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public array $backoff = [60, 300];

    /** @var \Modules\CMS\Entities\CmsSubscriber Suscriptor que descargo el brochure. */
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(MailSender::address(), MailSender::name()),
            subject: 'Alguien descargó el brochure - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion_descarga_brochure',
            with: [
                'data' => $this->data,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * El correo va encolado: si el SMTP falla tras los 3 intentos el error
     * quedaria solo como un "FAIL" del worker. Aqui se registra junto al
     * suscriptor para poder ubicarlo y reintentar.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('No se pudo enviar el aviso de descarga de brochure.', [
            'subscriber_id' => $this->data->id ?? null,
            'subscriber_email' => $this->data->email ?? null,
            'error' => $e->getMessage(),
        ]);
    }
}
