<x-layouts.admin
    :title="__('Розсилка та підписки')"
    :description="__('Підписники на новини, історія розсилок та форма для блясту.')"
    breadcrumb-current="{{ __('Newsletter') }}"
    active="newsletter"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.kpi-card :label="__('Активних підписників')" :value="$totals['active']" tone="emerald" />
        <x-admin.kpi-card :label="__('Відписалися')" :value="$totals['unsubscribed']" tone="rose" />
        <x-admin.kpi-card :label="__('За цей місяць')" :value="$totals['this_month']" />
        <x-admin.kpi-card :label="__('Авторів у системі')" :value="$totals['authors']" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_1fr]">
        <x-admin.section :title="__('Новий блясту')">
            <form method="POST" action="{{ route('admin.newsletter.blast') }}" class="grid gap-3" onsubmit="return confirm('{{ __('Розіслати лист усім обраним підписникам?') }}')">
                @csrf
                <x-admin.field name="subject" :label="__('Тема')" required />
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Текст листа (HTML/plain)') }}</label>
                    <textarea name="body" rows="9" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white" required></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Аудиторія') }}</label>
                    <select name="audience" class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                        <option value="all_subscribers">{{ __('Усі активні підписники') }} ({{ $totals['active'] }})</option>
                        <option value="authors">{{ __('Лише автори, які підписані') }}</option>
                        <option value="buyers">{{ __('Лише покупці, які підписані') }}</option>
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 text-xs">
                    <input type="checkbox" name="confirm" value="1" required class="h-4 w-4 rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40">
                    {{ __('Я перевірив(ла) контент і підтверджую розсилку') }}
                </label>
                <button class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Поставити в чергу') }}</button>
            </form>
        </x-admin.section>

        <x-admin.section :title="__('Останні розсилки')">
            @if($blasts->isEmpty())
                <p class="py-8 text-center text-xs text-zinc-500">{{ __('Поки розсилок не було.') }}</p>
            @else
                <ul class="space-y-2">
                    @foreach($blasts as $b)
                        <li class="rounded-xl border border-white/10 bg-white/[0.04] p-3 text-sm">
                            <p class="truncate font-bold text-white">{{ $b->subject }}</p>
                            <p class="mt-1 text-[11px] text-zinc-500">
                                {{ $b->audience }} · {{ $b->recipients_count }} {{ __('одержувачів') }} ·
                                {{ $b->createdBy?->name }} · {{ optional($b->sent_at)->translatedFormat('d M H:i') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-admin.section>
    </div>

    <x-admin.section :title="__('Підписники')" class="mt-6">
        <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('Email або імʼя…') }}" class="h-9 w-64 rounded-full border border-white/10 bg-white/[0.04] px-3 text-sm text-white placeholder:text-zinc-500">
            <select name="status" onchange="this.form.submit()" class="h-9 rounded-full border border-white/10 bg-white/[0.04] px-3 text-xs text-white">
                <option value="active" @selected($status === 'active')>{{ __('Активні') }}</option>
                <option value="unsubscribed" @selected($status === 'unsubscribed')>{{ __('Відписалися') }}</option>
                <option value="all" @selected($status === 'all')>{{ __('Усі') }}</option>
            </select>
            <button class="inline-flex h-9 items-center rounded-full bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Знайти') }}</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-950/40 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                        <th class="px-4 py-3">{{ __('Імʼя') }}</th>
                        <th class="px-4 py-3">{{ __('Локаль') }}</th>
                        <th class="px-4 py-3">{{ __('Джерело') }}</th>
                        <th class="px-4 py-3">{{ __('Підписано') }}</th>
                        <th class="px-4 py-3">{{ __('Статус') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($subs as $s)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-2.5 font-mono text-xs text-zinc-200">{{ $s->email }}</td>
                            <td class="px-4 py-2.5 text-zinc-300">{{ $s->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs uppercase text-zinc-400">{{ $s->locale ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">{{ $s->source }}</td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">{{ $s->created_at->format('d.m.Y') }}</td>
                            <td class="px-4 py-2.5">
                                @if($s->unsubscribed_at)
                                    <span class="inline-flex items-center rounded-full border border-rose-300/30 bg-rose-300/[0.08] px-2 py-0.5 text-[10px] font-bold text-rose-200">{{ __('відписаний') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-emerald-300/30 bg-emerald-300/[0.08] px-2 py-0.5 text-[10px] font-bold text-emerald-200">{{ __('активний') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <form method="POST" action="{{ route('admin.newsletter.destroy', $s) }}" onsubmit="return confirm('{{ __('Видалити підписника?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex h-7 items-center rounded-md border border-rose-300/30 bg-rose-300/[0.06] px-2.5 text-[11px] font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('видалити') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Підписників немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $subs->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
