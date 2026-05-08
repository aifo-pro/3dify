<x-layouts.admin
    :title="__('Скарги на моделі')"
    :description="__('Перевіряйте поскарги, дисциплінарні рішення фіксуються в журналі дій.')"
    breadcrumb-current="{{ __('Скарги') }}"
    active="moderation"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach(['pending' => __('Очікують'), 'reviewed' => __('Переглянуті'), 'actioned' => __('З діями'), 'dismissed' => __('Відхилені'), 'all' => __('Усі')] as $key => $label)
            @php $href = route('admin.moderation.reports', $key === 'all' ? [] : ['status' => $key]); @endphp
            <a href="{{ $href }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ ($status === $key || ($key === 'all' && ! in_array($status, ['pending','reviewed','actioned','dismissed'], true))) ? 'border-rose-300/40 bg-rose-300/[0.10] text-rose-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                {{ $label }}
                <span class="ml-1.5 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-black">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.section :title="__('Скарги')">
        <div class="space-y-3">
            @forelse($reports as $r)
                <article id="report-{{ $r->id }}" class="scroll-mt-24 rounded-2xl border border-white/10 bg-white/[0.04] p-5 target:border-rose-300/50 target:ring-1 target:ring-rose-300/30">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            @if($r->product?->cover_path && \Storage::disk('public')->exists($r->product->cover_path))
                                <a href="{{ route('products.show', $r->product) }}" target="_blank">
                                    <img src="{{ \Storage::disk('public')->url($r->product->cover_path) }}" class="h-12 w-12 shrink-0 rounded-xl border border-white/10 object-cover">
                                </a>
                            @else
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl border border-white/10 bg-zinc-900 text-[10px] font-bold text-emerald-200">3D</span>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ $r->product ? route('products.show', $r->product) : '#' }}" target="_blank" class="block truncate font-semibold text-white hover:text-emerald-200">{{ $r->product?->localized('title') ?? __('модель видалено') }}</a>
                                <p class="mt-0.5 text-xs text-zinc-500">
                                    {{ __('Автор моделі') }}: {{ $r->product?->author?->name ?? '—' }}
                                    @if($r->product?->author)<span> · </span><a href="{{ route('admin.users', ['q' => $r->product->author->email]) }}" class="text-emerald-200 hover:text-emerald-100">{{ __('пошук') }}</a>@endif
                                </p>
                                <p class="mt-1.5 text-[11px] text-zinc-500">{{ __('Скаргу подав') }}: <strong class="text-zinc-300">{{ $r->user?->name ?? __('гість') }}</strong> ({{ $r->user?->email ?? '—' }}) · {{ $r->created_at->translatedFormat('d M Y · H:i') }}</p>
                            </div>
                        </div>
                        <x-ui.status :status="$r->status" />
                    </div>

                    <div class="mt-3 rounded-xl border border-white/5 bg-zinc-950/40 p-3 text-sm">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-rose-300">{{ __($r::REASONS[$r->reason] ?? $r->reason) }}</p>
                        @if($r->message)<p class="mt-1.5 text-zinc-300">{{ $r->message }}</p>@endif
                    </div>

                    <form method="POST" action="{{ route('admin.moderation.reports.update', $r) }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_220px_auto]">
                        @csrf @method('PATCH')
                        <input type="text" name="admin_notes" value="{{ $r->admin_notes }}" placeholder="{{ __('Нотатка') }}" class="h-9 rounded-lg border border-white/10 bg-zinc-950/60 px-2.5 text-xs text-white">
                        <select name="status" class="h-9 rounded-lg border border-white/10 bg-zinc-950/60 px-2.5 text-xs text-white">
                            <option value="pending" @selected($r->status === 'pending')>pending</option>
                            <option value="reviewed" @selected($r->status === 'reviewed')>reviewed</option>
                            <option value="dismissed" @selected($r->status === 'dismissed')>dismissed (no action)</option>
                            <option value="actioned" @selected($r->status === 'actioned')>actioned</option>
                        </select>
                        <button class="h-9 rounded-lg bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Зберегти') }}</button>
                    </form>
                </article>
            @empty
                <p class="py-12 text-center text-sm text-zinc-500">{{ __('Скарг немає.') }}</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $reports->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
