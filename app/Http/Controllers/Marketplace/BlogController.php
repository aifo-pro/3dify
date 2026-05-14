<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogPostBlock;
use App\Models\BlogTag;
use App\Support\BlogBlockPlainText;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $categorySlug = trim((string) $request->input('category', ''));

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
                'activeCategorySlug' => '',
                'blogAwaitingMigration' => true,
            ]);
        }

        $activeCategory = null;
        if ($categorySlug !== '' && Schema::hasTable('blog_categories')) {
            $activeCategory = BlogCategory::query()
                ->where('slug', $categorySlug)
                ->where('is_active', true)
                ->first();
        }

        $with = ['categories', 'tags', 'author'];
        if (BlogPost::hasBlogPostBlocksTable()) {
            $with[] = 'blocks';
        }

        $posts = BlogPost::query()
            ->with($with)
            ->published()
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q) {
                $like = '%'.$q.'%';
                $inner->where('title_uk', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('excerpt_uk', 'like', $like)
                    ->orWhere('excerpt_en', 'like', $like);
            }))
            ->when($activeCategory, fn ($query) => $query->whereHas(
                'categories',
                fn ($c) => $c->where('blog_categories.id', $activeCategory->id)
            ))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('marketplace.blog.index', [
            'posts' => $posts,
            'featured' => BlogPost::hasBlogPostBlocksTable()
                ? BlogPost::with(['categories', 'blocks'])->published()->featured()->latest('published_at')->first()
                : BlogPost::with(['categories'])->published()->featured()->latest('published_at')->first(),
            'categories' => BlogCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'tags' => BlogTag::where('is_active', true)->orderBy('name_uk')->limit(24)->get(),
            'popular' => BlogPost::hasBlogPostBlocksTable()
                ? BlogPost::with('blocks')->published()->orderByDesc('views')->limit(5)->get()
                : BlogPost::query()->published()->orderByDesc('views')->limit(5)->get(),
            'q' => $q,
            'activeCategorySlug' => $activeCategory?->slug ?? '',
            'blogAwaitingMigration' => false,
        ]);
    }

    public function show(BlogPost $post)
    {
        abort_unless($post->status === 'published' && $post->published_at && $post->published_at->lte(now()), 404);
        $post->increment('views');
        $post->load(['categories', 'tags', 'author']);

        $blocks = BlogPost::hasBlogPostBlocksTable()
            ? $post->blocks()->active()->orderBy('sort_order')->get()
            : collect();

        $headingIds = [];
        foreach ($blocks as $b) {
            if ($b->type === 'heading') {
                $headingIds[$b->id] = BlogBlockPlainText::headingFragmentId($b, $b->data ?? []);
            }
        }

        $toc = [];
        $locale = app()->getLocale();
        foreach ($blocks as $block) {
            if ($block->type !== 'heading') {
                continue;
            }
            $d = $block->data ?? [];
            $level = (int) ($d['level'] ?? 2);
            if (! in_array($level, [2, 3], true)) {
                $level = 2;
            }
            $text = $locale === 'en'
                ? trim((string) (($d['title_en'] ?? '') ?: ($d['title_uk'] ?? '')))
                : trim((string) (($d['title_uk'] ?? '') ?: ($d['title_en'] ?? '')));
            if ($text === '') {
                continue;
            }
            $toc[] = [
                'level' => $level,
                'text' => $text,
                'id' => $headingIds[$block->id] ?? BlogBlockPlainText::headingFragmentId($block, $d),
            ];
        }

        $faqJsonLd = $this->buildFaqJsonLd($blocks, $locale);

        $readMinutes = BlogBlockPlainText::readingMinutes(
            $blocks,
            $locale,
            trim(strip_tags($post->localized('excerpt')))
        );

        $relatedWith = ['categories'];
        if (BlogPost::hasBlogPostBlocksTable()) {
            $relatedWith[] = 'blocks';
        }

        $related = BlogPost::query()
            ->with($relatedWith)
            ->published()
            ->whereKeyNot($post->id)
            ->where(function ($query) use ($post) {
                $query->whereHas('tags', fn ($q) => $q->whereIn('blog_tags.id', $post->tags->pluck('id')))
                    ->orWhereHas('categories', fn ($q) => $q->whereIn('blog_categories.id', $post->categories->pluck('id')));
            })
            ->limit(3)
            ->get();

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
            'blocks' => $blocks,
            'hasActiveBlocks' => $blocks->isNotEmpty(),
            'toc' => $toc,
            'headingIds' => $headingIds,
            'faqJsonLd' => $faqJsonLd,
            'readMinutes' => $readMinutes,
        ]);
    }

    public function category(BlogCategory $category)
    {
        abort_unless($category->is_active, 404);

        return view('marketplace.blog.term', [
            'term' => $category,
            'type' => 'category',
            'posts' => $category->posts()->with(
                BlogPost::hasBlogPostBlocksTable()
                    ? ['categories', 'tags', 'blocks']
                    : ['categories', 'tags']
            )->published()->latest('published_at')->paginate(9),
        ]);
    }

    public function tag(BlogTag $tag)
    {
        abort_unless($tag->is_active, 404);

        return view('marketplace.blog.term', [
            'term' => $tag,
            'type' => 'tag',
            'posts' => $tag->posts()->with(
                BlogPost::hasBlogPostBlocksTable()
                    ? ['categories', 'tags', 'blocks']
                    : ['categories', 'tags']
            )->published()->latest('published_at')->paginate(9),
        ]);
    }

    /**
     * @param  Collection<int, BlogPostBlock>  $blocks
     */
    private function buildFaqJsonLd($blocks, string $locale): ?array
    {
        $mainEntity = [];
        foreach ($blocks as $block) {
            if ($block->type !== 'faq') {
                continue;
            }
            $items = $block->data['items'] ?? [];
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $q = $locale === 'en'
                    ? trim((string) (($item['question_en'] ?? '') ?: ($item['question_uk'] ?? '')))
                    : trim((string) (($item['question_uk'] ?? '') ?: ($item['question_en'] ?? '')));
                $a = $locale === 'en'
                    ? trim(strip_tags((string) (($item['answer_en'] ?? '') ?: ($item['answer_uk'] ?? ''))))
                    : trim(strip_tags((string) (($item['answer_uk'] ?? '') ?: ($item['answer_en'] ?? ''))));
                if ($q === '' || $a === '') {
                    continue;
                }
                $mainEntity[] = [
                    '@type' => 'Question',
                    'name' => $q,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $a,
                    ],
                ];
            }
        }

        if ($mainEntity === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];
    }
}
