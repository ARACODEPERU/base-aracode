<?php

namespace Modules\CRM\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

class PersonalizedEmailStudent extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function envelope(): Envelope
    {
        $from_mail = $this->data['from_mail'];
        $from_name = $this->data['from_name'];
        $title = $this->data['title'];
        $title = $this->data['title'];

        return new Envelope(
            from: new Address($from_mail, $from_name),
            subject: $title,
        );
    }
    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('crm::mails.personalize-email-student', [
            'data' => $this->data
        ]);
    }
}
