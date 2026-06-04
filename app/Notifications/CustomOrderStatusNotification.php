<?php

namespace App\Notifications;

use App\Models\CustomOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomOrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CustomOrder $order,
        public readonly string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->order->title;
        $url   = route('custom-orders.show', $this->order);

        return (new MailMessage)
            ->subject(__('custom_orders.notify.'.$this->status.'.subject', ['number' => $this->order->number]))
            ->greeting(__('custom_orders.notify.greeting', ['name' => $notifiable->displayName()]))
            ->line(__('custom_orders.notify.'.$this->status.'.line', ['title' => $title]))
            ->action(__('custom_orders.notify.action'), $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'custom_order_status',
            'custom_order_id' => $this->order->id,
            'number'          => $this->order->number,
            'status'          => $this->status,
            'title'           => $this->order->title,
            'message'         => __('custom_orders.notify.'.$this->status.'.line', ['title' => $this->order->title]),
        ];
    }
}
