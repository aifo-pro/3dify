@props([
    'author',
    'isFollowing' => false,
    'isSelf' => false,
    'size' => 'md',
])

@php
    $sizeCls = $size === 'sm'
        ? 'h-9 px-3 text-xs'
        : ($size === 'lg' ? 'h-11 px-5 text-sm' : 'h-10 px-4 text-xs');
@endphp

@if($isSelf)
    <a href="{{ route('profile.edit') }}"
       {{ $attributes->merge(['class' => "inline-flex {$sizeCls} items-center gap-1.5 rounded-xl border border-white/15 bg-white/[0.05] font-bold text-white transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.10]"]) }}>
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        {{ __('Редагувати профіль') }}
    </a>
@elseif(! auth()->check())
    <a href="{{ route('login') }}"
       {{ $attributes->merge(['class' => "inline-flex {$sizeCls} items-center gap-1.5 rounded-xl bg-emerald-400 font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300"]) }}>
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        {{ __('Підписатися') }}
    </a>
@elseif($isFollowing)
    <form method="POST" action="{{ route('authors.unfollow', ['user' => $author->username ?: $author->id]) }}">
        @csrf
        @method('DELETE')
        <button type="submit"
                {{ $attributes->merge(['class' => "inline-flex {$sizeCls} items-center gap-1.5 rounded-xl border border-emerald-300/40 bg-emerald-300/[0.10] font-bold text-emerald-100 transition hover:border-rose-300/40 hover:bg-rose-300/[0.10] hover:text-rose-100"]) }}>
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span>{{ __('Відписатися') }}</span>
        </button>
    </form>
@else
    <form method="POST" action="{{ route('authors.follow', ['user' => $author->username ?: $author->id]) }}">
        @csrf
        <button type="submit"
                {{ $attributes->merge(['class' => "inline-flex {$sizeCls} items-center gap-1.5 rounded-xl bg-emerald-400 font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300"]) }}>
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Підписатися') }}
        </button>
    </form>
@endif
