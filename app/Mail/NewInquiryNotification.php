<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent to the shop when someone uses the "Ask about this piece" form. */
class NewInquiryNotification extends Mailable
{
    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        $about = $this->inquiry->piece ? " — {$this->inquiry->piece->reference}" : '';

        return new Envelope(
            replyTo: [$this->inquiry->email],
            subject: "Message de {$this->inquiry->name}{$about}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-inquiry');
    }
}
