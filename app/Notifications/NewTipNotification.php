<?php

namespace App\Notifications;

use App\Models\Tip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewTipNotification extends Notification
{
    use Queueable;

    public function __construct(public Tip $tip) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $this->tip->loadMissing('product');

        return [
            'type' => 'tip.received',
            'title' => __('Нова подяка'),
            'message' => __('Для моделі «:title» отримано :amount грн.', [
                'title' => $this->tip->product?->localized('title') ?? '—',
                'amount' => number_format((float) $this->tip->amount, 2),
            ]),
            'url' => $this->tip->product
                ? route('products.show', $this->tip->product)
                : route('dashboard'),
            'icon' => 'heart',
            'tip_id' => $this->tip->id,
        ];
    }
}
