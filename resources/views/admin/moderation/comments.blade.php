<x-layouts.admin
    :title="__('Модерація коментарів')"
    breadcrumb-current="{{ __('Коментарі') }}"
    active="moderation"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach(['pending' => __('Очікують'), 'published' => __('Опубліковані'), 'hidden' => __('Сховані'), 'all' => __('Усі')] as $key => $label)
            @php $active = ($status === $key || ($key === 'all' && ! in_array($status, ['pending','published','hidden'], true))); @endphp
            <a href="{{ route('admin.moderation.comments', $key === 'all' ? [] : ['status' => $key]) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ $active ? 'border-sky-300/40 bg-sky-300/[0.10] text-sky-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                {{ $label }}
                <span class="ml-1.5 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-black">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.section :title="__('Коментарі')">
        <div class="space-y-2">
            @forelse($comments as $c)
                <article class="rounded-xl border border-white/10 bg-white/[0.04] p-4 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-zinc-400">
                            <strong class="text-white">{{ $c->user?->name ?? '—' }}</strong>
                            @if($c->user?->email)<span class="text-[11px] text-zinc-500">{{ $c->user->email }}</span>@endif
                            <span>·</span>
                            <a href="{{ $c->product ? route('products.show', $c->product) : '#' }}" target="_blank" class="text-emerald-200 hover:text-emerald-100">{{ Str::limit($c->product?->localized('title'), 40) }}</a>
                            <span class="text-[11px] text-zinc-500">· {{ $c->created_at->translatedFormat('d M Y H:i') }}</span>
                        </div>
                        <x-ui.status :status="$c->status" size="xs" />
                    </div>
                    <p class="mt-2 text-zinc-300">{{ $c->body }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        @if($c->status !== 'published')
                            <form method="POST" action="{{ route('admin.moderation.comments.update', $c) }}">
                                @csrf @method('PATCH')<input type="hidden" name="status" value="published">
                                <button class="h-8 rounded-lg border border-emerald-300/30 bg-emerald-300/[0.06] px-3 text-xs font-bold text-emerald-200 hover:bg-emerald-300/[0.12]">{{ __('Затвердити') }}</button>
                            </form>
                        @endif
                        @if($c->status !== 'hidden')
                            <form method="POST" action="{{ route('admin.moderation.comments.update', $c) }}">
                                @csrf @method('PATCH')<input type="hidden" name="status" value="hidden">
                                <button class="h-8 rounded-lg border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-white hover:bg-white/[0.10]">{{ __('Сховати') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.moderation.comments.destroy', $c) }}" class="ml-auto" onsubmit="return confirm('{{ __('Видалити?') }}')">
                            @csrf @method('DELETE')
                            <button class="h-8 rounded-lg border border-rose-300/30 bg-rose-300/[0.06] px-3 text-xs font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('Видалити') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="py-12 text-center text-sm text-zinc-500">{{ __('Коментарів немає.') }}</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $comments->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
