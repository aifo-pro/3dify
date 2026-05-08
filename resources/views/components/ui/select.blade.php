@props(['label' => null, 'helper' => null, 'error' => null])

<label class="grid gap-2 text-sm font-medium text-zinc-200">
    @if($label)
        <span>{{ $label }}</span>
    @endif
    <select {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-white/10 bg-zinc-950/80 px-4 py-3 text-white shadow-inner shadow-black/20 transition focus:border-emerald-300 focus:ring-emerald-300']) }}>
        {{ $slot }}
    </select>
    @if($helper)
        <span class="text-xs leading-5 text-zinc-500">{{ $helper }}</span>
    @endif
    @if($error)
        <span class="text-xs text-red-300">{{ $error }}</span>
    @endif
</label>
