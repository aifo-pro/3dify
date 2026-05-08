<x-layouts.admin
    :title="__('Оголошення')"
    :description="__('Сайтові банери видимі тільки для обраної аудиторії та лише в активний період.')"
    breadcrumb-current="{{ __('Оголошення') }}"
    active="announcements"
>
    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    <x-admin.section :title="__('Створити нове')">
        <form method="POST" action="{{ route('admin.announcements.store') }}" class="grid gap-3 md:grid-cols-2">
            @csrf
            <x-admin.field name="title" :label="__('Заголовок')" required class="md:col-span-2" />
            <div class="md:col-span-2">
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Текст') }}</label>
                <textarea name="body" rows="3" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Рівень') }}</label>
                <select name="level" class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                    @foreach(\App\Models\Announcement::LEVELS as $l)<option value="{{ $l }}">{{ ucfirst($l) }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Аудиторія') }}</label>
                <select name="audience" class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                    @foreach(\App\Models\Announcement::AUDIENCES as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
                </select>
            </div>
            <x-admin.field name="cta_label" :label="__('CTA текст')" />
            <x-admin.field name="cta_url" :label="__('CTA URL')" />
            <x-admin.field name="starts_at" type="datetime-local" :label="__('Старт')" />
            <x-admin.field name="ends_at" type="datetime-local" :label="__('Завершення')" />
            <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40"> {{ __('Активне') }}</label>
            <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_dismissible" value="1" checked class="h-4 w-4 rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40"> {{ __('Користувач може закрити') }}</label>
            <div class="md:col-span-2">
                <button class="inline-flex h-10 items-center rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Створити') }}</button>
            </div>
        </form>
    </x-admin.section>

    <x-admin.section :title="__('Список')" class="mt-6">
        @if($announcements->isEmpty())
            <p class="py-12 text-center text-sm text-zinc-500">{{ __('Поки немає.') }}</p>
        @else
            <div class="space-y-2">
                @foreach($announcements as $a)
                    @php
                        $tone = match($a->level) {
                            'critical' => 'border-rose-300/30 bg-rose-300/[0.06]',
                            'warning' => 'border-amber-300/30 bg-amber-300/[0.06]',
                            'success' => 'border-emerald-300/30 bg-emerald-300/[0.06]',
                            default => 'border-sky-300/30 bg-sky-300/[0.06]',
                        };
                    @endphp
                    <details class="rounded-xl border {{ $tone }} p-3">
                        <summary class="flex cursor-pointer flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-2 py-0.5 text-[10px] font-bold uppercase">{{ $a->level }}</span>
                            <span class="rounded-full border border-white/10 bg-white/[0.04] px-2 py-0.5 text-[10px] font-bold uppercase">{{ $a->audience }}</span>
                            <strong class="text-white">{{ $a->title }}</strong>
                            @if($a->is_active)
                                <span class="rounded-full bg-emerald-300/[0.16] px-2 py-0.5 text-[10px] font-bold text-emerald-200">{{ __('активне') }}</span>
                            @else
                                <span class="rounded-full bg-zinc-800 px-2 py-0.5 text-[10px] font-bold text-zinc-400">{{ __('вимкнено') }}</span>
                            @endif
                            <span class="ml-auto flex gap-1.5">
                                <form method="POST" action="{{ route('admin.announcements.toggle', $a) }}">@csrf @method('PATCH')<button class="h-7 rounded-md border border-white/10 bg-white/[0.04] px-2.5 text-[11px] font-bold text-white hover:bg-white/[0.10]">{{ $a->is_active ? __('вимк.') : __('увімк.') }}</button></form>
                                <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" onsubmit="return confirm('?')">@csrf @method('DELETE')<button class="h-7 rounded-md border border-rose-300/30 bg-rose-300/[0.06] px-2.5 text-[11px] font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('видалити') }}</button></form>
                            </span>
                        </summary>
                        <div class="mt-2 space-y-1 px-1 text-xs text-zinc-300">
                            @if($a->body)<p>{{ $a->body }}</p>@endif
                            <p class="text-[11px] text-zinc-500">{{ __('Період') }}: {{ optional($a->starts_at)->format('d.m.Y H:i') ?? '∞' }} → {{ optional($a->ends_at)->format('d.m.Y H:i') ?? '∞' }}</p>
                            @if($a->cta_label)
                                <p class="text-[11px] text-zinc-500">CTA: <a href="{{ $a->cta_url }}" class="text-emerald-200" target="_blank">{{ $a->cta_label }}</a></p>
                            @endif

                            <form method="POST" action="{{ route('admin.announcements.update', $a) }}" class="mt-3 grid gap-2 md:grid-cols-2">
                                @csrf @method('PATCH')
                                <x-admin.field name="title" :label="__('Заголовок')" :value="$a->title" required class="md:col-span-2" />
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Текст') }}</label>
                                    <textarea name="body" rows="2" class="w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white">{{ $a->body }}</textarea>
                                </div>
                                <select name="level" class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                                    @foreach(\App\Models\Announcement::LEVELS as $l)<option value="{{ $l }}" @selected($a->level === $l)>{{ $l }}</option>@endforeach
                                </select>
                                <select name="audience" class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white">
                                    @foreach(\App\Models\Announcement::AUDIENCES as $au)<option value="{{ $au }}" @selected($a->audience === $au)>{{ $au }}</option>@endforeach
                                </select>
                                <x-admin.field name="cta_label" :label="__('CTA текст')" :value="$a->cta_label" />
                                <x-admin.field name="cta_url" :label="__('CTA URL')" :value="$a->cta_url" />
                                <x-admin.field name="starts_at" type="datetime-local" :label="__('Старт')" :value="optional($a->starts_at)->format('Y-m-d\TH:i')" />
                                <x-admin.field name="ends_at" type="datetime-local" :label="__('Завершення')" :value="optional($a->ends_at)->format('Y-m-d\TH:i')" />
                                <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($a->is_active) class="h-4 w-4 rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40"> {{ __('Активне') }}</label>
                                <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_dismissible" value="1" @checked($a->is_dismissible) class="h-4 w-4 rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40"> {{ __('Користувач може закрити') }}</label>
                                <div class="md:col-span-2">
                                    <button class="inline-flex h-9 items-center rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Зберегти') }}</button>
                                </div>
                            </form>
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </x-admin.section>
</x-layouts.admin>
