<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if (! Schema::hasTable('blog_posts')) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 9, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('marketplace.blog.index', [
                'posts' => $emptyPaginator,
                'featured' => null,
                'categories' => collect(),
                'tags' => collect(),
                'popular' => collect(),
                'q' => $q,
                'blogAwaitingMigration' => true,
            ]);
        }

        $posts = BlogPost::query()
            ->with(['categories', 'tags', 'author'])
            ->published()
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q) {
                $like = '%'.$q.'%';
                $inner->where('title_uk', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('excerpt_uk', 'like', $like)
                    ->orWhere('excerpt_en', 'like', $like);
            }))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('marketplace.blog.index', [
            'posts' => $posts,
            'featured' => BlogPost::with(['categories'])->published()->featured()->latest('published_at')->first(),
            'categories' => BlogCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'tags' => BlogTag::where('is_active', true)->orderBy('name_uk')->limit(24)->get(),
            'popular' => BlogPost::published()->orderByDesc('views')->limit(5)->get(),
            'q' => $q,
            'blogAwaitingMigration' => false,
        ]);
    }

    public function show(BlogPost $post)
    {
        abort_unless($post->status === 'published' && $post->published_at && $post->published_at->lte(now()), 404);
        $post->increment('views');
        $post->load(['categories', 'tags', 'author']);

        $related = BlogPost::query()
            ->with(['categories'])
            ->published()
            ->whereKeyNot($post->id)
            ->where(function ($query) use ($post) {
                $query->whereHas('tags', fn ($q) => $q->whereIn('blog_tags.id', $post->tags->pluck('id')))
                    ->orWhereHas('categories', fn ($q) => $q->whereIn('blog_categories.id', $post->categories->pluck('id')));
            })
            ->limit(3)
            ->get();

        $contentHtml = $this->injectHeadingIds($post->localized_content);

        return view('marketplace.blog.show', [
            'post' => $post,
            'related' => $related,
            'toc' => $this->tocFromHtml($contentHtml),
            'contentHtml' => $contentHtml,
        ]);
    }

    public function category(BlogCategory $category)
    {
        abort_unless($category->is_active, 404);

        return view('marketplace.blog.term', [
            'term' => $category,
            'type' => 'category',
            'posts' => $category->posts()->with(['categories', 'tags'])->published()->latest('published_at')->paginate(9),
        ]);
    }

    public function tag(BlogTag $tag)
    {
        abort_unless($tag->is_active, 404);

        return view('marketplace.blog.term', [
            'term' => $tag,
            'type' => 'tag',
            'posts' => $tag->posts()->with(['categories', 'tags'])->published()->latest('published_at')->paginate(9),
        ]);
    }

    private function injectHeadingIds(string $html): string
    {
        $used = [];

        return preg_replace_callback('/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is', function (array $m) use (&$used) {
            $attrs = $m[2] ?? '';
            if (preg_match('/\bid\s*=\s*("|\')/', $attrs)) {
                return $m[0];
            }
            $text = trim(strip_tags($m[3]));
            $base = Str::slug($text) ?: 'section';
            $id = $base;
            $n = 1;
            while (isset($used[$id])) {
                $id = $base.'-'.(++$n);
            }
            $used[$id] = true;

            return '<h'.$m[1].' id="'.e($id).'"'.$attrs.'>'.$m[3].'</h'.$m[1].'>';
        }, $html) ?? $html;
    }

    /**
     * @return list<array{level:int, text:string, id:string}>
     */
    private function tocFromHtml(string $html): array
    {
        preg_match_all('/<h([23])\s[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER);

        return collect($matches)->map(fn ($match) => [
            'level' => (int) $match[1],
            'text' => trim(strip_tags($match[3])),
            'id' => $match[2],
        ])->filter(fn ($item) => $item['text'] !== '')->values()->all();
    }
}
