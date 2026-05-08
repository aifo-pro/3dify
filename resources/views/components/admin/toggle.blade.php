@props([
    'name',
    'label',
    'description' => null,
    'checked' => false,
])

<label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-white/10 bg-zinc-950/50 px-4 py-3 transition hover:border-white/20">
    <span class="min-w-0">
        <span class="block text-sm font-semibold text-white">{{ $label }}</span>
        @if($description)
            <span class="mt-0.5 block text-xs leading-5 text-zinc-500">{{ $description }}</span>
        @endif
    </span>
    <span class="relative inline-flex h-5 shrink-0 items-center">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) {{ $attributes }} class="peer h-5 w-9 cursor-pointer appearance-none rounded-full bg-white/10 transition-colors checked:bg-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-300/40">
        <span class="pointer-events-none absolute left-0.5 top-1/2 h-4 w-4 -translate-y-1/2 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
    </span>
</label>
