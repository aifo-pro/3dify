<x-layouts.admin
    :title="__('Провалені завдання')"
    :description="__('Перегляньте, перезапустіть або видаліть завдання з failed_jobs.')"
    breadcrumb-current="{{ __('Failed jobs') }}"
    active="system"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    @if($unavailable)
        <div class="rounded-2xl border border-amber-300/30 bg-amber-300/[0.08] p-6 text-sm text-amber-100">
            {{ __('Таблицю failed_jobs не виявлено. Виконайте `php artisan queue:failed-table` та `php artisan migrate`.') }}
        </div>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('admin.system.queue.retry-all') }}" onsubmit="return confirm('{{ __('Перезапустити усі?') }}')">
                @csrf
                <button class="inline-flex h-9 items-center rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Retry all') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.system.queue.flush') }}" onsubmit="return confirm('{{ __('Видалити усі?') }}')">
                @csrf
                <button class="inline-flex h-9 items-center rounded-xl border border-rose-300/30 bg-rose-300/[0.08] px-4 text-xs font-bold text-rose-200 hover:bg-rose-300/[0.16]">{{ __('Flush all') }}</button>
            </form>
        </div>

        <x-admin.section :title="__(':n завдань', ['n' => $jobs->count()])">
            @if($jobs->isEmpty())
                <p class="py-12 text-center text-sm text-zinc-500">{{ __('Усе чисто, провалених завдань немає.') }}</p>
            @else
                <div class="space-y-3">
                    @foreach($jobs as $j)
                        <details class="rounded-xl border border-white/10 bg-white/[0.04] p-4">
                            <summary class="flex cursor-pointer flex-wrap items-center gap-3">
                                <span class="font-mono text-[10px] text-zinc-500">#{{ $j->id }}</span>
                                <strong class="text-sm text-white">{{ $j->job_name ?? $j->queue }}</strong>
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-2 py-0.5 text-[10px] font-bold text-zinc-300">{{ $j->queue }}</span>
                                <span class="text-[11px] text-zinc-500">{{ $j->failed_at }}</span>
                                <span class="ml-auto flex gap-1">
                                    <form method="POST" action="{{ route('admin.system.queue.retry', $j->uuid ?: $j->id) }}">
                                        @csrf
                                        <button class="h-7 rounded-md border border-emerald-300/30 bg-emerald-300/[0.08] px-2.5 text-[11px] font-bold text-emerald-200 hover:bg-emerald-300/[0.16]">retry</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.system.queue.delete', $j->uuid ?: $j->id) }}" onsubmit="return confirm('{{ __('Видалити?') }}')">
                                        @csrf
                                        <button class="h-7 rounded-md border border-rose-300/30 bg-rose-300/[0.08] px-2.5 text-[11px] font-bold text-rose-200 hover:bg-rose-300/[0.16]">forget</button>
                                    </form>
                                </span>
                            </summary>
                            <p class="mt-3 text-xs leading-5 text-rose-200">{{ $j->exception_first }}</p>
                            <pre class="mt-3 max-h-64 overflow-auto rounded-lg bg-zinc-950/60 p-3 text-[11px] leading-5 text-zinc-400">{{ $j->exception }}</pre>
                        </details>
                    @endforeach
                </div>
            @endif
        </x-admin.section>
    @endif
</x-layouts.admin>
