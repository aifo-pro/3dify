@php
    use App\Services\BlogPostBlockService;
    $seedRaw = old('blocks_json', $initialBlocksJson ?? '[]');
    $seedArr = json_decode(is_string($seedRaw) ? $seedRaw : '[]', true) ?: [];
    $typeLabels = __('blog.admin.block_types');
    $blogPostBlocksCfg = [
        'initialBlocks' => $seedArr,
        'uploadUrl' => route('admin.blog.upload'),
        'csrf' => csrf_token(),
        'typeLabels' => $typeLabels,
    ];
    $blogPostBlocksCfgJson = json_encode(
        $blogPostBlocksCfg,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    ) ?: '{}';
@endphp
<script>
    window.__blogPostBlocksCfg = {!! $blogPostBlocksCfgJson !!};
</script>

<x-layouts.admin
    :title="$post->exists ? __('Редагувати статтю') : __('Нова стаття')"
    :description="__('blog.admin.blocks_hint')"
    active="blog"
    :load-tiny-mce="false"
>
    @if($blogBlocksMigrationMissing ?? false)
        <div class="mb-6 rounded-2xl border border-amber-300/35 bg-amber-400/[0.10] px-4 py-3 text-sm font-semibold text-amber-100">{{ __('blog.admin.blocks_table_missing') }}</div>
    @endif
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-300/30 bg-rose-300/[0.08] px-4 py-3 text-sm text-rose-100">{{ $errors->first() }}</div>
    @endif

    <div x-data="blogPostBlocksEditor()">
    <form
        id="blog-post-form"
        method="POST"
        action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}"
        enctype="multipart/form-data"
        class="grid gap-8 xl:grid-cols-[1fr_320px]"
        @submit="prepareBlocksSubmit()"
    >
        @csrf
        @if($post->exists) @method('PUT') @endif
        <input type="hidden" name="blocks_json" id="blocks_json" value="">

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
                <div class="overflow-hidden rounded-3xl border border-dashed border-emerald-400/25 bg-gradient-to-br from-emerald-400/[0.06] to-zinc-950/80 p-1">
                    <div class="rounded-2xl bg-zinc-950/90 p-5 sm:p-6">
                        @if($post->cover_url)
                            <div class="relative mb-5 overflow-hidden rounded-2xl border border-white/10">
                                <img src="{{ $post->cover_url }}" alt="" class="aspect-[1200/630] w-full object-cover">
                            </div>
                        @else
                            <div class="mb-5 grid aspect-[1200/630] place-items-center rounded-2xl border border-white/10 bg-zinc-900/80 text-center">
                                <p class="text-xs font-semibold text-zinc-500">{{ __('blog.admin.no_cover') }}</p>
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
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" @click="showPicker = true" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-300">
                        + {{ __('blog.admin.add_block') }}
                    </button>
                </div>

                <div class="mt-6 space-y-4">
                    <template x-for="(block, index) in blocks" :key="block._key">
                        <div class="overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-b from-white/[0.06] to-zinc-950/70 shadow-xl shadow-black/25" :class="!block.is_active && 'opacity-50'">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 bg-zinc-950/50 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-emerald-400/15 text-[11px] font-bold text-emerald-200" x-text="index + 1"></span>
                                    <span class="truncate text-xs font-bold uppercase tracking-[0.12em] text-zinc-400" x-text="typeLabel(block.type)"></span>
                                    <span x-show="!block.is_active" class="rounded-full border border-amber-400/30 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-200">hidden</span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <button type="button" class="rounded-xl border border-white/10 px-2 py-1 text-[11px] text-zinc-400 hover:bg-white/5" @click="move(index, -1)" :disabled="index === 0">↑</button>
                                    <button type="button" class="rounded-xl border border-white/10 px-2 py-1 text-[11px] text-zinc-400 hover:bg-white/5" @click="move(index, 1)" :disabled="index === blocks.length - 1">↓</button>
                                    <button type="button" class="rounded-xl border border-white/10 px-2 py-1 text-[11px] text-zinc-300 hover:bg-white/5" @click="toggleActive(index)" x-text="block.is_active ? 'hide' : 'show'"></button>
                                    <button type="button" class="rounded-xl border border-rose-400/30 px-2 py-1 text-[11px] text-rose-200 hover:bg-rose-400/10" @click="removeBlock(index)">{{ __('Видалити') }}</button>
                                </div>
                            </div>
                            <div class="space-y-4 p-4 sm:p-5">
                                <template x-if="block.type === 'heading'">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div><label class="mb-1 block text-[10px] font-bold uppercase text-zinc-500">H2/H3</label>
                                            <select x-model.number="block.data.level" class="h-10 w-full max-w-xs rounded-xl border border-white/10 bg-zinc-950/80 px-3 text-sm text-white"><option :value="2">H2</option><option :value="3">H3</option></select>
                                        </div>
                                        <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                                            <div><label class="mb-1 block text-[10px] font-bold uppercase text-zinc-500">Title UK</label><input type="text" x-model="block.data.title_uk" class="w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"></div>
                                            <div><label class="mb-1 block text-[10px] font-bold uppercase text-zinc-500">Title EN</label><input type="text" x-model="block.data.title_en" class="w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"></div>
                                        </div>
                                        <div class="sm:col-span-2"><label class="mb-1 block text-[10px] font-bold uppercase text-zinc-500">Anchor (optional)</label><input type="text" x-model="block.data.anchor" class="w-full max-w-md rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="my-section"></div>
                                    </div>
                                </template>
                                <template x-if="block.type === 'paragraph'">
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div><label class="mb-1 block text-[10px] font-bold uppercase text-zinc-500">HTML UK</label><textarea x-model="block.data.text_uk" rows="8" class="min-h-[180px] w-full rounded-2xl border border-white/10 bg-zinc-950/90 px-3 py-2 font-mono text-sm text-zinc-100"></textarea></div>
                                        <div><label class="mb-1 block text-[10px] font-bold uppercase text-zinc-500">HTML EN</label><textarea x-model="block.data.text_en" rows="8" class="min-h-[180px] w-full rounded-2xl border border-white/10 bg-zinc-950/90 px-3 py-2 font-mono text-sm text-zinc-100"></textarea></div>
                                    </div>
                                </template>
                                <template x-if="block.type === 'image' || block.type === 'image_text'">
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <div>
                                            <label class="mb-2 block text-[10px] font-bold uppercase text-zinc-500">{{ __('blog.admin.pick_image') }}</label>
                                            <div class="mb-3 overflow-hidden rounded-2xl border border-white/10 bg-zinc-900/50">
                                                <template x-if="block.data.path"><img :src="'/storage/' + block.data.path.replace(/^\/+/, '')" class="aspect-video w-full object-cover" alt=""></template>
                                                <template x-if="!block.data.path"><div class="grid aspect-video place-items-center text-xs text-zinc-500">—</div></template>
                                            </div>
                                            <label class="inline-flex cursor-pointer rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-2 text-xs font-bold text-emerald-100">
                                                Upload
                                                <input type="file" class="hidden" accept="image/jpeg,image/png,image/webp" @change="uploadForBlock(block, $event)">
                                            </label>
                                            <input type="text" x-model="block.data.path" class="mt-2 w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-xs text-zinc-400" placeholder="blog/... path">
                                        </div>
                                        <div class="space-y-3">
                                            <template x-if="block.type === 'image_text'">
                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <input type="text" x-model="block.data.title_uk" placeholder="Title UK" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                                    <input type="text" x-model="block.data.title_en" placeholder="Title EN" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                                    <select x-model="block.data.image_position" class="sm:col-span-2 h-10 max-w-xs rounded-xl border border-white/10 bg-zinc-950/80 px-3 text-sm text-white"><option value="left">Image left</option><option value="right">Image right</option></select>
                                                </div>
                                            </template>
                                            <input type="text" x-model="block.data.alt_uk" placeholder="Alt UK" class="w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                            <input type="text" x-model="block.data.alt_en" placeholder="Alt EN" class="w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                            <input type="text" x-model="block.data.caption_uk" placeholder="Caption UK" class="w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                            <input type="text" x-model="block.data.caption_en" placeholder="Caption EN" class="w-full rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                            <template x-if="block.type === 'image_text'">
                                                <div class="grid gap-3 sm:grid-cols-2 sm:col-span-2">
                                                    <textarea x-model="block.data.text_uk" rows="5" placeholder="Text UK (HTML)" class="rounded-2xl border border-white/10 bg-zinc-950/90 px-3 py-2 font-mono text-sm text-white"></textarea>
                                                    <textarea x-model="block.data.text_en" rows="5" placeholder="Text EN (HTML)" class="rounded-2xl border border-white/10 bg-zinc-950/90 px-3 py-2 font-mono text-sm text-white"></textarea>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="block.type === 'quote'">
                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <textarea x-model="block.data.text_uk" rows="4" class="rounded-2xl border border-white/10 bg-zinc-950/90 px-3 py-2 text-sm text-white" placeholder="Quote UK (HTML)"></textarea>
                                        <textarea x-model="block.data.text_en" rows="4" class="rounded-2xl border border-white/10 bg-zinc-950/90 px-3 py-2 text-sm text-white" placeholder="Quote EN (HTML)"></textarea>
                                    </div>
                                </template>
                                <template x-if="block.type === 'list'">
                                    <div class="space-y-4">
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <input type="text" x-model="block.data.title_uk" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="List title UK">
                                            <input type="text" x-model="block.data.title_en" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="List title EN">
                                            <select x-model="block.data.style" class="sm:col-span-2 h-10 max-w-xs rounded-xl border border-white/10 bg-zinc-950/80 px-3 text-sm text-white"><option value="bullets">Bullets</option><option value="checks">Checks</option><option value="numbers">Numbers</option></select>
                                        </div>
                                        <div class="grid gap-6 lg:grid-cols-2">
                                            <div class="space-y-2">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">UK</p>
                                                <template x-for="(it, li) in block.data.items_uk" :key="'iuk'+index+li">
                                                    <div class="flex gap-2">
                                                        <input type="text" x-model="block.data.items_uk[li]" class="min-w-0 flex-1 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                                        <button type="button" class="shrink-0 rounded-lg border border-white/10 px-2 text-xs" @click="listRemoveItem(block, 'items_uk', li)">×</button>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="space-y-2">
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">EN</p>
                                                <template x-for="(it, li) in block.data.items_en" :key="'ien'+index+li">
                                                    <div class="flex gap-2">
                                                        <input type="text" x-model="block.data.items_en[li]" class="min-w-0 flex-1 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                                        <button type="button" class="shrink-0 rounded-lg border border-white/10 px-2 text-xs" @click="listRemoveItem(block, 'items_en', li)">×</button>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" class="rounded-xl border border-white/10 px-3 py-1 text-xs text-zinc-300" @click="listAddItem(block, 'items_uk')">+ UK item</button>
                                            <button type="button" class="rounded-xl border border-white/10 px-3 py-1 text-xs text-zinc-300" @click="listAddItem(block, 'items_en')">+ EN item</button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="block.type === 'table'">
                                    <div class="space-y-4">
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <input type="text" x-model="block.data.title_uk" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Table title UK">
                                            <input type="text" x-model="block.data.title_en" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Table title EN">
                                        </div>
                                        <p class="text-xs text-zinc-500">Headers + rows as text fields (pipe | optional in cells).</p>
                                        <div class="flex gap-2">
                                            <button type="button" class="rounded-xl border border-white/10 px-3 py-1 text-xs" @click="tableAddCol(block)">+ col</button>
                                            <button type="button" class="rounded-xl border border-white/10 px-3 py-1 text-xs" @click="tableAddRow(block)">+ row</button>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="(h, hi) in block.data.headers" :key="'h'+index+hi">
                                                <input type="text" x-model="block.data.headers[hi]" class="w-28 rounded-lg border border-white/10 bg-zinc-950/80 px-2 py-1 text-xs text-white">
                                            </template>
                                        </div>
                                        <template x-for="(row, ri) in block.data.rows" :key="'r'+index+ri">
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="(cell, ci) in row" :key="'c'+index+ri+ci">
                                                    <input type="text" x-model="block.data.rows[ri][ci]" class="w-28 rounded-lg border border-white/10 bg-zinc-950/80 px-2 py-1 text-xs text-white">
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="block.type === 'tips'">
                                    <div class="space-y-4">
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <input type="text" x-model="block.data.title_uk" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Tips title UK">
                                            <input type="text" x-model="block.data.title_en" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Tips title EN">
                                            <input type="text" x-model="block.data.icon" class="sm:col-span-2 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Icon (emoji optional)">
                                        </div>
                                        <template x-for="(it, li) in block.data.items_uk" :key="'tuk'+index+li"><div class="flex gap-2"><input type="text" x-model="block.data.items_uk[li]" class="flex-1 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"><button type="button" @click="listRemoveItem(block, 'items_uk', li)" class="rounded-lg border border-white/10 px-2">×</button></div></template>
                                        <template x-for="(it, li) in block.data.items_en" :key="'ten'+index+li"><div class="flex gap-2"><input type="text" x-model="block.data.items_en[li]" class="flex-1 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"><button type="button" @click="listRemoveItem(block, 'items_en', li)" class="rounded-lg border border-white/10 px-2">×</button></div></template>
                                        <div class="flex gap-2"><button type="button" @click="listAddItem(block, 'items_uk')" class="rounded-xl border border-white/10 px-3 py-1 text-xs">+ UK</button><button type="button" @click="listAddItem(block, 'items_en')" class="rounded-xl border border-white/10 px-3 py-1 text-xs">+ EN</button></div>
                                    </div>
                                </template>
                                <template x-if="block.type === 'warning'">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <input type="text" x-model="block.data.title_uk" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Title UK">
                                        <input type="text" x-model="block.data.title_en" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Title EN">
                                        <select x-model="block.data.tone" class="sm:col-span-2 h-10 max-w-xs rounded-xl border border-white/10 bg-zinc-950/80 px-3 text-sm text-white"><option value="amber">Amber</option><option value="red">Red</option></select>
                                        <textarea x-model="block.data.text_uk" rows="4" class="rounded-2xl border border-white/10 bg-zinc-950/90 px-3 py-2 text-sm text-white" placeholder="Text UK"></textarea>
                                        <textarea x-model="block.data.text_en" rows="4" class="rounded-2xl border border-white/10 bg-zinc-950/90 px-3 py-2 text-sm text-white" placeholder="Text EN"></textarea>
                                    </div>
                                </template>
                                <template x-if="block.type === 'steps'">
                                    <div class="space-y-4">
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <input type="text" x-model="block.data.title_uk" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                            <input type="text" x-model="block.data.title_en" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                        </div>
                                        <template x-for="(st, si) in block.data.steps" :key="'s'+index+si">
                                            <div class="rounded-2xl border border-white/10 bg-zinc-950/50 p-4 space-y-2">
                                                <input type="text" x-model="block.data.steps[si].title_uk" class="w-full rounded-lg border border-white/10 bg-zinc-950 px-2 py-1 text-sm text-white" placeholder="Step title UK">
                                                <input type="text" x-model="block.data.steps[si].title_en" class="w-full rounded-lg border border-white/10 bg-zinc-950 px-2 py-1 text-sm text-white" placeholder="Step title EN">
                                                <textarea x-model="block.data.steps[si].text_uk" rows="2" class="w-full rounded-lg border border-white/10 bg-zinc-950 px-2 py-1 text-sm text-white"></textarea>
                                                <textarea x-model="block.data.steps[si].text_en" rows="2" class="w-full rounded-lg border border-white/10 bg-zinc-950 px-2 py-1 text-sm text-white"></textarea>
                                                <button type="button" @click="stepRemove(block, si)" class="text-xs text-rose-300">remove step</button>
                                            </div>
                                        </template>
                                        <button type="button" @click="stepAdd(block)" class="rounded-xl border border-emerald-400/30 px-3 py-1 text-xs text-emerald-200">+ step</button>
                                    </div>
                                </template>
                                <template x-if="block.type === 'faq'">
                                    <div class="space-y-3">
                                        <template x-for="(fq, fi) in block.data.items" :key="'f'+index+fi">
                                            <div class="rounded-2xl border border-white/10 bg-zinc-950/50 p-4 space-y-2">
                                                <input type="text" x-model="block.data.items[fi].question_uk" class="w-full rounded-lg border border-white/10 bg-zinc-950 px-2 py-1 text-sm text-white" placeholder="Q UK">
                                                <textarea x-model="block.data.items[fi].answer_uk" rows="2" class="w-full rounded-lg border border-white/10 bg-zinc-950 px-2 py-1 text-sm text-white"></textarea>
                                                <input type="text" x-model="block.data.items[fi].question_en" class="w-full rounded-lg border border-white/10 bg-zinc-950 px-2 py-1 text-sm text-white" placeholder="Q EN">
                                                <textarea x-model="block.data.items[fi].answer_en" rows="2" class="w-full rounded-lg border border-white/10 bg-zinc-950 px-2 py-1 text-sm text-white"></textarea>
                                                <button type="button" @click="faqRemove(block, fi)" class="text-xs text-rose-300">remove</button>
                                            </div>
                                        </template>
                                        <button type="button" @click="faqAdd(block)" class="rounded-xl border border-emerald-400/30 px-3 py-1 text-xs text-emerald-200">+ FAQ item</button>
                                    </div>
                                </template>
                                <template x-if="block.type === 'cta'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <input type="text" x-model="block.data.title_uk" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Title UK">
                                        <input type="text" x-model="block.data.title_en" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Title EN">
                                        <textarea x-model="block.data.text_uk" rows="3" class="sm:col-span-2 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"></textarea>
                                        <textarea x-model="block.data.text_en" rows="3" class="sm:col-span-2 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"></textarea>
                                        <input type="text" x-model="block.data.button_text_uk" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Button UK">
                                        <input type="text" x-model="block.data.button_text_en" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="Button EN">
                                        <input type="text" x-model="block.data.button_url" class="sm:col-span-2 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="https://">
                                    </div>
                                </template>
                                <template x-if="block.type === 'product_cards' || block.type === 'related_models'">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <input type="text" x-model="block.data.title_uk" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                        <input type="text" x-model="block.data.title_en" class="rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white">
                                        <textarea x-model="block.data.body_uk" rows="3" class="sm:col-span-2 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"></textarea>
                                        <textarea x-model="block.data.body_en" rows="3" class="sm:col-span-2 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white"></textarea>
                                        <input type="text" x-model="block.data.href" class="sm:col-span-2 rounded-xl border border-white/10 bg-zinc-950/80 px-3 py-2 text-sm text-white" placeholder="{{ route('products.index') }}">
                                    </div>
                                </template>
                                <template x-if="block.type === 'divider'">
                                    <p class="text-sm text-zinc-500">— horizontal rule —</p>
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

            <button type="submit" class="w-full rounded-3xl bg-emerald-400 px-5 py-4 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-300">{{ __('Зберегти статтю') }}</button>
        </aside>
    </form>

    <template x-teleport="body">
        <div x-show="showPicker" x-cloak class="fixed inset-0 z-[100] flex items-end justify-center bg-black/70 p-4 sm:items-center" @click.self="showPicker = false">
            <div class="max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-3xl border border-white/10 bg-zinc-950 p-6 shadow-2xl" @click.stop>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-black text-white">{{ __('blog.admin.choose_block') }}</h3>
                    <button type="button" class="rounded-lg border border-white/10 px-2 py-1 text-sm text-zinc-400" @click="showPicker = false">✕</button>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach(BlogPostBlockService::TYPES as $bt)
                        <button type="button" @click="addBlock('{{ $bt }}')" class="rounded-2xl border border-white/10 bg-white/[0.04] px-3 py-3 text-left text-xs font-bold text-zinc-200 transition hover:border-emerald-400/40 hover:bg-emerald-400/10 hover:text-emerald-50">
                            {{ __('blog.admin.block_types.'.$bt) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </template>
    </div>
</x-layouts.admin>
