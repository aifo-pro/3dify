<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewProductFromAuthorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Product $product) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $author = $this->product->author;
        $title  = $this->product->localized('title');
        $price  = $this->product->is_free ? __('marketplace.free') : number_format((float) $this->product->price, 2).' '.$this->product->currency;

        return (new MailMessage)
            ->subject(__('notifications.new_product_subject', ['author' => $author?->displayName(), 'title' => $title]))
            ->greeting(__('notifications.new_product_greeting', ['name' => $notifiable->displayName()]))
            ->line(__('notifications.new_product_line', ['author' => $author?->displayName()]))
            ->line("**{$title}** — {$price}")
            ->action(__('notifications.new_product_action'), route('products.show', $this->product))
            ->line(__('notifications.unfollow_hint'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'new_product',
            'product_id' => $this->product->id,
            'product_slug' => $this->product->slug,
            'title'      => $this->product->localized('title'),
            'author_id'  => $this->product->user_id,
            'author_name' => $this->product->author?->displayName(),
            'cover_url'  => $this->product->cover_path
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->product->cover_path)
                : null,
        ];
    }
}
