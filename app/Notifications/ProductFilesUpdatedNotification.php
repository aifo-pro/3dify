<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductFilesUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'product_updated',
            'product_id'   => $this->product->id,
            'product_slug' => $this->product->slug,
            'title'        => $this->product->localized('title'),
            'message'      => 'Автор оновив файли моделі "'.$this->product->localized('title').'".',
        ];
    }
}
