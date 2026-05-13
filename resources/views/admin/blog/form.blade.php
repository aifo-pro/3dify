<x-layouts.admin :title="$post->exists ? __('Редагувати статтю') : __('Нова стаття')" :description="__('SEO-ready blog post with bilingual content, schema and RSS.')" active="blog">
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-300/30 bg-rose-300/[0.08] px-4 py-3 text-sm text-rose-100">{{ $errors->first() }}</div>
    @endif

    <form id="blog-post-form" method="POST" action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1fr_340px]">
        @csrf
        @if($post->exists) @method('PUT') @endif

        <div class="space-y-6">
            <x-admin.section :title="__('Основний контент')">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-admin.field name="title_uk" :label="__('Title UK')" :value="old('title_uk', $post->title_uk)" required />
                    <x-admin.field name="title_en" :label="__('Title EN')" :value="old('title_en', $post->title_en)" />
                    <x-admin.field name="slug" :label="__('Slug')" :value="old('slug', $post->slug)" class="md:col-span-2" />
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Excerpt UK') }}</label>
                        <textarea name="excerpt_uk" rows="3" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white">{{ old('excerpt_uk', $post->excerpt_uk) }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Excerpt EN') }}</label>
                        <textarea name="excerpt_en" rows="3" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white">{{ old('excerpt_en', $post->excerpt_en) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Content UK') }}</label>
                        <textarea id="content_uk" name="content_uk" rows="14" class="w-full rounded-2xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-white">{{ old('content_uk', $post->content_uk) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Content EN') }}</label>
                        <textarea id="content_en" name="content_en" rows="14" class="w-full rounded-2xl border border-white/10 bg-zinc-950/60 px-4 py-3 text-sm text-white">{{ old('content_en', $post->content_en) }}</textarea>
                    </div>
                </div>
            </x-admin.section>

            <x-admin.section :title="__('SEO')">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-admin.field name="seo_title_uk" :label="__('SEO title UK')" :value="old('seo_title_uk', $post->seo_title_uk)" />
                    <x-admin.field name="seo_title_en" :label="__('SEO title EN')" :value="old('seo_title_en', $post->seo_title_en)" />
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('SEO description UK') }}</label>
                        <textarea name="seo_description_uk" rows="3" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white">{{ old('seo_description_uk', $post->seo_description_uk) }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('SEO description EN') }}</label>
                        <textarea name="seo_description_en" rows="3" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white">{{ old('seo_description_en', $post->seo_description_en) }}</textarea>
                    </div>
                    <x-admin.field name="seo_keywords" :label="__('SEO keywords')" :value="old('seo_keywords', $post->seo_keywords)" class="md:col-span-2" />
                </div>
            </x-admin.section>
        </div>

        <aside class="space-y-6">
            <x-admin.section :title="__('Публікація')">
                <div class="space-y-4">
                    <select name="status" class="h-11 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                        @foreach(\App\Models\BlogPost::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $post->status) === $status)>{{ $status }}</option>@endforeach
                    </select>
                    <x-admin.field name="published_at" type="datetime-local" :label="__('Published at')" :value="old('published_at', optional($post->published_at)->format('Y-m-d\TH:i'))" />
                    <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured)) class="rounded border-white/20 bg-zinc-950 text-emerald-400"> {{ __('Вибрана стаття') }}</label>
                    <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="allow_index" value="1" @checked(old('allow_index', $post->allow_index ?? true)) class="rounded border-white/20 bg-zinc-950 text-emerald-400"> {{ __('Дозволити індексацію') }}</label>
                </div>
            </x-admin.section>

            <x-admin.section :title="__('Медіа')">
                <div class="space-y-4">
                    @if($post->cover_url)<img src="{{ $post->cover_url }}" alt="" class="aspect-[1200/630] w-full rounded-2xl border border-white/10 object-cover">@endif
                    <x-admin.field name="cover_image" type="file" :label="__('Cover image')" />
                    <x-admin.field name="cover_alt_uk" :label="__('Cover alt UK')" :value="old('cover_alt_uk', $post->cover_alt_uk)" />
                    <x-admin.field name="cover_alt_en" :label="__('Cover alt EN')" :value="old('cover_alt_en', $post->cover_alt_en)" />
                    <x-admin.field name="og_image" type="file" :label="__('OG image')" />
                </div>
            </x-admin.section>

            <x-admin.section :title="__('Таксономія')">
                <div class="space-y-5">
                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Категорії') }}</p>
                        <div class="grid gap-2">
                            @foreach($categories as $category)
                                <label class="flex items-center gap-2 text-sm text-zinc-300"><input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked(in_array($category->id, old('categories', $post->categories->pluck('id')->all() ?? []))) class="rounded border-white/20 bg-zinc-950 text-emerald-400"> {{ $category->localized('name') }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Теги') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <label class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs text-zinc-300"><input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, old('tags', $post->tags->pluck('id')->all() ?? []))) class="mr-1 rounded border-white/20 bg-zinc-950 text-emerald-400"> {{ $tag->localized() }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-admin.section>

            <button class="w-full rounded-2xl bg-emerald-400 px-5 py-4 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-300">{{ __('Зберегти статтю') }}</button>
        </aside>
    </form>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tinymce@7.4.0/tinymce.min.js"></script>
        <script>
            (function () {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const uploadUrl = @json(route('admin.blog.upload'));
                function boot() {
                    if (!window.tinymce) {
                        return void setTimeout(boot, 40);
                    }
                    tinymce.init({
                        selector: '#content_uk, #content_en',
                        height: 520,
                        menubar: false,
                        branding: false,
                        license_key: 'gpl',
                        plugins: 'link lists table code image media autoresize',
                        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | blockquote | link image media table | code | removeformat',
                        block_formats: 'Paragraph=p;Heading 2=h2;Heading 3=h3;Preformatted=pre',
                        relative_urls: false,
                        convert_urls: true,
                        autoresize_bottom_margin: 24,
                        content_style: 'body{font-family:ui-sans-serif,system-ui,sans-serif;font-size:15px;background:#09090b;color:#e4e4e7;line-height:1.65}a{color:#34d399}img{max-width:100%;height:auto;border-radius:12px}table{width:100%;border-collapse:collapse}td,th{border:1px solid rgba(255,255,255,.12);padding:8px}iframe{max-width:100%;border-radius:12px}',
                        images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
                            const form = new FormData();
                            form.append('image', blobInfo.blob(), blobInfo.filename());
                            fetch(uploadUrl, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrf || '', 'Accept': 'application/json' },
                                body: form,
                                credentials: 'same-origin',
                            })
                                .then((r) => r.json())
                                .then((j) => (j.url ? resolve(j.url) : reject(new Error('Upload failed'))))
                                .catch(() => reject(new Error('Upload failed')));
                        }),
                        setup: (editor) => {
                            editor.on('change input undo redo', () => editor.save());
                        },
                    });
                }
                boot();
                document.getElementById('blog-post-form')?.addEventListener('submit', () => {
                    if (window.tinymce) {
                        tinymce.triggerSave();
                    }
                });
            })();
        </script>
    @endpush
</x-layouts.admin>
