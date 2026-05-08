<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SaleNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function build()
    {
        $rendered = app(EmailTemplateRenderer::class)->render('sale', [
            'order' => [
                'number' => $this->order->number,
                'total' => number_format((float) $this->order->total, 2),
                'currency' => $this->order->currency,
            ],
            'user' => ['name' => $this->order->user->name],
        ]);

        return $this->subject($rendered['subject'])
            ->view('emails.templated', ['body' => $rendered['body']]);
    }
}
