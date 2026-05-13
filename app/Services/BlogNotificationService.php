<?php

namespace App\Services;

use App\Mail\RenderedTemplateMail;
use App\Models\BlogPost;
use App\Models\BlogSubscriber;
use Illuminate\Support\Facades\Mail;

class BlogNotificationService
{
    public function sendPublished(BlogPost $post): void
    {
        if ($post->status !== 'published' || $post->notification_sent_at) {
            return;
        }

        if (! $post->published_at || $post->published_at->isFuture()) {
            return;
        }

        BlogSubscriber::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->chunk(100, function ($subscribers) use ($post) {
                foreach ($subscribers as $subscriber) {
                    $locale = in_array($subscriber->locale, ['uk', 'en'], true) ? $subscriber->locale : 'uk';
                    $rendered = app(EmailTemplateRenderer::class)->render('blog_post_published', [
                        'user' => [
                            'name' => $subscriber->email,
                            'email' => $subscriber->email,
                        ],
                        'post' => [
                            'title' => $post->localized('title', $locale),
                            'excerpt' => $post->localized('excerpt', $locale),
                            'url' => $post->url,
                            'cover' => $post->cover_url ?: $post->og_image_url ?: '',
                        ],
                        'link' => route('blog.unsubscribe', $subscriber->unsubscribe_token),
                    ], $locale);

                    Mail::to($subscriber->email)->send(new RenderedTemplateMail($rendered['subject'], $rendered['body']));
                }
            });

        $post->forceFill(['notification_sent_at' => now()])->saveQuietly();
    }
}
