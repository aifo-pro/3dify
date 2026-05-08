@props([
    'author',
    'isFollowing' => false,
    'isSelf' => false,
    'compact' => false,
])

@php
    $modelsCount = \App\Models\Product::query()->where('user_id', $author->id)->where('status', 'published')->count();
    $downloadsCount = (int) \App\Models\Product::query()->where('user_id', $author->id)->where('status', 'published')->sum('downloads_count');
    $followersCount = $author->followers()->count();
    $contactAction = route('authors.contact', ['user' => $author->username ?: $author->id]);
    $username = '@' . ($author->username ?: 'author-'.$author->id);
    $initial = mb_strtoupper(mb_substr($author->name, 0, 1));
@endphp

<article {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-[28px] border border-white/10 bg-zinc-950/72 p-5 shadow-2xl shadow-black/25 ring-1 ring-emerald-300/[0.03] backdrop-blur-xl']) }}>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-300/45 to-transparent"></div>
    <div class="pointer-events-none absolute -right-20 -top-24 h-44 w-44 rounded-full bg-emerald-400/12 blur-3xl"></div>

    <div class="relative flex items-center gap-3.5">
        @if($author->avatarUrl())
            <a href="{{ $author->profileUrl() }}" class="block shrink-0">
                <img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" class="h-14 w-14 rounded-2xl border border-white/10 bg-zinc-900 object-cover shadow-lg shadow-black/25">
            </a>
        @else
            <a href="{{ $author->profileUrl() }}" class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-emerald-300 to-teal-500 text-lg font-black text-zinc-950 shadow-lg shadow-emerald-500/20">
                {{ $initial }}
            </a>
        @endif

        <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-200/70">{{ __('Автор моделі') }}</p>
            <div class="mt-1 flex min-w-0 items-center gap-1.5">
                <a href="{{ $author->profileUrl() }}" class="min-w-0 truncate text-base font-black leading-5 text-white transition hover:text-emerald-200">
                    {{ $author->name }}
                </a>
                <x-ui.verified-badge :user="$author" size="xs" />
            </div>
            <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $username }}</p>
        </div>
    </div>

    <div class="relative mt-4 rounded-2xl border border-white/[0.06] bg-white/[0.025] px-4 py-3">
        @if($author->bio)
            <p class="line-clamp-3 text-sm leading-6 text-zinc-300">{{ $author->bio }}</p>
        @else
            <p class="text-sm italic leading-6 text-zinc-500">{{ __('Автор поки не додав опис.') }}</p>
        @endif
    </div>

    <div class="relative mt-4 grid grid-cols-3 gap-2">
        <div class="rounded-2xl border border-white/[0.08] bg-white/[0.035] px-2.5 py-3 text-center">
            <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Моделі') }}</p>
            <p class="mt-1 text-xl font-black leading-none text-white">{{ number_format($modelsCount) }}</p>
        </div>
        <div class="rounded-2xl border border-white/[0.08] bg-white/[0.035] px-2.5 py-3 text-center">
            <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Завантаж.') }}</p>
            <p class="mt-1 text-xl font-black leading-none text-white">{{ number_format($downloadsCount) }}</p>
        </div>
        <div class="rounded-2xl border border-white/[0.08] bg-white/[0.035] px-2.5 py-3 text-center">
            <p class="text-[9px] font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Підписники') }}</p>
            <p class="mt-1 text-xl font-black leading-none text-white">{{ number_format($followersCount) }}</p>
        </div>
    </div>

    @if(! $compact)
        <div class="relative mt-4 flex items-center gap-2 rounded-2xl border border-white/[0.06] bg-zinc-950/45 px-3 py-2 text-[11px] text-zinc-500">
            <svg class="h-3.5 w-3.5 shrink-0 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span class="truncate">{{ __('На 3Dify з') }} {{ optional($author->created_at)->translatedFormat('F Y') ?: '—' }}</span>
        </div>
    @endif

    <div class="relative mt-4 grid gap-2">
        <div class="flex flex-wrap gap-2">
            <x-ui.follow-button :author="$author" :is-following="$isFollowing" :is-self="$isSelf" size="sm" class="min-w-0 flex-1 justify-center" />

            @auth
                @if(! $isSelf)
                    <button type="button"
                            @click="$dispatch('open-contact-modal', { action: '{{ $contactAction }}', author: @js($author->name) })"
                            class="inline-flex h-9 min-w-0 flex-1 items-center justify-center gap-1.5 rounded-xl border border-white/12 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 transition hover:border-emerald-300/35 hover:bg-emerald-300/[0.10] hover:text-white">
                        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        <span class="truncate">{{ __('Контакт') }}</span>
                    </button>
                @endif
            @else
                <a href="{{ route('login') }}" class="inline-flex h-9 min-w-0 flex-1 items-center justify-center gap-1.5 rounded-xl border border-white/12 bg-white/[0.04] px-3 text-xs font-bold text-zinc-200 transition hover:border-emerald-300/35 hover:bg-emerald-300/[0.10] hover:text-white">
                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    <span class="truncate">{{ __('Контакт') }}</span>
                </a>
            @endauth
        </div>

        <a href="{{ $author->profileUrl() }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-emerald-300/[0.08] px-3 text-xs font-bold text-emerald-100 transition hover:bg-emerald-300/[0.14] hover:text-white">
            {{ __('Усі моделі автора') }}
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </div>
</article>
