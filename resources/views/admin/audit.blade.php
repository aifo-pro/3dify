<x-layouts.admin
    :title="__('Журнал дій')"
    :description="__('Хто, що, коли і з якої IP. Адміністративні дії автоматично логуються.')"
    breadcrumb-current="{{ __('Audit log') }}"
>
    <x-admin.section :title="__('Фільтри')">
        <form method="GET" class="grid gap-3 sm:grid-cols-[1fr_220px_180px_auto]">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Пошук за дією, ресурсом, IP…') }}" class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500">
            <select name="action" class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                <option value="">{{ __('Будь-яка дія') }}</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
                @endforeach
            </select>
            <input type="number" name="user_id" value="{{ request('user_id') }}" placeholder="user_id" class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
            <button class="h-10 rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Фільтр') }}</button>
        </form>
    </x-admin.section>

    <x-admin.section :title="__('Записи')">
        <div class="overflow-hidden rounded-2xl border border-white/10">
            <table class="min-w-full divide-y divide-white/5 text-sm">
                <thead class="bg-white/[0.03] text-left text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Час') }}</th>
                        <th class="px-4 py-3">{{ __('Користувач') }}</th>
                        <th class="px-4 py-3">{{ __('Дія') }}</th>
                        <th class="px-4 py-3">{{ __('Об\'єкт') }}</th>
                        <th class="px-4 py-3">{{ __('Зміни') }}</th>
                        <th class="px-4 py-3">{{ __('IP') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-4 py-3 align-top text-xs text-zinc-400 whitespace-nowrap">{{ $log->created_at->translatedFormat('d M H:i:s') }}</td>
                            <td class="px-4 py-3 align-top">
                                @if($log->user)
                                    <p class="text-sm text-white">{{ $log->user->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $log->user->email }}</p>
                                @else
                                    <span class="text-xs italic text-zinc-500">{{ __('система') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="rounded-md bg-emerald-300/[0.10] px-2 py-0.5 font-mono text-xs font-bold text-emerald-200">{{ $log->action }}</span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if($log->subject_type)
                                    <p class="text-xs text-zinc-300">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</p>
                                @else
                                    <span class="text-xs italic text-zinc-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if(! empty($log->changes))
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-zinc-400 hover:text-white">{{ __('переглянути') }}</summary>
                                        <pre class="mt-2 max-w-md overflow-auto rounded-lg border border-white/10 bg-zinc-950/60 p-2 text-[11px] text-zinc-300">{{ json_encode($log->changes, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @else
                                    <span class="text-xs italic text-zinc-600">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-zinc-500 font-mono">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Записів немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $logs->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
