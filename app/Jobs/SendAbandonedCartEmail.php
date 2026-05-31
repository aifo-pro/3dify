<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $orderId) {}

    public function handle(): void
    {
        $order = Order::query()
            ->where('id', $this->orderId)
            ->where('status', 'pending')
            ->with(['user', 'items.product'])
            ->first();

        if (! $order || ! $order->user?->email) {
            return;
        }

        // Don't send if order is older than 24h (probably lost cause)
        if ($order->created_at->lt(now()->subHours(24))) {
            return;
        }

        $product = $order->items->first()?->product;
        if (! $product) {
            return;
        }

        $checkoutUrl = route('products.show', $product);
        $user        = $order->user;

        Mail::to($user->email)->queue(
            new \App\Mail\AbandonedCartMail($order, $product, $user, $checkoutUrl)
        );
    }
}
