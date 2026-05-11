<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function build()
    {
        $rendered = app(EmailTemplateRenderer::class)->render('purchase_success', [
            'order' => [
                'number' => $this->order->number,
                'total' => number_format((float) $this->order->total, 2),
                'currency' => $this->order->currency,
                'url' => route('checkout.success', $this->order),
            ],
            'user' => [
                'name' => $this->order->user->name,
                'email' => $this->order->user->email,
            ],
        ], $this->order->user->locale);

        $html = view('emails.templated', ['body' => $rendered['body']])->render();

        return $this->subject($rendered['subject'])->html($html);
    }
}
