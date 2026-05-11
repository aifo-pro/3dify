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
    $absoluteCurrentUrl = $currentUrl ? url($currentUrl) : null;
    $inputId = 'asset-'.md5($name);
    $errorKey = preg_replace('/^assets\[(.+)\]$/', 'assets.$1', $name);
@endphp

<div
    class="rounded-2xl border border-white/10 bg-zinc-950/50 p-4"
    x-data="{ preview: null, fileName: '' }"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-white">{{ $label }}</p>
            @if($description)
                <p class="mt-0.5 text-xs leading-5 text-zinc-500">{{ $description }}</p>
            @endif
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-4">
        <template x-if="preview">
            <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl border border-emerald-300/30 bg-zinc-900 ring-2 ring-emerald-300/10">
                <img :src="preview" alt="" class="h-full w-full object-contain">
            </div>
        </template>
        @if($currentUrl)
            <div x-show="!preview" class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl border border-white/10 bg-zinc-900">
                <img src="{{ $currentUrl }}" alt="" class="h-full w-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="font-mono text-[10px] text-zinc-500 break-all">{{ $currentPath }}</p>
                @if($absoluteCurrentUrl)
                    <a href="{{ $absoluteCurrentUrl }}" target="_blank" rel="noopener" class="mt-1 inline-flex text-[11px] font-semibold text-emerald-200 hover:text-emerald-100">
                        {{ __('Відкрити файл') }}
                    </a>
                @endif
            </div>
        @else
            <div x-show="!preview" class="grid h-20 w-20 shrink-0 place-items-center rounded-2xl border border-dashed border-white/15 bg-zinc-950/40 text-zinc-500">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            </div>
        @endif

        <div class="min-w-0 flex-1">
            <label for="{{ $inputId }}" class="mb-2 inline-flex h-9 cursor-pointer items-center rounded-full border border-white/10 bg-white/10 px-4 text-xs font-bold text-zinc-100 transition hover:bg-white/15">
                {{ __('Вибрати файл') }}
            </label>
            <input
                id="{{ $inputId }}"
                type="file"
                name="{{ $name }}"
                accept="{{ $accept }}"
                class="sr-only"
                @change="
                    const file = $event.target.files && $event.target.files[0] ? $event.target.files[0] : null;
                    fileName = file ? file.name : '';
                    preview = file && file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
                "
            >
            <p x-show="fileName" x-cloak class="text-xs font-semibold text-emerald-200">
                {{ __('Обрано') }}: <span x-text="fileName"></span>
            </p>
            <p x-show="fileName" x-cloak class="mt-1 text-[11px] leading-5 text-amber-200/90">
                {{ __('Файл ще не збережено. Натисніть кнопку збереження внизу форми.') }}
            </p>
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
            @error($errorKey)
                <p class="mt-2 text-xs font-semibold text-rose-300">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-[11px] leading-5 text-zinc-500">
                {{ __('Після вибору натисніть кнопку збереження нижче. Максимум 8MB.') }}
            </p>
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
