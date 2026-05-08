<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification
{
    use Queueable;

    public function __construct(public Product $product, public ProductReview $review) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'review.created',
            'title' => __('Новий відгук'),
            'message' => __(':name оцінив модель ":title" на :rating з 5.', [
                'name' => $this->review->user->name ?? '—',
                'title' => $this->product->localized('title'),
                'rating' => $this->review->rating,
            ]),
            'url' => route('products.show', $this->product).'#reviews',
            'icon' => 'star',
            'product_id' => $this->product->id,
            'review_id' => $this->review->id,
        ];
    }
}
