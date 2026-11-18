<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent to the customer when the order is marked as shipped in the admin. */
class OrderShipped extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [config('shop.contact_email')],
            subject: __('shop.mail.shipped_subject', ['number' => $this->order->number]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-shipped');
    }
}
