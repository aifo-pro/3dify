@php
    if (! \Illuminate\Support\Facades\Schema::hasTable('announcements')) return;
    $announcements = \App\Models\Announcement::active()->orderByDesc('id')->limit(5)->get();
    $user = auth()->user();
    $announcements = $announcements->filter(fn ($a) => $a->targetsUser($user))->values();
    if ($announcements->isEmpty()) return;

    $tones = [
        'info' => 'border-sky-300/40 bg-sky-300/[0.08] text-sky-100',
        'success' => 'border-emerald-300/40 bg-emerald-300/[0.08] text-emerald-100',
        'warning' => 'border-amber-300/40 bg-amber-300/[0.08] text-amber-100',
        'critical' => 'border-rose-300/40 bg-rose-300/[0.08] text-rose-100',
    ];
@endphp

<div class="space-y-1.5 px-4 pt-3 sm:px-6 lg:px-8">
    @foreach($announcements as $a)
        @php $cls = $tones[$a->level] ?? $tones['info']; @endphp
        <div
            x-data="{ show: ! window.localStorage.getItem('ann_dismiss_{{ $a->id }}') }"
            x-show="show" x-cloak
            class="flex flex-wrap items-center gap-2 rounded-xl border px-3 py-2 text-xs sm:text-sm {{ $cls }}"
        >
            <strong class="font-bold">{{ $a->title }}</strong>
            @if($a->body)<span class="opacity-90">{{ $a->body }}</span>@endif
            @if($a->cta_url)
                <a href="{{ $a->cta_url }}" class="ml-auto inline-flex items-center rounded-md border border-white/20 bg-white/10 px-2.5 py-0.5 text-[11px] font-bold hover:bg-white/20">{{ $a->cta_label ?: __('Дізнатися') }} →</a>
            @endif
            @if($a->is_dismissible)
                <button type="button" @click="window.localStorage.setItem('ann_dismiss_{{ $a->id }}', '1'); show = false" class="@if(! $a->cta_url) ml-auto @endif rounded-md p-1 hover:bg-white/10" aria-label="{{ __('Закрити') }}">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            @endif
        </div>
    @endforeach
</div>
