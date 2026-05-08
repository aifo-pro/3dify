<x-layouts.admin
    :title="__('Модерація рев\'ю')"
    :description="__('Сховати, відновити або видалити окремі рев\'ю користувачів.')"
    breadcrumb-current="{{ __('Рев\'ю') }}"
    active="moderation"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <form method="GET" class="mb-5 flex flex-wrap gap-1.5">
        @foreach(['pending' => __('Очікують'), 'published' => __('Опубліковані'), 'hidden' => __('Сховані'), 'all' => __('Усі')] as $key => $label)
            @php $active = ($status === $key || ($key === 'all' && ! in_array($status, ['pending','published','hidden'], true))); @endphp
            <a href="{{ route('admin.moderation.reviews', $key === 'all' ? [] : ['status' => $key]) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ $active ? 'border-violet-300/40 bg-violet-300/[0.10] text-violet-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                {{ $label }}
                <span class="ml-1.5 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-black">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
        <select name="rating" onchange="this.form.submit()" class="h-9 rounded-xl border border-white/10 bg-zinc-950/60 px-2.5 text-xs text-white">
            <option value="">{{ __('Будь-який рейтинг') }}</option>
            @for($s = 1; $s <= 5; $s++)<option value="{{ $s }}" @selected(request('rating') == $s)>{{ $s }} ★</option>@endfor
        </select>
    </form>

    <x-admin.section :title="__('Рев\'ю')">
        <div class="space-y-3">
            @forelse($reviews as $rev)
                <article class="rounded-2xl border border-white/10 bg-white/[0.04] p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex items-start gap-3">
                            @if($rev->product?->cover_path && \Storage::disk('public')->exists($rev->product->cover_path))
                                <img src="{{ \Storage::disk('public')->url($rev->product->cover_path) }}" class="h-12 w-12 shrink-0 rounded-xl border border-white/10 object-cover">
                            @else
                                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl border border-white/10 bg-zinc-900 text-[10px] font-bold text-emerald-200">3D</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-white">{{ $rev->product?->localized('title') ?? __('модель видалено') }}</p>
                                <div class="mt-0.5 flex items-center gap-2 text-xs">
                                    <span class="flex items-center gap-0.5 text-amber-300">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="{{ $i <= $rev->rating ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                        @endfor
                                    </span>
                                    <span class="font-bold text-zinc-200">{{ $rev->user?->name }}</span>
                                    @if($rev->is_verified_buyer)
                                        <span class="rounded-full border border-emerald-300/30 bg-emerald-300/[0.10] px-2 py-0.5 text-[10px] font-bold text-emerald-200">{{ __('покупець') }}</span>
                                    @endif
                                    <span class="text-zinc-500">· {{ $rev->created_at->translatedFormat('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <x-ui.status :status="$rev->status" />
                    </div>

                    @if($rev->body)
                        <p class="mt-3 rounded-xl border border-white/5 bg-zinc-950/40 p-3 text-sm leading-6 text-zinc-300">{{ $rev->body }}</p>
                    @endif

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('admin.moderation.reviews.update', $rev) }}" class="flex gap-1.5">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $rev->status === 'hidden' ? 'published' : 'hidden' }}">
                            <button class="h-8 rounded-lg border border-white/10 bg-white/[0.04] px-3 text-xs font-bold text-white hover:bg-white/[0.10]">{{ $rev->status === 'hidden' ? __('Показати') : __('Сховати') }}</button>
                        </form>
                        @if($rev->status !== 'published')
                            <form method="POST" action="{{ route('admin.moderation.reviews.update', $rev) }}" class="flex gap-1.5">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="published">
                                <button class="h-8 rounded-lg border border-emerald-300/30 bg-emerald-300/[0.06] px-3 text-xs font-bold text-emerald-200 hover:bg-emerald-300/[0.12]">{{ __('Затвердити') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.moderation.reviews.destroy', $rev) }}" onsubmit="return confirm('{{ __('Видалити рев\'ю?') }}')" class="ml-auto">
                            @csrf @method('DELETE')
                            <button class="h-8 rounded-lg border border-rose-300/30 bg-rose-300/[0.06] px-3 text-xs font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('Видалити') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="py-12 text-center text-sm text-zinc-500">{{ __('Рев\'ю немає.') }}</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $reviews->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
