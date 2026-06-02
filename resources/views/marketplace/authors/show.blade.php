@php
    $authorKey = $author->username ?: $author->id;
    $contactAction = route('authors.contact', ['user' => $authorKey]);
    $bio = $author->localizedBio();
    $socials = [
        'Website' => $author->website_url,
        'Telegram' => $author->telegram_url,
        'Instagram' => $author->instagram_url,
        'YouTube' => $author->youtube_url,
        'GitHub' => $author->github_url,
        'X' => $author->twitter_url,
    ];
    $tabs = [
        ['key' => 'models', 'label' => __('Усі моделі'), 'count' => $stats['models']],
        ['key' => 'free', 'label' => __('Безкоштовні')],
        ['key' => 'popular', 'label' => __('Популярні')],
        ['key' => 'about', 'label' => __('Про автора')],
    ];

    // Stable per-author hue so each profile has its own identity even without a custom cover.
    $hashSeed = crc32(strtolower($author->displayName().'|'.$author->id));
    $hueA = $hashSeed % 360;
    $hueB = ($hueA + 60 + ($hashSeed >> 8) % 100) % 360;
    $hueC = ($hueA + 200 + ($hashSeed >> 16) % 80) % 360;
    $initials = mb_strtoupper(mb_substr($author->displayName(), 0, 1));
@endphp

