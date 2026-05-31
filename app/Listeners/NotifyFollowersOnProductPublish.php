<?php

namespace App\Listeners;

use App\Events\ProductPublished;
use App\Notifications\NewProductFromAuthorNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyFollowersOnProductPublish implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(ProductPublished $event): void
    {
        $product = $event->product->loadMissing('author.followers');
        $author  = $product->author;

        if (! $author) {
            return;
        }

        $author->followers()
            ->where('follower_id', '!=', $author->id)
            ->lazyById(100)
            ->each(fn ($follower) => $follower->notify(new NewProductFromAuthorNotification($product)));
    }
}
