<x-layouts.admin
    :title="__('API-токени')"
    :description="__('Персональні токени для інтеграцій. Зберігаються лише як SHA-256 хеші.')"
    breadcrumb-current="{{ __('API tokens') }}"
    active="api-tokens"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    @if(session('plain_token'))
        <div class="mb-6 rounded-2xl border border-amber-300/40 bg-amber-300/[0.08] p-5">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-amber-300">{{ __('Новий токен') }}</p>
            <p class="mt-2 break-all rounded-lg bg-zinc-950/60 px-3 py-2 font-mono text-sm text-amber-100 select-all">{{ session('plain_token') }}</p>
            <p class="mt-2 text-xs text-amber-200">{{ __('Скопіюйте та збережіть зараз — він не буде показаний знову.') }}</p>
        </div>
    @endif

    <x-admin.section :title="__('Створити новий токен')">
        <form method="POST" action="{{ route('admin.api-tokens.store') }}" class="grid gap-3 md:grid-cols-3">
            @csrf
            <x-admin.field name="name" :label="__('Імʼя')" required placeholder="my-integration" />
            <x-admin.field name="abilities" :label="__('Abilities (через кому)')" placeholder="*" />
            <x-admin.field name="expires_at" type="datetime-local" :label="__('Закінчення (опц.)')" />
            <div class="md:col-span-3">
                <button class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Створити токен') }}</button>
            </div>
        </form>
    </x-admin.section>

    <x-admin.section :title="__('Активні токени')" class="mt-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-950/40 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Імʼя') }}</th>
                        <th class="px-4 py-3">{{ __('Власник') }}</th>
                        <th class="px-4 py-3">{{ __('Abilities') }}</th>
                        <th class="px-4 py-3">{{ __('Створено') }}</th>
                        <th class="px-4 py-3">{{ __('Останнє використання') }}</th>
                        <th class="px-4 py-3">{{ __('Закінчення') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($tokens as $t)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-2.5 font-mono text-xs text-white">{{ $t->name }}</td>
                            <td class="px-4 py-2.5 text-zinc-300">{{ $t->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-[11px] text-zinc-400">{{ implode(', ', $t->abilities ?? ['*']) }}</td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">{{ $t->created_at->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">{{ optional($t->last_used_at)->diffForHumans() ?: '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">{{ optional($t->expires_at)->format('d.m.Y') ?: __('ніколи') }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <form method="POST" action="{{ route('admin.api-tokens.destroy', $t) }}" onsubmit="return confirm('{{ __('Відкликати токен?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex h-7 items-center rounded-md border border-rose-300/30 bg-rose-300/[0.06] px-2.5 text-[11px] font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('відкликати') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Токенів немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.section>
</x-layouts.admin>
