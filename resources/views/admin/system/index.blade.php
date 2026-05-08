@php
    $fmt = function (int $bytes): string {
        $units = ['B','KB','MB','GB','TB'];
        $i = 0;
        while ($bytes > 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return number_format($bytes, $i ? 1 : 0).' '.$units[$i];
    };
@endphp
<x-layouts.admin
    :title="__('Системні інструменти')"
    :description="__('Maintenance, кеш, черги, логи та health-check одним кліком.')"
    breadcrumb-current="{{ __('Системні') }}"
    active="system"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    {{-- Health snapshot --}}
    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.kpi-card :label="__('PHP')" :value="$stats['php']" />
        <x-admin.kpi-card :label="__('Laravel')" :value="$stats['laravel']" />
        <x-admin.kpi-card :label="__('Середовище')" :value="$stats['env']" />
        <x-admin.kpi-card :label="__('Debug')" :value="$stats['debug']" />
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.kpi-card :label="__('Кеш')" :value="$stats['cache']" />
        <x-admin.kpi-card :label="__('Черга')" :value="$stats['queue']" />
        <x-admin.kpi-card :label="__('БД')" :value="$stats['driver']" />
        <x-admin.kpi-card :label="__('Mailer')" :value="$stats['mail']" />
    </div>

    <div class="mt-3 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.kpi-card :label="__('Запис у storage')" :value="$fmt($stats['storage_used'])" />
        <x-admin.kpi-card :label="__('Логи')" :value="$fmt($stats['logs_size'])" />
        <x-admin.kpi-card :label="__('Завдання у черзі')" :value="$jobsCount" />
        <x-admin.kpi-card :label="__('Failed jobs')" :value="$failedJobsCount" :tone="$failedJobsCount > 0 ? 'amber' : 'emerald'" />
    </div>

    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        {{-- Maintenance --}}
        <x-admin.section :title="__('Режим обслуговування')">
            <p class="mb-4 text-xs leading-6 text-zinc-500">
                {{ __('Якщо увімкнено — сайт повертає 503 для всіх неавторизованих відвідувачів. Адміни далі бачать панель.') }}
            </p>
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border {{ $stats['maintenance'] ? 'border-rose-300/40 bg-rose-300/[0.08]' : 'border-emerald-300/30 bg-emerald-300/[0.06]' }} p-4">
                <span class="grid h-10 w-10 place-items-center rounded-xl {{ $stats['maintenance'] ? 'bg-rose-300/[0.20] text-rose-100' : 'bg-emerald-300/[0.20] text-emerald-100' }}">
                    @if($stats['maintenance'])
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                    @else
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    @endif
                </span>
                <div class="flex-1">
                    <p class="text-sm font-bold text-white">{{ $stats['maintenance'] ? __('Сайт ВИМКНЕНО для відвідувачів') : __('Сайт працює') }}</p>
                    <p class="text-[11px] text-zinc-400">{{ __('Перемикання негайне.') }}</p>
                </div>
                <form method="POST" action="{{ route('admin.system.maintenance') }}" onsubmit="return confirm('{{ __('Підтверджуєте?') }}')">
                    @csrf
                    <button class="inline-flex h-9 items-center rounded-xl px-4 text-xs font-bold {{ $stats['maintenance'] ? 'bg-emerald-400 text-zinc-950 hover:bg-emerald-300' : 'bg-rose-400 text-white hover:bg-rose-500' }}">
                        {{ $stats['maintenance'] ? __('Увімкнути сайт') : __('Перевести на технічну') }}
                    </button>
                </form>
            </div>
        </x-admin.section>

        {{-- Cache --}}
        <x-admin.section :title="__('Кеш')">
            <p class="mb-4 text-xs leading-6 text-zinc-500">{{ __('Очистка прискорює застосування змін config/route/view після деплою.') }}</p>
            <div class="grid grid-cols-2 gap-2">
                @foreach([
                    'all' => __('Все'),
                    'cache' => __('Програмний кеш'),
                    'config' => __('Config'),
                    'route' => __('Route'),
                    'view' => __('View'),
                    'opcache' => __('Optimize:clear'),
                ] as $type => $label)
                    <form method="POST" action="{{ route('admin.system.cache') }}">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <button class="w-full rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2.5 text-xs font-bold text-white transition hover:bg-white/[0.10]">{{ $label }}</button>
                    </form>
                @endforeach
            </div>
        </x-admin.section>

        {{-- Queue --}}
        <x-admin.section :title="__('Черги завдань')">
            <p class="mb-4 text-xs leading-6 text-zinc-500">{{ __('Failed jobs можна перезапустити пакетно або точково.') }}</p>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.system.failed-jobs') }}" class="inline-flex h-9 items-center rounded-xl bg-amber-400 px-4 text-xs font-bold text-zinc-950 hover:bg-amber-300">
                    {{ __('Переглянути failed_jobs') }} <span class="ml-2 rounded-full bg-zinc-900/30 px-1.5 py-0.5 text-[10px]">{{ $failedJobsCount }}</span>
                </a>
                <form method="POST" action="{{ route('admin.system.queue.retry-all') }}" onsubmit="return confirm('{{ __('Перезапустити усі провалені?') }}')">
                    @csrf
                    <button class="inline-flex h-9 items-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-xs font-bold text-white hover:bg-white/[0.10]">{{ __('Retry all') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.system.queue.flush') }}" onsubmit="return confirm('{{ __('Видалити усі провалені?') }}')">
                    @csrf
                    <button class="inline-flex h-9 items-center rounded-xl border border-rose-300/30 bg-rose-300/[0.08] px-4 text-xs font-bold text-rose-200 hover:bg-rose-300/[0.16]">{{ __('Flush failed') }}</button>
                </form>
            </div>
        </x-admin.section>

        {{-- Logs --}}
        <x-admin.section :title="__('Логи')">
            <p class="mb-4 text-xs leading-6 text-zinc-500">{{ __('Перегляньте останні рядки логів Laravel прямо тут.') }}</p>
            <a href="{{ route('admin.system.logs') }}" class="inline-flex h-9 items-center rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Відкрити переглядач логів') }}</a>
        </x-admin.section>
    </div>
</x-layouts.admin>
