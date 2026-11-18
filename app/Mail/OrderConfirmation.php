<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent to the customer when the payment succeeds (in the customer's language). */
class OrderConfirmation extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [config('shop.contact_email')],
            subject: __('shop.mail.confirmation_subject', ['number' => $this->order->number]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-confirmation');
    }
}
