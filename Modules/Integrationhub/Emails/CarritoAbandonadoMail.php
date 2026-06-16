<?php

namespace Modules\Integrationhub\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CarritoAbandonadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public array $items;
    public string $total;

    public function __construct(string $name, array $items, string $total)
    {
        $this->name = $name;
        $this->items = $items;
        $this->total = $total;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Tus cursos te están esperando!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'integrationhub::emails.carrito-abandonado',
            with: [
                'name' => $this->name,
                'items' => $this->items,
                'total' => $this->total,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
