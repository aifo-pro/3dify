<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\ProductComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification
{
    use Queueable;

    public function __construct(public Product $product, public ProductComment $comment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'comment.created',
            'title' => __('Новий коментар'),
            'message' => __(':name прокоментував модель ":title".', [
                'name' => $this->comment->user->name ?? '—',
                'title' => $this->product->localized('title'),
            ]),
            'url' => route('products.show', $this->product).'#comments',
            'icon' => 'message-circle',
            'product_id' => $this->product->id,
            'comment_id' => $this->comment->id,
        ];
    }
}
