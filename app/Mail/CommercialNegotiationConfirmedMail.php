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
use Modules\Commercial\Entities\CommercialNegotiation;

class CommercialNegotiationConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $negotiation;
    public $client;

    /**
     * Enlace a la negociacion dentro del panel: es el "siguiente paso" que el
     * equipo debe continuar. Al ser propiedad publica, la vista la tiene disponible
     * como $reviewUrl.
     *
     * Se resuelve aqui, y no con route() dentro de la vista, para que un nombre de
     * ruta no publicado (por ejemplo una cache de rutas anterior a habilitar el
     * modulo) no deje el correo sin enviar: en ese caso se usa la URL canonica.
     */
    public $reviewUrl;

    /** @var int Número máximo de intentos, compatible con el worker general. */
    public $tries = 3;

    /** @var array<int, int> Demoras entre reintentos, en segundos. */
    public $backoff = [60, 300];

    public function __construct(CommercialNegotiation $negotiation, $client)
    {
        $this->negotiation = $negotiation;
        $this->client = $client;
        $this->reviewUrl = $this->resolveReviewUrl($negotiation);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                MailSender::address('contacto@globalcpa.com'),
                MailSender::name()
            ),
            subject: 'Negociacion confirmada por el cliente - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commercial_negotiation_confirmed',
            with: [
                'negotiation' => $this->negotiation,
                'client' => $this->client,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * El correo va encolado: si falla, el worker solo deja un "FAIL" y el motivo
     * queda enterrado (asi paso cuando la plantilla no existia y nadie se entero).
     * Aqui se registra el error junto al id de la negociacion para poder ubicarlo.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('No se pudo enviar el aviso de negociacion confirmada.', [
            'negotiation_id' => $this->negotiation?->id,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * route() lanza cuando el nombre no esta publicado (caches de rutas anteriores
     * a habilitar el modulo): se cae a la URL canonica del modulo, que es la misma
     * que esa ruta genera, para que el aviso salga igual.
     */
    private function resolveReviewUrl(CommercialNegotiation $negotiation): string
    {
        try {
            return route('comm_negotiations_show', $negotiation->id);
        } catch (\Throwable $e) {
            return url('/commercial/negotiations/show/' . $negotiation->id);
        }
    }
}
