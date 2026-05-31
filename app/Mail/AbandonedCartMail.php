<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order   $order,
        public readonly Product $product,
        public readonly User    $user,
        public readonly string  $checkoutUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.abandoned_cart_subject', ['title' => $this->product->localized('title')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.abandoned-cart');
    }
}
