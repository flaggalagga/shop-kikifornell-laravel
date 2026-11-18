<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Sent to the shop when a new order is paid. */
class NewOrderNotification extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: array_filter([$this->order->customer_email]),
            subject: "Nouvelle commande {$this->order->number} — {$this->order->money('total_cents')}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-order');
    }
}
