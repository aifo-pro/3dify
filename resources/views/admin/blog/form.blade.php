@php
    $tinyMceConfig = [
        'menubar' => false,
        'branding' => false,
        'license_key' => 'gpl',
        'plugins' => 'link lists table code image autoresize',
        'toolbar' => 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | blockquote | link image table | code removeformat',
        'block_formats' => 'Paragraph=p;Heading 2=h2;Heading 3=h3;Preformatted=pre',
        'relative_urls' => false,
        'convert_urls' => true,
        'autoresize_bottom_margin' => 24,
        'content_style' => 'body{font-family:ui-sans-serif,system-ui,sans-serif;font-size:15px;background:#09090b;color:#e4e4e7;line-height:1.65}a{color:#34d399}img{max-width:100%;height:auto;border-radius:12px}table{width:100%;border-collapse:collapse}td,th{border:1px solid rgba(255,255,255,.12);padding:8px}',
    ];
    $blockLabels = [
        'heading' => __('blog.admin.block_heading'),
        'richtext' => __('blog.admin.block_richtext'),
        'image' => __('blog.admin.block_image'),
        'quote' => __('blog.admin.block_quote'),
        'divider' => __('blog.admin.block_divider'),
    ];
@endphp

<script>
    window.__blogBlocksEditorPayload = @json([
        'initial' => $contentBlocksDocument,
        'csrf' => csrf_token(),
        'uploadUrl' => route('admin.blog.upload'),
        'tinyDefaults' => $tinyMceConfig,
        'labels' => $blockLabels,
    ]);
</script>

