<x-layouts.admin
    :title="__('Модерація фото друку')"
    breadcrumb-current="{{ __('Фото друку') }}"
    active="moderation"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap gap-1.5">
        @foreach(['pending' => __('Очікують'), 'approved' => __('Затверджені'), 'rejected' => __('Відхилені'), 'all' => __('Усі')] as $key => $label)
            @php $active = ($status === $key || ($key === 'all' && ! in_array($status, ['pending','approved','rejected'], true))); @endphp
            <a href="{{ route('admin.moderation.makes', $key === 'all' ? [] : ['status' => $key]) }}" class="inline-flex h-9 items-center rounded-xl border px-3 text-xs font-bold transition {{ $active ? 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100' : 'border-white/10 bg-white/[0.04] text-zinc-400 hover:bg-white/[0.08] hover:text-white' }}">
                {{ $label }}
                <span class="ml-1.5 rounded-full bg-white/10 px-1.5 py-0.5 text-[10px] font-black">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.section :title="__('Завантажені фото')">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse($makes as $m)
                <article class="group overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04]">
                    <div class="relative aspect-square overflow-hidden bg-zinc-950">
                        @if($m->image_path && \Storage::disk('public')->exists($m->image_path))
                            <a href="{{ \Storage::disk('public')->url($m->image_path) }}" target="_blank">
                                <img src="{{ \Storage::disk('public')->url($m->image_path) }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105">
                            </a>
                        @else
                            <div class="grid h-full place-items-center text-zinc-600">{{ __('файл відсутній') }}</div>
                        @endif
                        <x-ui.status :status="$m->status" size="xs" class="absolute right-2 top-2 backdrop-blur" />
                    </div>
                    <div class="p-4 text-xs">
                        <a href="{{ $m->product ? route('products.show', $m->product) : '#' }}" target="_blank" class="block truncate font-bold text-white hover:text-emerald-200">{{ $m->product?->localized('title') ?? '—' }}</a>
                        <p class="mt-1 truncate text-zinc-500">{{ $m->user?->name ?? '—' }} · {{ $m->created_at->translatedFormat('d M Y') }}</p>
                        @if($m->comment)<p class="mt-2 line-clamp-2 text-zinc-400">{{ $m->comment }}</p>@endif

                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                            @if($m->status !== 'approved')
                                <form method="POST" action="{{ route('admin.moderation.makes.update', $m) }}">
                                    @csrf @method('PATCH')<input type="hidden" name="status" value="approved">
                                    <button class="h-7 rounded-md border border-emerald-300/30 bg-emerald-300/[0.06] px-2.5 text-[11px] font-bold text-emerald-200 hover:bg-emerald-300/[0.12]">✓</button>
                                </form>
                            @endif
                            @if($m->status !== 'rejected')
                                <form method="POST" action="{{ route('admin.moderation.makes.update', $m) }}">
                                    @csrf @method('PATCH')<input type="hidden" name="status" value="rejected">
                                    <button class="h-7 rounded-md border border-amber-300/30 bg-amber-300/[0.06] px-2.5 text-[11px] font-bold text-amber-200 hover:bg-amber-300/[0.12]">✕</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.moderation.makes.destroy', $m) }}" class="ml-auto" onsubmit="return confirm('{{ __('Видалити?') }}')">
                                @csrf @method('DELETE')
                                <button class="h-7 rounded-md border border-rose-300/30 bg-rose-300/[0.06] px-2.5 text-[11px] font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('видалити') }}</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <p class="col-span-full py-12 text-center text-sm text-zinc-500">{{ __('Фото немає.') }}</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $makes->links() }}</div>
    </x-admin.section>
</x-layouts.admin>
