<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\BlogBlockCompiler;
use App\Services\BlogContentSanitizer;
use App\Services\BlogImageService;
use App\Services\BlogNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::with(['author', 'categories'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $q->where(fn ($inner) => $inner->where('title_uk', 'like', $term)->orWhere('title_en', 'like', $term)->orWhere('slug', 'like', $term));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.blog.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.blog.form', [
            'post' => new BlogPost(['status' => 'draft', 'allow_index' => true]),
            'categories' => BlogCategory::orderBy('sort_order')->get(),
            'tags' => BlogTag::orderBy('name_uk')->get(),
            'contentBlocksDocument' => app(BlogBlockCompiler::class)->defaultDocumentFromLegacy(null, null),
        ]);
    }

    public function store(Request $request, BlogContentSanitizer $sanitizer, BlogImageService $images, BlogNotificationService $notifications)
    {
        $data = $this->validated($request);
        $data['user_id'] = auth()->id();
        $data = $this->prepareData($data, $request, $sanitizer, $images);
        $post = BlogPost::create($data);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        $notifications->sendPublished($post->fresh());

        return redirect()->route('admin.blog.edit', $post)->with('status', __('blog.admin.post_created'));
    }

    public function edit(BlogPost $post)
    {
        $post->load(['categories', 'tags']);

        $doc = $post->content_blocks;
        if (! is_array($doc) || ! isset($doc['blocks'])) {
            $doc = app(BlogBlockCompiler::class)->defaultDocumentFromLegacy($post->content_uk, $post->content_en);
        }

        return view('admin.blog.form', [
            'post' => $post,
            'categories' => BlogCategory::orderBy('sort_order')->get(),
            'tags' => BlogTag::orderBy('name_uk')->get(),
            'contentBlocksDocument' => $doc,
        ]);
    }

    public function update(Request $request, BlogPost $post, BlogContentSanitizer $sanitizer, BlogImageService $images, BlogNotificationService $notifications)
    {
        $prevStatus = $post->status;
        $data = $this->prepareData($this->validated($request, $post), $request, $sanitizer, $images);
        if ($prevStatus === 'published' && ($data['status'] ?? '') !== 'published') {
            $data['notification_sent_at'] = null;
        }
        $post->update($data);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        $notifications->sendPublished($post->fresh());

        return redirect()->route('admin.blog.edit', $post)->with('status', __('blog.admin.post_updated'));
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();

        return redirect()->route('admin.blog.index')->with('status', __('blog.admin.post_deleted'));
    }

    public function upload(Request $request, BlogImageService $images)
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $images->storeContentImage($data['image']);

        return response()->json([
            'url' => $images->publicUrl($path),
            'path' => $path,
        ]);
    }

    private function validated(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'title_uk' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('blog_posts', 'slug')->ignore($post?->id)],
            'excerpt_uk' => ['nullable', 'string'],
            'excerpt_en' => ['nullable', 'string'],
            'content_blocks' => ['required', 'string', 'max:600000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_alt_uk' => ['nullable', 'string', 'max:255'],
            'cover_alt_en' => ['nullable', 'string', 'max:255'],
            'seo_title_uk' => ['nullable', 'string', 'max:255'],
            'seo_title_en' => ['nullable', 'string', 'max:255'],
            'seo_description_uk' => ['nullable', 'string', 'max:500'],
            'seo_description_en' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(BlogPost::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'allow_index' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:blog_categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:blog_tags,id'],
        ]);
    }

    private function prepareData(array $data, Request $request, BlogContentSanitizer $sanitizer, BlogImageService $images): array
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['title_en'] ?: Str::transliterate($data['title_uk']));

        $document = json_decode($data['content_blocks'] ?? 'null', true);
        if (! is_array($document)) {
            throw ValidationException::withMessages(['content_blocks' => [__('Некоректний JSON конструктора.')]]);
        }
        $this->assertValidBlockDocument($document);
        $compiled = app(BlogBlockCompiler::class)->compile($document);
        $data['content_blocks'] = $document;
        $data['content_uk'] = $sanitizer->clean($compiled['uk'] ?: null);
        $data['content_en'] = $sanitizer->clean($compiled['en'] ?: null);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['allow_index'] = $request->boolean('allow_index', true);
        $data['published_at'] = $data['published_at'] ?: ($data['status'] === 'published' ? now() : null);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $images->storeCover($request->file('cover_image'));
        } else {
            unset($data['cover_image']);
        }

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $images->storeOg($request->file('og_image'));
        } else {
            unset($data['og_image']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function assertValidBlockDocument(array $document): void
    {
        $blocks = $document['blocks'] ?? null;
        if (! is_array($blocks)) {
            throw ValidationException::withMessages(['content_blocks' => [__('Відсутній масив blocks.')]]);
        }
        if (count($blocks) > 60) {
            throw ValidationException::withMessages(['content_blocks' => [__('Забагато блоків (макс. 60).')]]);
        }

        $allowed = ['heading', 'richtext', 'image', 'quote', 'divider'];

        foreach ($blocks as $i => $block) {
            if (! is_array($block)) {
                throw ValidationException::withMessages(['content_blocks' => [__('Некоректний блок #:idx.', ['idx' => $i])]]);
            }
            $type = (string) ($block['type'] ?? '');
            if (! in_array($type, $allowed, true)) {
                throw ValidationException::withMessages(['content_blocks' => [__('Недозволений тип блоку: :type', ['type' => $type])]]);
            }
            if ($type === 'image') {
                $path = (string) ($block['path'] ?? '');
                if ($path !== '' && (str_contains($path, '..') || ! preg_match('#^blog/#', $path))) {
                    throw ValidationException::withMessages(['content_blocks' => [__('Некоректний шлях зображення.')]]);
                }
            }
        }
    }
}
