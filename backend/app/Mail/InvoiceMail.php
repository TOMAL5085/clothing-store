<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['items', 'shippingAddress', 'user']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invoice '.$this->order->number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice');
    }
}
