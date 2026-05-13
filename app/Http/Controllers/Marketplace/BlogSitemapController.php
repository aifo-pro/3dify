<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Schema;

class BlogSitemapController extends Controller
{
    public function __invoke()
    {
        $posts = Schema::hasTable('blog_posts')
            ? BlogPost::published()->indexable()->latest('updated_at')->get(['slug', 'updated_at'])
            : collect();

        return response()
            ->view('marketplace.blog.sitemap', compact('posts'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
