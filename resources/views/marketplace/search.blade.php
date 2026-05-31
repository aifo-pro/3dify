<x-layouts.marketplace :seo-title="($q ? 'Пошук: '.$q : 'Пошук') . ' · 3Dify'">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">

        <form method="GET" action="{{ route('search') }}" class="mb-10 flex gap-2">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input name="q" value="{{ $q }}" autofocus placeholder="Пошук моделей, авторів, статей..."
                       class="h-14 w-full rounded-2xl border border-white/10 bg-zinc-900/60 pl-12 pr-4 text-lg text-white placeholder:text-zinc-500 focus:border-emerald-400 focus:outline-none">
            </div>
            <button class="h-14 shrink-0 rounded-2xl bg-emerald-400 px-6 text-base font-black text-zinc-950 transition hover:bg-emerald-300">Шукати</button>
        </form>

        @if($q && $products->isEmpty() && $authors->isEmpty() && $posts->isEmpty())
            <x-ui.empty-state title="Нічого не знайдено" description="Спробуйте інші ключові слова." />
        @else

            @if($products->isNotEmpty())
                <section class="mb-12">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-xl font-black text-white">3D-моделі <span class="text-zinc-500 text-base font-normal">({{ $products->count() }})</span></h2>
                        <a href="{{ route('products.index', ['q' => $q]) }}" class="text-sm text-emerald-400 hover:text-emerald-300">Всі результати →</a>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($products as $product)
                            <x-ui.model-card :product="$product" />
                        @endforeach
                    </div>
                </section>
            @endif

            @if($authors->isNotEmpty())
                <section class="mb-12">
                    <h2 class="mb-5 text-xl font-black text-white">Автори <span class="text-zinc-500 text-base font-normal">({{ $authors->count() }})</span></h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($authors as $author)
                            <a href="{{ $author->profileUrl() }}" class="flex items-center gap-4 rounded-2xl border border-white/[0.07] bg-zinc-900/50 p-4 transition hover:border-emerald-400/25">
                                @if($author->avatarUrl())
                                    <img src="{{ $author->avatarUrl() }}" class="h-12 w-12 rounded-full object-cover">
                                @else
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-zinc-700 font-black text-zinc-300">{{ mb_strtoupper(mb_substr($author->displayName(), 0, 1)) }}</span>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-bold text-white truncate">{{ $author->displayName() }}</p>
                                    <p class="text-xs text-zinc-500">{{ $author->published_count }} моделей</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($posts->isNotEmpty())
                <section class="mb-12">
                    <h2 class="mb-5 text-xl font-black text-white">Статті блогу <span class="text-zinc-500 text-base font-normal">({{ $posts->count() }})</span></h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach($posts as $post)
                            @include('marketplace.blog.partials.card', ['post' => $post])
                        @endforeach
                    </div>
                </section>
            @endif

        @endif
    </div>
</x-layouts.marketplace>
