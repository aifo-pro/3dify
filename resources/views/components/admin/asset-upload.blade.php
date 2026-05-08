@props([
    'name',
    'label',
    'description' => null,
    'currentPath' => null,
    'accept' => 'image/*',
    'tab' => null,
])

@php
    $currentUrl = $currentPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($currentPath) : null;
@endphp

<div class="rounded-2xl border border-white/10 bg-zinc-950/50 p-4">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-white">{{ $label }}</p>
            @if($description)
                <p class="mt-0.5 text-xs leading-5 text-zinc-500">{{ $description }}</p>
            @endif
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-4">
        @if($currentUrl)
            <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl border border-white/10 bg-zinc-900">
                <img src="{{ $currentUrl }}" alt="" class="h-full w-full object-contain">
            </div>
            <p class="font-mono text-[10px] text-zinc-500 break-all">{{ $currentPath }}</p>
        @else
            <div class="grid h-20 w-20 shrink-0 place-items-center rounded-2xl border border-dashed border-white/15 bg-zinc-950/40 text-zinc-500">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            </div>
        @endif

        <div class="min-w-0 flex-1">
            <input
                type="file"
                name="{{ $name }}"
                accept="{{ $accept }}"
                class="block w-full text-xs text-zinc-400 file:mr-3 file:rounded-full file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-zinc-100 file:hover:bg-white/15"
            >
            @if($currentPath)
                <button
                    type="button"
                    onclick="event.preventDefault(); document.getElementById('delete-{{ md5($name) }}').submit();"
                    class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold text-rose-300 hover:text-rose-200"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                    {{ __('Видалити') }}
                </button>
            @endif
        </div>
    </div>
</div>

@if($currentPath)
    <form id="delete-{{ md5($name) }}" method="POST" action="{{ route('admin.settings.asset.delete') }}" class="hidden">
        @csrf
        @php
            $assetKey = preg_replace('/^assets\[(.+)\]$/', '$1', $name);
        @endphp
        <input type="hidden" name="key" value="{{ $assetKey }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
    </form>
@endif
