<x-layouts.marketplace>
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8">
            <x-ui.badge>{{ __('Кабінет') }}</x-ui.badge>
            <h1 class="mt-3 text-3xl font-black text-white sm:text-4xl">{{ __('Збережені пошуки') }}</h1>
            <p class="mt-2 text-zinc-400">{{ __('Швидкий доступ до улюблених фільтрів. Увімкніть сповіщення на пошту — і ми надішлемо лист, коли з\'являться нові моделі за вашим запитом.') }}</p>
        </header>

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        @endif

        @if($searches->isEmpty())
            <x-ui.empty-state :title="__('Поки немає збережених пошуків')" :description="__('Перейдіть до каталогу, налаштуйте фільтри та натисніть «Зберегти пошук» — побачите його тут.')" :href="route('products.index')" :action="__('Перейти в каталог')" />
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($searches as $s)
                    <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h3 class="truncate text-base font-bold text-white">{{ $s->name }}</h3>
                            @if($s->notify_email)
                                <span class="inline-flex h-6 items-center gap-1 rounded-full border border-emerald-300/30 bg-emerald-300/[0.10] px-2 text-[10px] font-bold text-emerald-100">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    {{ __('сповіщення') }}
                                </span>
                            @endif
                        </div>

                        <div class="mb-4 flex flex-wrap gap-1.5">
                            @foreach($s->filters as $key => $val)
                                @if($val !== '' && $val !== null && $val !== false && (! is_array($val) || count($val) > 0))
                                    <span class="inline-flex h-6 items-center rounded-full border border-white/10 bg-white/[0.04] px-2 text-[10px] text-zinc-300">
                                        <strong class="mr-1 text-zinc-500">{{ $key }}:</strong>
                                        {{ is_array($val) ? implode(',', $val) : $val }}
                                    </span>
                                @endif
                            @endforeach
                            @if(empty(array_filter($s->filters, fn ($v) => $v !== '' && $v !== null && $v !== false && (! is_array($v) || count($v) > 0))))
                                <span class="text-xs italic text-zinc-500">{{ __('усі моделі') }}</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ $s->url() }}" class="inline-flex h-9 items-center rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Відкрити') }}</a>
                            <form method="POST" action="{{ route('saved-searches.destroy', $s) }}" onsubmit="return confirm('{{ __('Видалити збережений пошук?') }}')">
                                @csrf @method('DELETE')
                                <button class="inline-flex h-9 items-center rounded-xl border border-white/10 bg-white/[0.04] px-3 text-xs text-zinc-400 hover:border-rose-300/30 hover:bg-rose-300/[0.08] hover:text-rose-200">{{ __('Видалити') }}</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.marketplace>