<x-layouts.marketplace>
    @if(session('status'))
        <div class="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        </div>
    @endif

    {{-- =================================================================== --}}
    {{-- HERO  (cover + avatar + meta)                                         --}}
    {{-- =================================================================== --}}
    @php $hasCover = (bool) $author->coverUrl(); @endphp
    <section class="relative">
        {{-- Cover canvas — taller for real images, shorter for the generated mesh --}}
        <div class="relative w-full overflow-hidden {{ $hasCover ? 'h-64 sm:h-80 lg:h-[420px]' : 'h-56 sm:h-72 lg:h-80' }}">
            @if($hasCover)
                <img src="{{ $author->coverUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
                {{-- Subtle vignette only at the very bottom so card edge blends, but image stays readable --}}
                <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-b from-transparent to-zinc-950/85"></div>
                {{-- Side gradients to anchor banner against page edges on widescreens --}}
                <div class="pointer-events-none absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-zinc-950/60 to-transparent"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-zinc-950/60 to-transparent"></div>
            @else
                {{-- Layer 1: deep base gradient seeded by author hue --}}
                <div
                    class="absolute inset-0"
                    style="background:
                        radial-gradient(circle at 18% 22%, hsla({{ $hueA }}, 70%, 55%, .35) 0%, transparent 55%),
                        radial-gradient(circle at 82% 28%, hsla({{ $hueB }}, 65%, 50%, .28) 0%, transparent 55%),
                        radial-gradient(circle at 50% 100%, hsla({{ $hueC }}, 60%, 40%, .35) 0%, transparent 60%),
                        linear-gradient(135deg, #06090d 0%, #0a0d12 50%, #07050d 100%);"
                ></div>

                {{-- Layer 2: soft floating blobs --}}
                <div class="absolute -top-20 -left-16 h-72 w-72 rounded-full opacity-50 blur-3xl"
                     style="background: radial-gradient(circle, hsla({{ $hueA }}, 80%, 60%, .55) 0%, transparent 70%);"></div>
                <div class="absolute top-10 right-[-60px] h-80 w-80 rounded-full opacity-40 blur-3xl"
                     style="background: radial-gradient(circle, hsla({{ $hueB }}, 75%, 55%, .50) 0%, transparent 70%);"></div>
                <div class="absolute bottom-[-80px] left-1/3 h-96 w-96 rounded-full opacity-30 blur-3xl"
                     style="background: radial-gradient(circle, hsla({{ $hueC }}, 70%, 50%, .50) 0%, transparent 70%);"></div>

                {{-- Layer 3: isometric grid (subtle) --}}
                <svg class="absolute inset-0 h-full w-full opacity-[0.07]" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <pattern id="iso-grid" width="44" height="76" patternUnits="userSpaceOnUse" patternTransform="rotate(0)">
                            <path d="M22 0 L44 38 L22 76 L0 38 Z" fill="none" stroke="white" stroke-width="0.6"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#iso-grid)"/>
                </svg>

                {{-- Layer 4: floating polyhedra silhouettes (3D vibe) --}}
                <svg class="absolute right-6 top-6 h-24 w-24 text-white/10 sm:h-32 sm:w-32" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="0.8" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="50,8 92,32 92,76 50,100 8,76 8,32"/>
                    <polygon points="50,8 92,32 50,56 8,32"/>
                    <line x1="50" y1="56" x2="50" y2="100"/>
                </svg>
                <svg class="absolute left-1/3 top-12 h-16 w-16 text-white/[0.07] rotate-12 sm:h-20 sm:w-20" viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="0.8" stroke-linejoin="round" aria-hidden="true">
                    <polygon points="50,12 88,50 50,88 12,50"/>
                    <line x1="50" y1="12" x2="50" y2="88"/>
                    <line x1="12" y1="50" x2="88" y2="50"/>
                </svg>

                {{-- Layer 5: scanline highlight --}}
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent"></div>

                {{-- Bottom fade into page (only for the generated mesh — real images stay clean) --}}
                <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-b from-transparent via-zinc-950/70 to-zinc-950"></div>
            @endif
        </div>

        {{-- Header card.
             Real images get a small overlap so most of the cover stays visible.
             Generated covers can overlap further because the bottom is faded into the page. --}}
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 {{ $hasCover ? '-mt-10 sm:-mt-12' : '-mt-20' }}">
            <div class="rounded-3xl border border-white/10 bg-zinc-950/70 p-5 shadow-2xl shadow-black/40 backdrop-blur-xl sm:p-6">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:gap-6">
                    {{-- Avatar with glow ring --}}
                    <div class="relative -mt-12 shrink-0 sm:-mt-14">
                        <span class="absolute -inset-1.5 rounded-3xl opacity-60 blur-md"
                              style="background: linear-gradient(135deg, hsla({{ $hueA }}, 80%, 60%, .65), hsla({{ $hueB }}, 75%, 55%, .50));"></span>
                        @if($author->avatarUrl())
                            <img src="{{ $author->avatarUrl() }}" alt="{{ $author->displayName() }}" class="relative h-24 w-24 rounded-3xl border-4 border-zinc-950 bg-zinc-950 object-cover shadow-2xl shadow-black/50 sm:h-28 sm:w-28">
                        @else
                            <div class="relative grid h-24 w-24 place-items-center rounded-3xl border-4 border-zinc-950 text-3xl font-black text-zinc-950 shadow-2xl shadow-black/50 sm:h-28 sm:w-28 sm:text-4xl"
                                 style="background: linear-gradient(135deg, hsla({{ $hueA }}, 75%, 65%, 1), hsla({{ $hueB }}, 70%, 55%, 1));">
                                {{ $initials }}
                            </div>
                        @endif
                    </div>

                    {{-- Identity --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-2xl font-black tracking-tight text-white sm:text-3xl lg:text-4xl">{{ $author->displayName() }}</h1>
                            <x-ui.verified-badge :user="$author" size="md" />
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-400">
                            <span class="font-mono text-emerald-300/80">{{ '@'.($author->username ?: 'author-'.$author->id) }}</span>
                            @if($author->publicLocation())
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    @if($author->countryFlag())
                                        <span aria-hidden="true">{{ $author->countryFlag() }}</span>
                                    @endif
                                    {{ $author->publicLocation() }}
                                </span>
                            @endif
                            @if($author->created_at)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    {{ __('на 3Dify з') }} {{ $author->created_at->translatedFormat('M Y') }}
                                </span>
                            @endif
                        </div>

                        @if($bio)
                            <p class="mt-3 line-clamp-2 max-w-2xl text-sm leading-6 text-zinc-300">{{ $bio }}</p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap items-center gap-2 sm:self-end">
                        <x-ui.follow-button :author="$author" :is-following="$isFollowing" :is-self="$isSelf" size="lg" />

                        @if(! $isSelf)
                            @auth
                                <a href="{{ route('custom-orders.create', ['author' => $authorKey]) }}" class="inline-flex h-11 items-center gap-1.5 rounded-xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                    {{ __('custom_orders.new_order') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex h-11 items-center gap-1.5 rounded-xl bg-emerald-400 px-5 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                                    {{ __('custom_orders.new_order') }}
                                </a>
                            @endauth
                        @endif

                        @if(! $isSelf && $author->contact_enabled)
                            @auth
                                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-contact-modal', { detail: { action: '{{ $contactAction }}', author: {{ json_encode($author->displayName()) }} } }))" class="inline-flex h-11 items-center gap-1.5 rounded-xl border border-white/15 bg-white/[0.05] px-5 text-sm font-bold text-white transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.10]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    {{ __('Контакт') }}
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex h-11 items-center gap-1.5 rounded-xl border border-white/15 bg-white/[0.05] px-5 text-sm font-bold text-white transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.10]">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                    {{ __('Контакт') }}
                                </a>
                            @endauth
                        @endif

                        @if(collect($socials)->filter()->isNotEmpty())
                            <div class="hidden items-center gap-1 lg:flex">
                                <span class="mx-1 h-6 w-px bg-white/10"></span>
                                @foreach($socials as $label => $url)
                                    @if($url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener" title="{{ $label }}" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-emerald-300/40 hover:text-emerald-200">
                                            <span class="text-[10px] font-black uppercase tracking-wider">{{ Str::substr($label, 0, 2) }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- =================================================================== --}}
    {{-- STATS                                                                 --}}
    {{-- =================================================================== --}}
    <section class="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
        @php
            $statCards = [
                ['key' => 'models', 'label' => __('Моделі'), 'value' => $stats['models'], 'tone' => 'emerald', 'icon' => '<polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"/><polyline points="2 8.5 12 15 22 8.5"/><line x1="12" y1="22" x2="12" y2="15"/>'],
                ['key' => 'downloads', 'label' => __('Завантаження'), 'value' => $stats['downloads'], 'tone' => 'sky', 'icon' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>'],
                ['key' => 'followers', 'label' => __('Підписники'), 'value' => $stats['followers'], 'tone' => 'violet', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
                ['key' => 'likes', 'label' => __('Обране'), 'value' => $stats['likes'], 'tone' => 'rose', 'icon' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>'],
            ];
            $tones = [
                'emerald' => ['ring' => 'group-hover:border-emerald-300/40', 'icon' => 'bg-emerald-300/[0.10] text-emerald-200', 'value' => 'text-emerald-100', 'glow' => 'from-emerald-500/[0.10]'],
                'sky' => ['ring' => 'group-hover:border-sky-300/40', 'icon' => 'bg-sky-300/[0.10] text-sky-200', 'value' => 'text-sky-100', 'glow' => 'from-sky-500/[0.10]'],
                'violet' => ['ring' => 'group-hover:border-violet-300/40', 'icon' => 'bg-violet-300/[0.10] text-violet-200', 'value' => 'text-violet-100', 'glow' => 'from-violet-500/[0.10]'],
                'rose' => ['ring' => 'group-hover:border-rose-300/40', 'icon' => 'bg-rose-300/[0.10] text-rose-200', 'value' => 'text-rose-100', 'glow' => 'from-rose-500/[0.10]'],
            ];
        @endphp
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($statCards as $card)
                @php $t = $tones[$card['tone']]; @endphp
                <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition {{ $t['ring'] }}">
                    <div class="pointer-events-none absolute -top-12 -right-12 h-32 w-32 rounded-full bg-gradient-to-br {{ $t['glow'] }} to-transparent opacity-60 blur-2xl"></div>
                    <div class="relative flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ $card['label'] }}</p>
                            <p class="mt-2 text-3xl font-black tabular-nums {{ $t['value'] }}">{{ number_format($card['value']) }}</p>
                        </div>
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $t['icon'] }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $card['icon'] !!}</svg>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 grid gap-3 lg:grid-cols-3">
            <div class="rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.06] p-5">
                <p class="text-[11px] font-black uppercase tracking-[0.16em] text-emerald-200">{{ __('Довіра автора') }}</p>
                <p class="mt-2 text-lg font-black text-white">
                    {{ $author->verificationTier() === 'verified' ? __('Перевірений автор') : ($author->verificationTier() === 'trusted' ? __('Надійний автор') : __('Новий автор') ) }}
                </p>
                <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('Показник базується на публікаціях, історії продажів, підписниках і активності профілю.') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <p class="text-[11px] font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('Активність') }}</p>
                <p class="mt-2 text-lg font-black text-white">{{ __(':sales продажів · :downloads завантажень', ['sales' => $stats['sales'], 'downloads' => $stats['downloads']]) }}</p>
                <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('Публічні числа допомагають оцінити досвід автора без розкриття приватних даних.') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                <p class="text-[11px] font-black uppercase tracking-[0.16em] text-zinc-500">{{ __('Підтримка') }}</p>
                <p class="mt-2 text-lg font-black text-white">{{ $author->contact_enabled ? __('Контакт відкритий') : __('Контакт закритий') }}</p>
                <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('Email автора не показується публічно: повідомлення йдуть через форму 3Dify.') }}</p>
            </div>
        </div>
    </section>

    <section class="mx-auto mt-8 max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        {{-- Tabs + filter — single rounded toolbar card matching the rest of the design --}}
        <div class="mb-8 flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-2 shadow-xl shadow-black/20 lg:flex-row lg:items-center lg:justify-between">
            <nav class="flex flex-wrap gap-1 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($tabs as $t)
                    @php $active = $tab === $t['key']; @endphp
                    <a href="{{ route('authors.show', ['user' => $authorKey, 'tab' => $t['key']]) }}"
                       class="inline-flex h-9 shrink-0 items-center gap-2 rounded-xl px-3.5 text-sm font-semibold transition {{ $active ? 'bg-emerald-400 text-zinc-950 shadow-lg shadow-emerald-500/25' : 'text-zinc-300 hover:bg-white/[0.06] hover:text-white' }}">
                        <span>{{ $t['label'] }}</span>
                        @if(isset($t['count']) && $t['count'] > 0)
                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-black {{ $active ? 'bg-zinc-950/30 text-emerald-50' : 'bg-white/10 text-zinc-300' }}">{{ $t['count'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            @if($tab !== 'about')
                <form method="GET" action="{{ route('authors.show', ['user' => $authorKey]) }}" class="flex flex-wrap items-center gap-2 lg:shrink-0">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <span class="hidden text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500 lg:inline">{{ __('Сортувати') }}</span>
                    <div class="relative">
                        <select name="sort" onchange="this.form.submit()" class="h-9 appearance-none rounded-xl border border-white/10 bg-zinc-950/60 pl-3 pr-9 text-xs font-semibold text-white focus:border-emerald-300 focus:outline-none focus:ring-1 focus:ring-emerald-300/40">
                            <option value="latest" @selected($sort === 'latest')>{{ __('Нові') }}</option>
                            <option value="popular" @selected($sort === 'popular')>{{ __('Популярні') }}</option>
                            <option value="downloads" @selected($sort === 'downloads')>{{ __('Найбільше завантажень') }}</option>
                            <option value="free" @selected($sort === 'free')>{{ __('Безкоштовні') }}</option>
                        </select>
                        <svg class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <noscript>
                        <button class="h-9 rounded-xl bg-emerald-400 px-3 text-xs font-black text-zinc-950">{{ __('Застосувати') }}</button>
                    </noscript>
                </form>
            @endif
        </div>

        @if($tab === 'about')
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <article class="rounded-3xl border border-white/10 bg-white/[0.04] p-6 shadow-xl shadow-black/20 sm:p-8">
                    <h2 class="text-xl font-black text-white">{{ __('Про автора') }}</h2>
                    <div class="mt-5 text-sm leading-7 text-zinc-300">
                        @if($bio)
                            <p>{{ $bio }}</p>
                        @else
                            <p class="text-zinc-500">{{ __('Автор поки не додав опис.') }}</p>
                        @endif
                    </div>
                    @if($author->contact_enabled && ! $isSelf)
                        <div class="mt-6 rounded-2xl border border-emerald-300/20 bg-emerald-300/[0.06] p-5">
                            <h3 class="font-bold text-white">{{ __('Хочете звʼязатися з автором?') }}</h3>
                            <p class="mt-1 text-sm text-zinc-400">{{ __('Напишіть коротке повідомлення через приватну форму. Email автора не показується публічно.') }}</p>
                        </div>
                    @endif
                </article>

                <aside class="grid gap-3 self-start lg:sticky lg:top-44">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('На 3Dify з') }}</p>
                        <p class="mt-2 text-base font-bold text-white">{{ optional($author->created_at)->translatedFormat('d F Y') ?: '—' }}</p>
                    </div>
                    @if(collect($socials)->filter()->isNotEmpty())
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-zinc-500">{{ __('Посилання') }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($socials as $label => $url)
                                    @if($url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-full border border-white/10 bg-zinc-950/50 px-3 py-1.5 text-xs font-bold text-zinc-200 transition hover:border-emerald-300/40 hover:text-emerald-100">{{ $label }}</a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        @else
            @if($products && $products->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($products as $p)
                        <x-ui.model-card :product="$p" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            @else
                <x-ui.empty-state :title="__('Моделей ще немає')" :description="__('У цій вкладці поки немає опублікованих моделей.')" />
            @endif
        @endif
    </section>

    @auth
        <x-ui.contact-modal />
    @endauth
</x-layouts.marketplace>
