@props(['product'])

@auth
    @php
        $printer = auth()->user()->defaultPrinter();
        $fits = $printer?->fits($product);
        $hasMaterials = $printer && is_array($printer->materials) && ! empty($printer->materials)
            && is_array($product->recommended_materials) && ! empty($product->recommended_materials);
        $matchingMaterials = $hasMaterials
            ? array_values(array_intersect(
                array_map('strtoupper', (array) $printer->materials),
                array_map('strtoupper', (array) $product->recommended_materials)
            ))
            : [];
    @endphp

    @if($printer && ($product->dim_x || $product->dim_y || $product->dim_z || $product->recommended_materials))
        @php
            $palette = match ($fits) {
                true => 'border-emerald-300/40 bg-emerald-300/[0.10] text-emerald-100',
                false => 'border-rose-300/40 bg-rose-300/[0.10] text-rose-100',
                default => 'border-white/10 bg-white/[0.04] text-zinc-300',
            };
            $title = match ($fits) {
                true => __('Підходить вашому принтеру :name', ['name' => $printer->name]),
                false => __('Завеликі габарити для :name', ['name' => $printer->name]),
                default => __('Не вистачає даних для перевірки сумісності.'),
            };
        @endphp
        <div class="rounded-2xl border p-3 text-xs {{ $palette }}" title="{{ $title }}">
            <div class="flex items-center gap-2 font-bold">
                @if($fits === true)
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ __('Підходить вашому принтеру') }}
                @elseif($fits === false)
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ __('Не поміщається на :name', ['name' => $printer->name]) }}
                @else
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ __('Перевірка сумісності недоступна') }}
                @endif
            </div>
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[10px] text-zinc-400">
                <span>{{ $printer->name }} · {{ $printer->bed_x }}×{{ $printer->bed_y }}×{{ $printer->bed_z }} мм</span>
                @if($product->dim_x || $product->dim_y || $product->dim_z)
                    <span>· {{ __('модель') }}: {{ $product->dim_x ?? '—' }}×{{ $product->dim_y ?? '—' }}×{{ $product->dim_z ?? '—' }} мм</span>
                @endif
            </div>
            @if($hasMaterials)
                <p class="mt-1.5 text-[10px] text-zinc-400">
                    {{ __('Матеріали') }}:
                    @if(empty($matchingMaterials))
                        <span class="text-amber-200">{{ __('немає збігів') }}</span>
                    @else
                        <span class="text-emerald-200">{{ implode(', ', $matchingMaterials) }}</span>
                    @endif
                </p>
            @endif
        </div>
    @elseif($printer === null)
        <a href="{{ route('printers.index') }}" class="block rounded-2xl border border-dashed border-white/15 bg-white/[0.02] p-3 text-xs text-zinc-400 transition hover:border-emerald-300/40 hover:bg-emerald-300/[0.04] hover:text-emerald-100">
            <span class="font-bold">{{ __('Додати свій принтер') }}</span>
            <span class="block text-[10px] text-zinc-500">{{ __('Покаже сумісність з усіма моделями автоматично.') }}</span>
        </a>
    @endif
@endauth
