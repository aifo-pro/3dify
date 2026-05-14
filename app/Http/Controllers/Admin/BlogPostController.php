<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\BlogContentSanitizer;
use App\Services\BlogImageService;
use App\Services\BlogNotificationService;
use App\Services\BlogPostBlockService;
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
            'initialBlocksJson' => '[]',
            'blogBlocksMigrationMissing' => ! BlogPost::hasBlogPostBlocksTable(),
        ]);
    }

    public function store(
        Request $request,
        BlogContentSanitizer $sanitizer,
        BlogImageService $images,
        BlogNotificationService $notifications,
        BlogPostBlockService $blockService,
    ) {
        $validated = $this->validated($request);
        $blocks = $this->decodeBlocksJson($validated['blocks_json']);
        unset($validated['blocks_json']);

        $data = $this->preparePostData($validated, $request, $images);
        $data['user_id'] = auth()->id();
        $post = BlogPost::create($data);
        $blockService->syncBlocks($post, $blocks, $sanitizer);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        $notifications->sendPublished($post->fresh(BlogPost::hasBlogPostBlocksTable() ? ['blocks'] : []));

        return redirect()->route('admin.blog.edit', $post)->with('status', __('blog.admin.post_created'));
    }

    public function edit(BlogPost $post)
    {
        $post->load(['categories', 'tags']);

        $initial = [];
        if (BlogPost::hasBlogPostBlocksTable()) {
            $post->load('blocks');
            $initial = $post->blocks->map(fn ($b) => [
                'id' => $b->id,
                'type' => $b->type,
                'is_active' => $b->is_active,
                'data' => $b->data,
            ])->values()->all();
        }

        return view('admin.blog.form', [
            'post' => $post,
            'categories' => BlogCategory::orderBy('sort_order')->get(),
            'tags' => BlogTag::orderBy('name_uk')->get(),
            'initialBlocksJson' => $this->safeJsonEncode($initial),
            'blogBlocksMigrationMissing' => ! BlogPost::hasBlogPostBlocksTable(),
        ]);
    }

    public function update(
        Request $request,
        BlogPost $post,
        BlogContentSanitizer $sanitizer,
        BlogImageService $images,
        BlogNotificationService $notifications,
        BlogPostBlockService $blockService,
    ) {
        $prevStatus = $post->status;
        $validated = $this->validated($request, $post);
        $blocks = $this->decodeBlocksJson($validated['blocks_json']);
        unset($validated['blocks_json']);

        $data = $this->preparePostData($validated, $request, $images);
        if ($prevStatus === 'published' && ($data['status'] ?? '') !== 'published') {
            $data['notification_sent_at'] = null;
        }
        $post->update($data);
        $blockService->syncBlocks($post, $blocks, $sanitizer);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        $notifications->sendPublished($post->fresh(BlogPost::hasBlogPostBlocksTable() ? ['blocks'] : []));

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
            'blocks_json' => ['required', 'string', 'max:2000000'],
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

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeBlocksJson(string $json): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw ValidationException::withMessages(['blocks_json' => [__('blog.admin.blocks_json_invalid')]]);
        }

        return $decoded;
    }

    private function preparePostData(array $data, Request $request, BlogImageService $images): array
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['title_en'] ?: Str::transliterate($data['title_uk']));
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
     * @param  array<mixed>  $payload
     */
    private function safeJsonEncode(array $payload): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
        if (defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
            $flags |= JSON_PARTIAL_OUTPUT_ON_ERROR;
        }
        $json = json_encode($payload, $flags);

        return $json === false ? '[]' : $json;
    }
}
