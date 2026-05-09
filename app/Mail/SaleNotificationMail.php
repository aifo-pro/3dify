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
        $locale = $this->seller->locale ?: 'uk';
        $rendered = app(EmailTemplateRenderer::class)->render('model_sold', [
            'order' => [
                'number' => $this->order->number,
                'total' => number_format((float) $this->order->total, 2),
                'currency' => $this->order->currency,
            ],
            'user' => ['name' => $this->order->user->name],
        ], $locale);

        return $this->subject($rendered['subject'])
            ->view('emails.templated', ['body' => $rendered['body']]);
    }
}
