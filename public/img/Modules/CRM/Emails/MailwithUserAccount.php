<?php

namespace Modules\CRM\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;

class MailwithUserAccount extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
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

    public function build()
    {
        return $this->view('crm::mails.mailwith-user-account', [
            'data' => $this->data
        ]);
    }
    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    // public function attachments(): array
    // {
    //     $Attachments = [];
    //     foreach ($this->data[1]->attachments as $file) {
    //         array_push(
    //             $Attachments,
    //             Attachment::fromPath(public_path('storage' . DIRECTORY_SEPARATOR . $file['path']))->as($file['file_name'])
    //         );
    //     }
    //     return $Attachments;
    // }
}
