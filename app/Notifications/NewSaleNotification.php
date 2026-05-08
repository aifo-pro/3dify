<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewSaleNotification extends Notification
{
    use Queueable;

    public function __construct(public OrderItem $item, public Order $order) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'sale.created',
            'title' => __('Нова покупка моделі'),
            'message' => __('Вашу модель ":title" придбано за :price :currency.', [
                'title' => $this->item->product?->localized('title') ?? '—',
                'price' => number_format((float) $this->item->price, 2),
                'currency' => $this->item->currency,
            ]),
            'url' => route('dashboard'),
            'icon' => 'shopping-bag',
            'order_id' => $this->order->id,
            'item_id' => $this->item->id,
        ];
    }
}
