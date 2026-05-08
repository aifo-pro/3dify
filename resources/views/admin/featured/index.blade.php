<x-layouts.admin
    :title="__('Featured-моделі')"
    :description="__('Виділяйте найкращі публікації на головну. Перетягуйте, щоб змінити порядок.')"
    breadcrumb-current="{{ __('Featured') }}"
    active="products"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <x-admin.section :title="__('Featured (порядок збережено в featured_order)')">
            @if($featured->isEmpty())
                <p class="py-12 text-center text-sm text-zinc-500">{{ __('Поки немає featured-моделей.') }}</p>
            @else
                <ul id="featured-list" class="space-y-2">
                    @foreach($featured as $i => $p)
                        <li data-id="{{ $p->id }}" class="flex items-center gap-3 rounded-xl border border-amber-300/20 bg-amber-300/[0.04] p-3 cursor-grab">
                            <span class="grid h-7 w-7 place-items-center rounded-md bg-amber-300/[0.16] text-xs font-black text-amber-100">{{ $i + 1 }}</span>
                            @if($p->cover_path)
                                <img src="{{ Storage::disk('public')->url($p->cover_path) }}" class="h-10 w-10 rounded-lg object-cover">
                            @else
                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-zinc-900 text-[10px] font-bold text-emerald-200">3D</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-white">{{ $p->localized('title') }}</p>
                                <p class="truncate text-xs text-zinc-500">{{ $p->author?->name }} · {{ $p->display_price }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.products.toggle-featured', $p) }}">
                                @csrf @method('PATCH')
                                <button class="h-7 rounded-md border border-rose-300/30 bg-rose-300/[0.06] px-2.5 text-[11px] font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('прибрати') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 text-xs text-zinc-500">{{ __('Перетягуйте картки, щоб змінити порядок.') }}</p>
            @endif
        </x-admin.section>

        <x-admin.section :title="__('Кандидати (опубліковані)')">
            @if($candidates->isEmpty())
                <p class="py-12 text-center text-sm text-zinc-500">{{ __('Усі опубліковані вже у Featured.') }}</p>
            @else
                <div class="grid gap-2">
                    @foreach($candidates as $c)
                        <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.04] p-3">
                            @if($c->cover_path)
                                <img src="{{ Storage::disk('public')->url($c->cover_path) }}" class="h-9 w-9 rounded-lg object-cover">
                            @else
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-zinc-900 text-[10px] font-bold text-emerald-200">3D</span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white">{{ $c->localized('title') }}</p>
                                <p class="truncate text-[11px] text-zinc-500">{{ $c->author?->name }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.products.toggle-featured', $c) }}">
                                @csrf @method('PATCH')
                                <button class="h-7 rounded-md border border-amber-300/30 bg-amber-300/[0.06] px-2.5 text-[11px] font-bold text-amber-200 hover:bg-amber-300/[0.12]">+ {{ __('Featured') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-admin.section>
    </div>

    @push('scripts')
    <script>
        (function () {
            const list = document.getElementById('featured-list');
            if (! list) return;
            let dragged = null;
            list.addEventListener('dragstart', (e) => {
                dragged = e.target.closest('li');
                if (dragged) dragged.style.opacity = '0.4';
            });
            list.addEventListener('dragend', () => { if (dragged) dragged.style.opacity = ''; dragged = null; persist(); });
            list.addEventListener('dragover', (e) => {
                e.preventDefault();
                const target = e.target.closest('li');
                if (! target || target === dragged || ! dragged) return;
                const rect = target.getBoundingClientRect();
                const after = (e.clientY - rect.top) / rect.height > 0.5;
                target.parentNode.insertBefore(dragged, after ? target.nextSibling : target);
            });
            list.querySelectorAll('li').forEach(li => li.draggable = true);

            function persist() {
                const ids = Array.from(list.querySelectorAll('li')).map(li => parseInt(li.dataset.id, 10));
                fetch(@js(route('admin.products.featured.reorder')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', 'Accept': 'application/json' },
                    body: JSON.stringify({ order: ids }),
                }).then(() => {
                    list.querySelectorAll('li').forEach((li, i) => li.firstElementChild.textContent = i + 1);
                });
            }
        })();
    </script>
    @endpush
</x-layouts.admin>