<x-layouts.admin :title="$post->exists ? __('Редагувати статтю') : __('Нова стаття')" :description="__('SEO-ready blog post with bilingual content, schema and RSS.')" active="blog">
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-300/30 bg-rose-300/[0.08] px-4 py-3 text-sm text-rose-100">{{ $errors->first() }}</div>
    @endif

    <form
        id="blog-post-form"
        method="POST"
        action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}"
        enctype="multipart/form-data"
        class="grid gap-8 xl:grid-cols-[1fr_320px]"
        x-data="blogBlocksEditor()"
        @submit="submitForm($event)"
    >
        @csrf
        @if($post->exists) @method('PUT') @endif
        <input type="hidden" name="content_blocks" value="">

        <div class="space-y-8">
            <x-admin.section :title="__('Основне')">
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
                </div>
            </x-admin.section>

            <x-admin.section :title="__('blog.admin.cover_card_title')" :description="__('blog.admin.cover_card_hint')">
                <div class="overflow-hidden rounded-2xl border border-dashed border-emerald-400/25 bg-gradient-to-br from-emerald-400/[0.06] to-zinc-950/80 p-1">
                    <div class="rounded-xl bg-zinc-950/90 p-5 sm:p-6">
                        @if($post->cover_url)
                            <div class="relative mb-5 overflow-hidden rounded-xl border border-white/10">
                                <img src="{{ $post->cover_url }}" alt="" class="aspect-[1200/630] w-full object-cover">
                            </div>
                        @else
                            <div class="mb-5 grid aspect-[1200/630] place-items-center rounded-xl border border-white/10 bg-zinc-900/80 text-center">
                                <div>
                                    <svg class="mx-auto h-10 w-10 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                    <p class="mt-2 text-xs font-semibold text-zinc-500">{{ __('blog.admin.no_cover') }}</p>
                                </div>
                            </div>
                        @endif
                        <x-admin.field name="cover_image" type="file" :label="__('blog.admin.pick_image')" />
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <x-admin.field name="cover_alt_uk" :label="__('Cover alt UK')" :value="old('cover_alt_uk', $post->cover_alt_uk)" />
                            <x-admin.field name="cover_alt_en" :label="__('Cover alt EN')" :value="old('cover_alt_en', $post->cover_alt_en)" />
                        </div>
                    </div>
                </div>
            </x-admin.section>

            <x-admin.section :title="__('blog.admin.blocks_title')" :description="__('blog.admin.blocks_hint')">
                <div class="mb-4 flex flex-wrap gap-2">
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1.5 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/35 hover:bg-emerald-400/10 hover:text-emerald-100" @click="addBlock('heading')"><span class="text-emerald-400/90">+</span> {{ __('blog.admin.block_heading') }}</button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1.5 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/35 hover:bg-emerald-400/10 hover:text-emerald-100" @click="addBlock('richtext')"><span class="text-emerald-400/90">+</span> {{ __('blog.admin.block_richtext') }}</button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1.5 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/35 hover:bg-emerald-400/10 hover:text-emerald-100" @click="addBlock('image')"><span class="text-emerald-400/90">+</span> {{ __('blog.admin.block_image') }}</button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1.5 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/35 hover:bg-emerald-400/10 hover:text-emerald-100" @click="addBlock('quote')"><span class="text-emerald-400/90">+</span> {{ __('blog.admin.block_quote') }}</button>
                    <button type="button" class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1.5 text-xs font-bold text-zinc-300 transition hover:border-emerald-400/35 hover:bg-emerald-400/10 hover:text-emerald-100" @click="addBlock('divider')"><span class="text-emerald-400/90">+</span> {{ __('blog.admin.block_divider') }}</button>
                </div>

                <div class="space-y-4">
                    <template x-if="doc.blocks.length === 0">
                        <div class="rounded-2xl border border-white/10 bg-zinc-950/50 px-5 py-10 text-center text-sm text-zinc-500">
                            {{ __('blog.admin.add_first_block') }}
                        </div>
                    </template>

                    <template x-for="(block, index) in doc.blocks" :key="block.id">
                        <div class="overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-b from-white/[0.05] to-zinc-950/60 shadow-lg shadow-black/20">
                            <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-zinc-950/40 px-4 py-2.5">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-emerald-400/15 text-[11px] font-bold text-emerald-200" x-text="index + 1"></span>
                                    <span class="truncate text-xs font-bold uppercase tracking-[0.12em] text-zinc-400" x-text="blockLabel(block.type)"></span>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" class="rounded-lg border border-white/10 px-2 py-1 text-[11px] font-semibold text-zinc-400 hover:bg-white/5" @click="moveBlock(index, -1)" :disabled="index === 0">↑</button>
                                    <button type="button" class="rounded-lg border border-white/10 px-2 py-1 text-[11px] font-semibold text-zinc-400 hover:bg-white/5" @click="moveBlock(index, 1)" :disabled="index === doc.blocks.length - 1">↓</button>
                                    <button type="button" class="rounded-lg border border-rose-400/30 px-2 py-1 text-[11px] font-semibold text-rose-200 hover:bg-rose-400/10" @click="removeBlock(index)">{{ __('Видалити') }}</button>
                                </div>
                            </div>

                            <div class="p-4 sm:p-5">
                                <template x-if="block.type === 'heading'">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Заголовок UK') }}</label>
                                            <input type="text" x-model="block.uk.text" class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Заголовок EN') }}</label>
                                            <input type="text" x-model="block.en.text" class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm text-white">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">H2 / H3</label>
                                            <select x-model.number="block.uk.level" @change="block.en.level = block.uk.level" class="h-10 w-full max-w-xs rounded-xl border border-white/10 bg-zinc-950/70 px-3 text-sm text-white">
                                                <option :value="2">H2</option>
                                                <option :value="3">H3</option>
                                            </select>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="block.type === 'richtext'">
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Контент UK') }}</label>
                                            <textarea
                                                class="blog-block-mce min-h-[260px] w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"
                                                :id="'mce_' + block.id + '_uk'"
                                                x-init="$el.value = block.uk?.html ?? ''"
                                            ></textarea>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Контент EN') }}</label>
                                            <textarea
                                                class="blog-block-mce min-h-[260px] w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"
                                                :id="'mce_' + block.id + '_en'"
                                                x-init="$el.value = block.en?.html ?? ''"
                                            ></textarea>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="block.type === 'image'">
                                    <div class="grid gap-5 lg:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Зображення') }}</label>
                                            <div class="overflow-hidden rounded-xl border border-white/10 bg-zinc-900/50">
                                                <template x-if="block.path">
                                                    <img :src="imagePreviewUrl(block)" alt="" class="aspect-video w-full object-cover">
                                                </template>
                                                <template x-if="!block.path">
                                                    <div class="grid aspect-video place-items-center text-xs text-zinc-500">{{ __('blog.admin.image_not_uploaded') }}</div>
                                                </template>
                                            </div>
                                            <label class="mt-3 inline-flex cursor-pointer items-center gap-2 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-2 text-xs font-bold text-emerald-100 hover:bg-emerald-400/15">
                                                <span>{{ __('blog.admin.pick_image') }}</span>
                                                <input type="file" accept="image/jpeg,image/png,image/webp,image/jpg" class="hidden" @change="uploadForBlock(index, $event)">
                                            </label>
                                        </div>
                                        <div class="grid gap-3">
                                            <div>
                                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">Alt UK</label>
                                                <input type="text" x-model="block.uk.alt" class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm text-white">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">Alt EN</label>
                                                <input type="text" x-model="block.en.alt" class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm text-white">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Підпис UK') }}</label>
                                                <input type="text" x-model="block.uk.caption" class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm text-white">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Підпис EN') }}</label>
                                                <input type="text" x-model="block.en.caption" class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm text-white">
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="block.type === 'quote'">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Цитата UK') }}</label>
                                            <textarea x-model="block.uk.text" rows="4" class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm text-white"></textarea>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Цитата EN') }}</label>
                                            <textarea x-model="block.en.text" rows="4" class="w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 py-2 text-sm text-white"></textarea>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="block.type === 'divider'">
                                    <p class="text-sm text-zinc-500">{{ __('blog.admin.divider_hint') }}</p>
                                </template>
                            </div>
                        </div>
                    </template>
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

            <x-admin.section :title="__('blog.admin.og_section')" :description="__('blog.admin.og_section_hint')">
                <x-admin.field name="og_image" type="file" :label="__('OG image')" />
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
    @endpush
</x-layouts.admin>
