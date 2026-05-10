<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SaleNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public User $seller) {}

    public function build()
    {
        $this->order->loadMissing(['items.product', 'user']);

        $locale = $this->seller->locale ?: 'uk';
        $firstItem = $this->order->items->first();
        $productTitle = '';
        $productUrl = '';
        if ($firstItem && $firstItem->product) {
            $productTitle = $firstItem->product->localized('title', $locale);
            $productUrl = route('products.show', $firstItem->product);
        }

        $rendered = app(EmailTemplateRenderer::class)->render('model_sold', [
            'order' => [
                'number' => $this->order->number,
                'total' => number_format((float) $this->order->total, 2),
                'currency' => $this->order->currency,
            ],
            'user' => [
                'name' => $this->order->user->name,
                'email' => $this->order->user->email,
            ],
            'product' => [
                'title' => $productTitle,
                'url' => $productUrl,
            ],
        ], $locale);

        return $this->subject($rendered['subject'])
            ->view('emails.templated', ['body' => $rendered['body']]);
    }
}
