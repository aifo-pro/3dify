<x-layouts.marketplace seo-title="Порівняння моделей · 3Dify">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-black text-white">Порівняння моделей</h1>
            <p class="mt-2 text-zinc-400">До 3 моделей одночасно.</p>
        </div>

        @if($products->isEmpty())
            <div class="rounded-2xl border border-white/10 bg-zinc-900/50 px-8 py-16 text-center">
                <p class="text-lg font-semibold text-white">Оберіть моделі для порівняння</p>
                <p class="mt-2 text-sm text-zinc-500">Натисніть "Порівняти" на картці моделі</p>
                <a href="{{ route('products.index') }}" class="mt-6 inline-flex items-center rounded-xl bg-emerald-400 px-6 py-2.5 text-sm font-black text-zinc-950 hover:bg-emerald-300">Перейти в каталог</a>
            </div>
        @else
            {{-- Product headers --}}
            <div class="overflow-x-auto">
                <table class="w-full table-fixed min-w-[640px]">
                    <colgroup>
                        <col class="w-40">
                        @foreach($products as $p)<col>@endforeach
                        @for($i = $products->count(); $i < 3; $i++)<col>@endfor
                    </colgroup>
                    <thead>
                        <tr>
                            <th></th>
                            @foreach($products as $p)
                                <th class="px-4 pb-6 text-left align-top">
                                    <div class="overflow-hidden rounded-xl bg-zinc-900 aspect-video mb-3">
                                        @if($p->cover_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($p->cover_path) }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <a href="{{ route('products.show', $p) }}" class="block text-sm font-bold text-white hover:text-emerald-300 line-clamp-2">{{ $p->localized('title') }}</a>
                                    <p class="mt-1 text-lg font-black text-emerald-300">{{ $p->display_price }}</p>
                                </th>
                            @endforeach
                            @for($i = $products->count(); $i < 3; $i++)
                                <th class="px-4 pb-6 text-left align-top">
                                    <div class="flex h-full items-start">
                                        <a href="{{ route('products.index') }}" class="mt-2 block rounded-xl border border-dashed border-white/10 px-4 py-8 text-center text-xs text-zinc-600 hover:border-white/20">+ Додати</a>
                                    </div>
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.05]">

                        @php
                            $rows = [
                                ['label' => 'Автор', 'fn' => fn($p) => $p->author?->displayName() ?? '—'],
                                ['label' => 'Категорія', 'fn' => fn($p) => $p->category?->localized('name') ?? '—'],
                                ['label' => 'Ліцензія', 'fn' => fn($p) => $p->license?->localized('name') ?? '—'],
                                ['label' => 'Файли', 'fn' => fn($p) => $p->files->where('type','source')->count().' файлів'],
                                ['label' => 'Формати', 'fn' => fn($p) => strtoupper($p->files->where('type','source')->pluck('extension')->unique()->join(', ')) ?: '—'],
                                ['label' => 'Розміри', 'fn' => fn($p) => $p->dim_x ? "{$p->dim_x}×{$p->dim_y}×{$p->dim_z} мм" : '—'],
                                ['label' => 'Рейтинг', 'fn' => fn($p) => $p->reviews->count() ? number_format($p->reviews->avg('rating'),1).'★ ('.$p->reviews->count().')' : '—'],
                                ['label' => 'Перегляди', 'fn' => fn($p) => number_format($p->views_count)],
                                ['label' => 'Завантажень', 'fn' => fn($p) => number_format($p->downloads_count)],
                                ['label' => 'Ціна', 'fn' => fn($p) => $p->display_price],
                            ];
                        @endphp

                        @foreach($rows as $row)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="py-3 pr-4 text-xs font-bold uppercase tracking-wider text-zinc-500">{{ $row['label'] }}</td>
                                @foreach($products as $p)
                                    <td class="px-4 py-3 text-sm text-zinc-300">{{ $row['fn']($p) }}</td>
                                @endforeach
                                @for($i = $products->count(); $i < 3; $i++)<td></td>@endfor
                            </tr>
                        @endforeach

                        <tr>
                            <td></td>
                            @foreach($products as $p)
                                <td class="px-4 py-4">
                                    <a href="{{ route('products.show', $p) }}" class="block w-full rounded-xl bg-emerald-400 py-2.5 text-center text-sm font-black text-zinc-950 hover:bg-emerald-300">
                                        {{ $p->is_free ? 'Отримати' : 'Купити' }}
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.marketplace>
