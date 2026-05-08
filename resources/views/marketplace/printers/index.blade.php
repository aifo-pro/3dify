<x-layouts.marketplace>
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="mb-8">
            <x-ui.badge>{{ __('Кабінет') }}</x-ui.badge>
            <h1 class="mt-3 text-3xl font-black text-white sm:text-4xl">{{ __('Мої принтери') }}</h1>
            <p class="mt-2 text-zinc-400">{{ __('Додайте параметри ваших принтерів — на сторінках моделей з\'явиться бейдж сумісності з областю друку та матеріалами.') }}</p>
        </header>

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        @endif

        @php $materialPresets = ['PLA', 'PETG', 'ABS', 'TPU', 'ASA', 'PC', 'PA (Nylon)', 'Resin', 'Resin Tough', 'Resin Flexible']; @endphp

        @if($printers->isNotEmpty())
            <div class="mb-8 grid gap-4 sm:grid-cols-2">
                @foreach($printers as $p)
                    <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5 {{ $p->is_default ? 'ring-2 ring-emerald-300/30' : '' }}">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <p class="text-base font-bold text-white">{{ $p->name }}</p>
                            @if($p->is_default)
                                <span class="inline-flex items-center rounded-full border border-emerald-300/40 bg-emerald-300/[0.10] px-2 py-0.5 text-[10px] font-bold text-emerald-100">{{ __('основний') }}</span>
                            @endif
                        </div>
                        <dl class="grid grid-cols-2 gap-2 text-xs text-zinc-400">
                            <div><dt class="text-zinc-500">{{ __('Технологія') }}</dt><dd class="text-zinc-200">{{ \App\Models\PrinterProfile::TECHNOLOGIES[$p->technology] ?? $p->technology }}</dd></div>
                            <div><dt class="text-zinc-500">{{ __('Сопло') }}</dt><dd class="text-zinc-200">{{ $p->nozzle ? $p->nozzle.' мм' : '—' }}</dd></div>
                            <div class="col-span-2"><dt class="text-zinc-500">{{ __('Область друку') }}</dt><dd class="text-zinc-200">{{ $p->bed_x ?? '—' }} × {{ $p->bed_y ?? '—' }} × {{ $p->bed_z ?? '—' }} мм</dd></div>
                            @if($p->materials)
                                <div class="col-span-2"><dt class="text-zinc-500">{{ __('Матеріали') }}</dt>
                                    <dd class="mt-1 flex flex-wrap gap-1">
                                        @foreach($p->materials as $m)<span class="rounded-full border border-white/10 bg-white/[0.04] px-2 py-0.5 text-[10px] text-zinc-300">{{ $m }}</span>@endforeach
                                    </dd>
                                </div>
                            @endif
                        </dl>
                        <div class="mt-4 flex gap-1.5">
                            @if(! $p->is_default)
                                <form method="POST" action="{{ route('printers.default', $p) }}">
                                    @csrf
                                    <button class="h-8 rounded-lg border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-white hover:bg-white/[0.10]">{{ __('Зробити основним') }}</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('printers.destroy', $p) }}" onsubmit="return confirm('{{ __('Видалити принтер?') }}')">
                                @csrf @method('DELETE')
                                <button class="h-8 rounded-lg border border-rose-300/30 bg-rose-300/[0.06] px-3 text-xs font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('Видалити') }}</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <x-ui.empty-state class="mb-8" :title="__('Принтерів ще немає')" :description="__('Додайте перший принтер нижче, щоб бачити сумісність на моделях.')" />
        @endif

        <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-1">
            <div class="rounded-[calc(1.5rem-4px)] bg-zinc-950/60 p-6">
                <h2 class="mb-1 text-lg font-bold text-white">{{ __('Додати принтер') }}</h2>
                <p class="mb-5 text-xs text-zinc-500">{{ __('Дані використовуються лише для перевірки сумісності — вони не публічні.') }}</p>

                <form method="POST" action="{{ route('printers.store') }}" class="grid gap-4 sm:grid-cols-2"
                      x-data="{ materials: [] }">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Назва') }}</label>
                        <input type="text" name="name" required maxlength="80" placeholder="Bambu Lab P1S, Voron 2.4, Prusa MK4..." class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Технологія') }}</label>
                        <select name="technology" class="mt-1 h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                            @foreach($technologies as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Сопло') }}</label>
                        <div class="relative mt-1">
                            <input type="number" step="0.05" min="0.1" max="2" name="nozzle" placeholder="0.4" class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 pr-10 font-mono text-sm tabular-nums text-white placeholder:text-zinc-600 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] uppercase tracking-wider text-zinc-500">мм</span>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Робоча зона (мм)') }}</label>
                        <div class="mt-1 grid grid-cols-3 gap-2">
                            @foreach(['bed_x' => 'X', 'bed_y' => 'Y', 'bed_z' => 'Z'] as $field => $label)
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-black uppercase tracking-widest text-emerald-300/70">{{ $label }}</span>
                                    <input type="number" name="{{ $field }}" min="30" max="2000" placeholder="256" class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 pl-7 pr-3 text-center font-mono text-sm font-semibold tabular-nums text-white placeholder:text-zinc-600 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-xs font-bold uppercase tracking-[0.14em] text-zinc-500">{{ __('Матеріали') }}</label>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @foreach($materialPresets as $m)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="materials[]" value="{{ $m }}" class="peer hidden">
                                    <span class="inline-flex h-7 items-center rounded-full border border-white/10 bg-white/[0.04] px-2.5 text-[11px] font-bold text-zinc-300 transition peer-checked:border-emerald-300/40 peer-checked:bg-emerald-300/[0.10] peer-checked:text-emerald-100">{{ $m }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-2 self-end text-xs text-zinc-300 sm:col-span-2">
                        <input type="checkbox" name="is_default" value="1" class="h-4 w-4 rounded border-white/20 bg-zinc-950 text-emerald-400">
                        {{ __('Зробити основним принтером') }}
                    </label>
                    <div class="sm:col-span-2">
                        <button class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Додати принтер') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-layouts.marketplace>
