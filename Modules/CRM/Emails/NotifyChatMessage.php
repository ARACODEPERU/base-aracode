<?php

namespace Modules\CRM\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

class NotifyChatMessage extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        $from_mail = env('MAIL_FROM_ADDRESS');
        $from_name = env('MAIL_FROM_NAME');
        $title = 'Consulta de Alumno';

        return new Envelope(
            from: new Address($from_mail, $from_name),
            subject: $title,
        );
    }

    public function build()
    {
        //dd($this->data);
        return $this->view('crm::mails.notify-chat-message', [
            'data' => $this->data
        ]);
    }
}
