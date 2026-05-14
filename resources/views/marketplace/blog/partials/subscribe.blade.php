@php
    $compact = $compact ?? false;
@endphp
<form
    method="POST"
    action="{{ route('blog.subscribe') }}"
    @class([
        'border shadow-xl shadow-black/25',
        'rounded-3xl border-emerald-300/20 bg-emerald-300/[0.07] p-6 shadow-2xl shadow-emerald-950/20' => ! $compact,
        'rounded-2xl border-emerald-300/25 bg-emerald-300/[0.08] p-4 shadow-lg' => $compact,
    ])
>
    @csrf
    <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-300">{{ __('blog.subscribe.badge') }}</p>
    @if($compact)
        <h2 class="mt-1.5 text-base font-black leading-snug text-white">{{ __('blog.subscribe.title') }}</h2>
        <p class="mt-1.5 text-xs leading-relaxed text-zinc-400">{{ __('blog.subscribe.description') }}</p>
        <div class="mt-3 flex flex-col gap-2">
            <input type="email" name="email" required placeholder="maker@example.com" class="h-10 min-w-0 w-full rounded-xl border border-white/10 bg-zinc-950/70 px-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
            <button type="submit" class="h-10 shrink-0 rounded-xl bg-emerald-400 px-4 text-xs font-black text-zinc-950 shadow-md shadow-emerald-500/15 transition hover:bg-emerald-300">{{ __('blog.subscribe.button') }}</button>
        </div>
    @else
        <h2 class="mt-2 text-2xl font-black text-white">{{ __('blog.subscribe.title') }}</h2>
        <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('blog.subscribe.description') }}</p>
        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
            <input type="email" name="email" required placeholder="maker@example.com" class="h-12 min-w-0 flex-1 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
            <button type="submit" class="h-12 rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">{{ __('blog.subscribe.button') }}</button>
        </div>
    @endif
</form>
