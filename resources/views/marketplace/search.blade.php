<x-layouts.marketplace :seo-title="($q ? 'Пошук: '.$q : 'Пошук') . ' · 3Dify'">

    {{-- Hero --}}
    <div class="relative border-b border-white/[0.06] bg-zinc-950 py-12 sm:py-16">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_70%_50%_at_50%_-10%,rgba(52,211,153,.08),transparent)]"></div>
        <div class="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <h1 class="mb-6 text-center text-3xl font-black text-white sm:text-4xl">Пошук по 3Dify</h1>
            <form method="GET" action="{{ route('search') }}">
                <div class="flex items-center gap-2 rounded-2xl border border-white/10 bg-zinc-900/80 px-4 focus-within:border-emerald-400/50 focus-within:ring-2 focus-within:ring-emerald-400/20 transition">
                    <svg class="h-5 w-5 shrink-0 text-zinc-500"
                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input
                        name="q"
                        value="{{ $q }}"
                        autofocus
                        autocomplete="off"
                        placeholder="Моделі, автори, статті…"
                        class="h-14 min-w-0 flex-1 bg-transparent text-base text-white placeholder:text-zinc-500 focus:outline-none"
                    >
                    <button type="submit"
                            class="shrink-0 rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-black text-zinc-950 transition hover:bg-emerald-300">
                        Шукати
                    </button>
                </div>
            </form>

            @if(! $q)
                <p class="mt-4 text-center text-sm text-zinc-500">
                    Шукайте серед моделей, авторів і статей блогу
                </p>
            @endif
        </div>
    </div>

    {{-- Results --}}
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">

        @if($q && $products->isEmpty() && $authors->isEmpty() && $posts->isEmpty())
            <div class="py-16 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-900">
                    <svg class="h-8 w-8 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <p class="text-lg font-semibold text-white">Нічого не знайдено за «{{ $q }}»</p>
                <p class="mt-2 text-sm text-zinc-500">Спробуйте інші ключові слова або перегляньте каталог</p>
                <a href="{{ route('products.index') }}" class="mt-6 inline-flex items-center rounded-xl bg-emerald-400 px-6 py-2.5 text-sm font-black text-zinc-950 hover:bg-emerald-300">
                    Перейти в каталог →
                </a>
            </div>

        @elseif($q)

            @if($products->isNotEmpty())
                <section class="mb-12">
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <h2 class="text-xl font-black text-white">
                            3D-моделі
                            <span class="ml-2 text-base font-normal text-zinc-500">({{ $products->count() }})</span>
                        </h2>
                        <a href="{{ route('products.index', ['q' => $q]) }}" class="text-sm font-semibold text-emerald-400 transition hover:text-emerald-300">
                            Всі результати →
                        </a>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($products as $product)
                            <x-ui.model-card :product="$product" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if($authors->isNotEmpty())
                <section class="mb-12">
                    <h2 class="mb-5 text-xl font-black text-white">
                        Автори
                        <span class="ml-2 text-base font-normal text-zinc-500">({{ $authors->count() }})</span>
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($authors as $author)
                            <a href="{{ $author->profileUrl() }}"
                               class="flex items-center gap-4 rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-4 transition hover:border-emerald-400/25 hover:bg-zinc-900/80">
                                @if($author->avatarUrl())
                                    <img src="{{ $author->avatarUrl() }}" class="h-12 w-12 shrink-0 rounded-full object-cover ring-2 ring-emerald-400/20">
                                @else
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-zinc-700 text-base font-black text-zinc-300">
                                        {{ mb_strtoupper(mb_substr($author->displayName(), 0, 1)) }}
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-white">{{ $author->displayName() }}</p>
                                    <p class="text-xs text-zinc-500">{{ $author->published_count }} моделей</p>
                                </div>
                                <svg class="ml-auto h-4 w-4 shrink-0 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($posts->isNotEmpty())
                <section>
                    <h2 class="mb-5 text-xl font-black text-white">
                        Статті блогу
                        <span class="ml-2 text-base font-normal text-zinc-500">({{ $posts->count() }})</span>
                    </h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($posts as $post)
                            @include('marketplace.blog.partials.card', ['post' => $post])
                        @endforeach
                    </div>
                </section>
            @endif

        @else
            {{-- No query — show quick nav cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('products.index') }}" class="group rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-5 transition hover:border-emerald-400/25 hover:bg-zinc-900/80">
                    <div class="mb-3 grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-zinc-800 text-zinc-400 transition group-hover:border-emerald-400/30 group-hover:text-emerald-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <p class="font-bold text-white">Каталог моделей</p>
                    <p class="mt-1 text-xs text-zinc-500">STL, 3MF, OBJ, GLB</p>
                </a>
                <a href="{{ route('authors.index') }}" class="group rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-5 transition hover:border-emerald-400/25 hover:bg-zinc-900/80">
                    <div class="mb-3 grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-zinc-800 text-zinc-400 transition group-hover:border-emerald-400/30 group-hover:text-emerald-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <p class="font-bold text-white">Автори</p>
                    <p class="mt-1 text-xs text-zinc-500">Профілі та моделі</p>
                </a>
                <a href="{{ route('blog.index') }}" class="group rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-5 transition hover:border-emerald-400/25 hover:bg-zinc-900/80">
                    <div class="mb-3 grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-zinc-800 text-zinc-400 transition group-hover:border-emerald-400/30 group-hover:text-emerald-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z"/><path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z"/></svg>
                    </div>
                    <p class="font-bold text-white">Блог</p>
                    <p class="mt-1 text-xs text-zinc-500">Гайди з 3D-друку</p>
                </a>
                <a href="{{ route('makes.gallery') }}" class="group rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-5 transition hover:border-emerald-400/25 hover:bg-zinc-900/80">
                    <div class="mb-3 grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-zinc-800 text-zinc-400 transition group-hover:border-emerald-400/30 group-hover:text-emerald-400">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <p class="font-bold text-white">Галерея друків</p>
                    <p class="mt-1 text-xs text-zinc-500">Фото від спільноти</p>
                </a>
            </div>
        @endif
    </div>
</x-layouts.marketplace>
