<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\License;
use App\Models\ModelFile;
use App\Models\Product;
use App\Models\Tag;
use App\Services\ModelFileValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request, ?Category $category = null)
    {
        $minPrice    = $request->filled('min_price') ? max(0, (float) $request->input('min_price')) : null;
        $maxPrice    = $request->filled('max_price') ? max(0, (float) $request->input('max_price')) : null;
        $licenseSlugs = (array) $request->input('license', []);
        $formatExt   = (array) $request->input('format', []);
        $categorySlug = $category?->slug ?: (string) $request->input('category', '');
        $minRating   = $request->filled('min_rating') ? max(1, min(5, (int) $request->input('min_rating'))) : null;
        $maxDim      = $request->filled('max_dim') ? max(1, (int) $request->input('max_dim')) : null;

        if (! $category && $request->filled('category') && count($request->query()) === 1) {
            $cleanCategory = Category::query()
                ->where('slug', $request->string('category')->toString())
                ->first();

            if ($cleanCategory) {
                return redirect()->route('categories.show', $cleanCategory, status: 301);
            }
        }

        $products = Product::query()
            ->with(['author', 'category', 'tags'])
            ->published()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('slug', 'like', $term)
                        ->orWhere('title', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($categorySlug !== '', fn ($query) => $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug)))
            ->when($request->boolean('free'), fn ($query) => $query->where('is_free', true))
            ->when($request->filled('tag'), fn ($query) => $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag)))
            ->when(! empty($licenseSlugs), fn ($query) => $query->whereHas('license', fn ($q) => $q->whereIn('slug', $licenseSlugs)))
            ->when(! empty($formatExt), fn ($query) => $query->whereHas('files', fn ($q) => $q->whereIn('extension', array_map('strtolower', $formatExt))))
            ->when($minPrice !== null, fn ($query) => $query->where('price', '>=', $minPrice))
            ->when($maxPrice !== null, fn ($query) => $query->where('price', '<=', $maxPrice))
            ->when($minRating !== null, fn ($q) => $q->whereHas('reviews', fn ($r) => $r->where('status', 'published'), '>=', 1)
                ->withAvg(['reviews as avg_rating' => fn ($r) => $r->where('status', 'published')], 'rating')
                ->havingRaw('avg_rating >= ?', [$minRating]))
            ->when($maxDim !== null, fn ($q) => $q->where(function ($inner) use ($maxDim) {
                $inner->whereNull('dim_x')->orWhere('dim_x', '<=', $maxDim);
            })->where(function ($inner) use ($maxDim) {
                $inner->whereNull('dim_y')->orWhere('dim_y', '<=', $maxDim);
            })->where(function ($inner) use ($maxDim) {
                $inner->whereNull('dim_z')->orWhere('dim_z', '<=', $maxDim);
            }))
            ->when($request->input('sort') === 'popular', fn ($query) => $query->orderByDesc('views_count'))
            ->when($request->input('sort') === 'downloads', fn ($query) => $query->orderByDesc('downloads_count'))
            ->when($request->input('sort') === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when($request->input('sort') === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->when($request->input('sort') === 'oldest', fn ($query) => $query->oldest('published_at'))
            ->when(! in_array($request->input('sort'), ['popular', 'downloads', 'price_asc', 'price_desc', 'oldest'], true), fn ($query) => $query->latest('published_at'))
            ->paginate(12)
            ->withQueryString();

        $availableFormats = \Illuminate\Support\Facades\DB::table('model_files')
            ->whereNotNull('extension')
            ->where('is_preview', false)
            ->select('extension', \Illuminate\Support\Facades\DB::raw('count(*) as c'))
            ->groupBy('extension')
            ->orderByDesc('c')
            ->limit(12)
            ->get();

        return view('marketplace.products.index', [
            'products' => $products,
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'tags' => Tag::query()->orderBy('slug')->get(),
            'licenses' => License::query()->orderBy('id')->get(),
            'availableFormats' => $availableFormats,
            'filters' => [
                'q' => (string) $request->input('q', ''),
                'category' => $categorySlug,
                'tag' => (string) $request->input('tag', ''),
                'free' => $request->boolean('free'),
                'license' => $licenseSlugs,
                'format' => array_map('strtolower', $formatExt),
                'min_price'  => $minPrice,
                'max_price'  => $maxPrice,
                'min_rating' => $minRating,
                'max_dim'    => $maxDim,
                'sort'       => (string) $request->input('sort', 'latest'),
            ],
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'published' || auth()->id() === $product->user_id || auth()->user()?->canModerate(), 404);
        try {
            $product->increment('views_count');

        // Daily aggregate for analytics. Insert-if-missing then increment — works on SQLite/MySQL alike.
            $today = now()->toDateString();
            $existing = DB::table('product_view_stats')->where('product_id', $product->id)->where('date', $today)->first();
            if ($existing) {
                DB::table('product_view_stats')->where('id', $existing->id)->update(['count' => $existing->count + 1, 'updated_at' => now()]);
            } else {
                DB::table('product_view_stats')->insert(['product_id' => $product->id, 'date' => $today, 'count' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Product view analytics failed', [
                'product_id' => $product->id,
                'message' => $e->getMessage(),
            ]);
        }

        $product->load(['author', 'category', 'license', 'commercialLicense', 'tags', 'files', 'previewFile']);

        // Approved makes for everyone + own pending makes for the uploader.
        $makes = $product->makes()
            ->with('user')
            ->where(function ($q) {
                $q->where('status', 'approved');
                if (auth()->check()) {
                    $q->orWhere(fn ($qq) => $qq->where('user_id', auth()->id()));
                }
            })
            ->latest()
            ->get();

        $comments = $product->comments()
            ->with('user', 'replies.user')
            ->whereNull('parent_id')
            ->where('status', 'published')
            ->latest()
            ->get();

        // Similar models: same category > same tags > latest published, exclude self.
        $similar = Product::query()
            ->with(['author', 'category'])
            ->published()
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->latest('published_at')
            ->take(4)
            ->get();

        if ($similar->count() < 4 && $product->tags->isNotEmpty()) {
            $tagIds = $product->tags->pluck('id');
            $tagBased = Product::query()
                ->with(['author', 'category'])
                ->published()
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $similar->pluck('id'))
                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                ->latest('published_at')
                ->take(4 - $similar->count())
                ->get();
            $similar = $similar->concat($tagBased);
        }

        if ($similar->count() < 4) {
            $filler = Product::query()
                ->with(['author', 'category'])
                ->published()
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $similar->pluck('id'))
                ->latest('published_at')
                ->take(4 - $similar->count())
                ->get();
            $similar = $similar->concat($filler);
        }

        return view('marketplace.products.show', [
            'product' => $product,
            'makes' => $makes,
            'comments' => $comments,
            'similar' => $similar,
        ]);
    }

    public function embed(Product $product)
    {
        abort_unless($product->status === 'published', 404);

        $product->load(['previewFile']);

        return view('marketplace.products.embed', [
            'product' => $product,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        return view('marketplace.author.form', [
            'product' => new Product(['currency' => 'UAH']),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'licenses' => License::query()->orderBy('slug')->get(),
            'tags' => Tag::query()->orderBy('slug')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $data = $this->validated($request);
        $uploadedPaths = [];
        try {
            $product = DB::transaction(function () use ($request, $data, &$uploadedPaths) {
                $payload = $this->productPayload($request, $data, null, $uploadedPaths);
                $product = Product::create($payload);
                $this->syncTags($product, $request);
                $this->storeFiles($product, $request, $uploadedPaths);

                return $product;
            });
        } catch (\Throwable $e) {
            foreach ($uploadedPaths as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }
            throw $e;
        }

        return redirect()->route('author.products.edit', $product)->with('status', 'Модель збережено та відправлено на модерацію.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        return view('marketplace.author.form', [
            'product' => $product->load('tags', 'files'),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'licenses' => License::query()->orderBy('slug')->get(),
            'tags' => Tag::query()->orderBy('slug')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $data = $this->validated($request);
        $uploadedPaths = [];
        try {
            DB::transaction(function () use ($request, $product, $data, &$uploadedPaths) {
                $product->update($this->productPayload($request, $data, $product, $uploadedPaths));
                $this->syncTags($product, $request);
                $this->storeFiles($product, $request, $uploadedPaths);
            });
        } catch (\Throwable $e) {
            foreach ($uploadedPaths as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }
            throw $e;
        }

        return back()->with('status', 'Модель оновлено.');
    }

    public function myProducts()
    {
        return view('marketplace.author.index', [
            'products' => auth()->user()->products()->with('category')->latest()->paginate(10),
        ]);
    }

    public function destroyFile(Product $product, ModelFile $file)
    {
        $this->authorize('update', $product);
        abort_unless($file->product_id === $product->id, 404);

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return back()->with('status', 'Файл видалено.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title_uk' => ['required', 'string', 'max:180'],
            'title_en' => ['nullable', 'string', 'max:180'],
            'short_description_uk' => ['nullable', 'string', 'max:500'],
            'description_uk' => ['required', 'string'],
            'description_en' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'license_id' => ['nullable', 'exists:licenses,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'currency' => ['required', Rule::in(['UAH'])],
            'commercial_license_enabled' => ['nullable'],
            'commercial_price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'commercial_license_id' => ['nullable', 'exists:licenses,id'],
            'commercial_license_description_uk' => ['nullable', 'string', 'max:1500'],
            'commercial_license_description_en' => ['nullable', 'string', 'max:1500'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'gallery' => ['nullable', 'array', 'max:24'],
            'gallery.*' => ['nullable', 'image', 'max:4096'],
            'gallery_remove' => ['nullable', 'array'],
            'gallery_remove.*' => ['integer', 'min:0'],
            'files.*' => ['nullable', 'file', 'max:102400'],
            'preview_file' => ['nullable', 'file', 'max:51200'],
            'tags' => ['array'],
            'tags.*' => ['exists:tags,id'],
            'dim_x' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'dim_y' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'dim_z' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'recommended_materials' => ['nullable', 'array'],
            'recommended_materials.*' => ['string', 'max:32'],
            'print_profile_settings' => ['nullable', 'array'],
            'print_profile_settings.*' => ['nullable', 'string', 'max:60'],
            'print_profile_file' => ['nullable', 'file', 'max:51200'],
            'print_profile_remove' => ['nullable', 'boolean'],
        ]);
    }

    private function productPayload(Request $request, array $data, ?Product $product = null, array &$uploadedPaths = []): array
    {
        $titleEn = $data['title_en'] ?? null;
        $shortDescriptionUk = $data['short_description_uk'] ?? '';
        $descriptionEn = $data['description_en'] ?? null;
        $cover = $product?->cover_path;
        if ($request->hasFile('cover')) {
            $cover = $request->file('cover')->store('covers', 'public');
            $uploadedPaths[] = ['public', $cover];
        }
        $gallery = array_values(array_filter((array) ($product?->gallery ?? [])));
        $removeGallery = collect((array) $request->input('gallery_remove', []))
            ->map(fn ($index) => (int) $index)
            ->unique()
            ->sortDesc()
            ->values();

        foreach ($removeGallery as $index) {
            if (array_key_exists($index, $gallery)) {
                Storage::disk('public')->delete($gallery[$index]);
                unset($gallery[$index]);
            }
        }

        $gallery = array_values($gallery);

        foreach ((array) $request->file('gallery', []) as $image) {
            $path = $image->store('gallery', 'public');
            $gallery[] = $path;
            $uploadedPaths[] = ['public', $path];
        }
        if (! $cover && count($gallery) > 0) {
            $cover = $gallery[0];
        }

        $printProfilePath = $product?->print_profile_path;
        $printProfileName = $product?->print_profile_name;
        if ($request->boolean('print_profile_remove') && $printProfilePath) {
            Storage::disk('private')->delete($printProfilePath);
            $printProfilePath = null;
            $printProfileName = null;
        }
        if ($request->hasFile('print_profile_file')) {
            $pp = $request->file('print_profile_file');
            $allowed = ['3mf', 'gcode', 'bgcode', 'zip'];
            $ext = strtolower($pp->getClientOriginalExtension());
            abort_unless(in_array($ext, $allowed, true), 422, 'Unsupported print profile type.');
            if ($printProfilePath) {
                Storage::disk('private')->delete($printProfilePath);
            }
            $printProfilePath = $pp->store('print-profiles/'.($product?->id ?? 'new'), 'private');
            $printProfileName = $pp->getClientOriginalName();
            $uploadedPaths[] = ['private', $printProfilePath];
        }

        $settings = array_filter((array) ($data['print_profile_settings'] ?? []), fn ($v) => filled($v));

        $commercialEnabled = $request->boolean('commercial_license_enabled');
        $commercialDescription = null;
        $descUk = trim((string) ($data['commercial_license_description_uk'] ?? ''));
        $descEn = trim((string) ($data['commercial_license_description_en'] ?? ''));
        if ($commercialEnabled && ($descUk !== '' || $descEn !== '')) {
            $commercialDescription = [
                'uk' => $descUk,
                'en' => $descEn ?: $descUk,
            ];
        }

        return [
            'user_id' => $product?->user_id ?? $request->user()->id,
            'category_id' => $data['category_id'] ?? null,
            'license_id' => $data['license_id'] ?? null,
            'slug' => $product?->slug ?? Str::slug($titleEn ?: $data['title_uk']).'-'.Str::lower(Str::random(6)),
            'title' => ['uk' => $data['title_uk'], 'en' => $titleEn ?: $data['title_uk']],
            'short_description' => ['uk' => $shortDescriptionUk, 'en' => $shortDescriptionUk],
            'description' => ['uk' => $data['description_uk'], 'en' => $descriptionEn ?: $data['description_uk']],
            'status' => $request->user()->canModerate() ? 'published' : 'pending',
            'price' => $data['price'],
            'personal_price' => $data['price'],
            'commercial_price' => $commercialEnabled ? ($data['commercial_price'] ?? null) : null,
            'commercial_license_enabled' => $commercialEnabled,
            'commercial_license_id' => $commercialEnabled ? ($data['commercial_license_id'] ?? null) : null,
            'commercial_license_description' => $commercialDescription,
            'currency' => 'UAH',
            'is_free' => (float) $data['price'] === 0.0,
            'cover_path' => $cover,
            'gallery' => array_values(array_unique($gallery)),
            'published_at' => $request->user()->canModerate() ? now() : $product?->published_at,
            'dim_x' => $data['dim_x'] ?? null,
            'dim_y' => $data['dim_y'] ?? null,
            'dim_z' => $data['dim_z'] ?? null,
            'recommended_materials' => $data['recommended_materials'] ?? null,
            'print_profile_path' => $printProfilePath,
            'print_profile_name' => $printProfileName,
            'print_profile_settings' => $settings ?: null,
        ];
    }

    private function syncTags(Product $product, Request $request): void
    {
        $product->tags()->sync($request->input('tags', []));
    }

    private function storeFiles(Product $product, Request $request, array &$uploadedPaths = []): void
    {
        $validator = app(ModelFileValidator::class);

        foreach ((array) $request->file('files', []) as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            abort_unless(in_array($extension, ModelFile::ALLOWED_EXTENSIONS, true), 422, 'Unsupported file type.');

            $validation = $validator->validate($file, $extension);
            abort_unless($validation['valid'], 422, implode(' ', $validation['errors']));

            $path = $file->store('models/'.$product->id, 'private');
            $uploadedPaths[] = ['private', $path];
            $product->files()->create([
                'type' => 'source',
                'disk' => 'private',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'validation_warnings' => $validation['warnings'] ?: null,
            ]);
        }

        if ($request->hasFile('preview_file')) {
            $file = $request->file('preview_file');
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedPreview = ['glb', 'gltf', 'obj', 'stl', 'gif', 'png', 'jpg', 'jpeg', 'webp'];
            abort_unless(in_array($extension, $allowedPreview, true), 422, 'Unsupported preview file type.');
            $path = $file->store('previews/'.$product->id, 'public');
            $uploadedPaths[] = ['public', $path];
            $product->files()->where('is_preview', true)->update(['is_preview' => false]);
            $product->files()->create([
                'type' => 'preview',
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'extension' => $extension,
                'size' => $file->getSize(),
                'is_preview' => true,
            ]);
        }
    }
}
