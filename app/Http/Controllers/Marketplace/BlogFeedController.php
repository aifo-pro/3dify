<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Schema;

class BlogFeedController extends Controller
{
    public function __invoke()
    {
        $posts = Schema::hasTable('blog_posts')
            ? BlogPost::with('author')->published()->latest('published_at')->limit(20)->get()
            : collect();

        return response()
            ->view('marketplace.blog.feed', compact('posts'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
