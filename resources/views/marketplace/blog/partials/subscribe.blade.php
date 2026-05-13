<form method="POST" action="{{ route('blog.subscribe') }}" class="rounded-3xl border border-emerald-300/20 bg-emerald-300/[0.07] p-6 shadow-2xl shadow-emerald-950/20">
    @csrf
    <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-300">{{ __('blog.subscribe.badge') }}</p>
    <h2 class="mt-2 text-2xl font-black text-white">{{ __('blog.subscribe.title') }}</h2>
    <p class="mt-2 text-sm leading-6 text-zinc-400">{{ __('blog.subscribe.description') }}</p>
    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
        <input type="email" name="email" required placeholder="maker@example.com" class="h-12 min-w-0 flex-1 rounded-2xl border border-white/10 bg-zinc-950/70 px-4 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
        <button class="h-12 rounded-2xl bg-emerald-400 px-6 text-sm font-black text-zinc-950 shadow-lg shadow-emerald-500/20 transition hover:bg-emerald-300">{{ __('blog.subscribe.button') }}</button>
    </div>
</form>
