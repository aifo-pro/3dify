<x-layouts.admin
    :title="__('Розсилка та підписки')"
    :description="__('Готові шаблони на основі статистики, історія розсилок і керування підписниками.')"
    breadcrumb-current="{{ __('Newsletter') }}"
    active="newsletter"
>
    @php
        $templateGroups = [
            'highlights' => ['label' => __('На основі статистики'), 'icon' => 'sparkles'],
            'community' => ['label' => __('Спільнота'), 'icon' => 'users'],
            'system' => ['label' => __('Системні'), 'icon' => 'cog'],
        ];

        $templateIcon = function (string $name): string {
            return match ($name) {
                'flame'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 17c1.5 0 2.5-1.5 2.5-3 0-1-.5-2-1-2.5C13 13 11 12 11 9.5c0-1.5 1-3 1-3s-2 .5-3.5 2.5C6 11.5 6 14 8.5 14.5Z"/><path d="M12 21a8 8 0 0 0 8-8c0-2.5-1-5-3-7-.5 3-2 4-3 4 .5-2-.5-4.5-2-6-.5 2.5-2 3.5-3 5-1 1.5-2 3-2 5 0 4 2.5 7 5 7Z"/></svg>',
                'trophy'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>',
                'sparkles' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/><path d="m5.5 5.5 2 2M16.5 16.5l2 2M5.5 18.5l2-2M16.5 7.5l2-2"/><circle cx="12" cy="12" r="3"/></svg>',
                'gift'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
                'layers'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
                'crown'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M2 7l3 9h14l3-9-5 4-5-7-5 7-5-4z"/><path d="M5 20h14"/></svg>',
                'users'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                'newspaper' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8M15 18h-5M10 6h8v4h-8V6Z"/></svg>',
                'coffee'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/></svg>',
                'megaphone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
                'heart'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
                'file'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
                'cog'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>',
                default    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="12" cy="12" r="9"/></svg>',
            };
        };

        $grouped = collect($templates)->groupBy('group');
    @endphp

    @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
    @endif

    {{-- KPI cards --}}
    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
        <x-admin.kpi-card :label="__('Активних підписників')" :value="$totals['active']" tone="emerald" />
        <x-admin.kpi-card :label="__('Відписалися')" :value="$totals['unsubscribed']" tone="rose" />
        <x-admin.kpi-card :label="__('За цей місяць')" :value="$totals['this_month']" />
        <x-admin.kpi-card :label="__('Авторів у системі')" :value="$totals['authors']" />
    </div>

    {{-- Composer with templates + live preview --}}
    <div
        x-data="newsletterComposer()"
        x-init="init()"
        class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]"
    >
        <!-- LEFT: templates + form -->
        <div class="space-y-4">
            {{-- Templates gallery --}}
            <x-admin.section :title="__('Готові шаблони')">
                <p class="mb-4 text-xs text-zinc-500">
                    {{ __('Натисни на шаблон — він підтягне свіжі дані з бази (топові моделі, нові надходження тощо) і заповнить форму. Тему та текст можна редагувати після цього.') }}
                </p>

                @foreach($templateGroups as $groupKey => $group)
                    @if(($items = $grouped->get($groupKey)) && $items->isNotEmpty())
                        <div class="mb-5 last:mb-0">
                            <div class="mb-2 flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-500">
                                <span class="text-emerald-300/70">{!! $templateIcon($group['icon']) !!}</span>
                                {{ $group['label'] }}
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($items as $tpl)
                                    <button
                                        type="button"
                                        @click="applyTemplate('{{ $tpl['key'] }}')"
                                        :class="currentTemplate === '{{ $tpl['key'] }}'
                                            ? 'border-emerald-300/50 bg-emerald-300/[0.10] ring-1 ring-emerald-300/30'
                                            : 'border-white/10 bg-white/[0.03] hover:border-white/20 hover:bg-white/[0.06]'"
                                        class="group relative flex items-start gap-3 rounded-xl border p-3 text-left transition"
                                    >
                                        <span
                                            class="grid h-9 w-9 shrink-0 place-items-center rounded-lg transition"
                                            :class="currentTemplate === '{{ $tpl['key'] }}' ? 'bg-emerald-300 text-zinc-950' : 'bg-zinc-900 text-emerald-300/80 group-hover:bg-emerald-300/[0.12]'"
                                        >
                                            {!! $templateIcon($tpl['icon']) !!}
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-bold text-white">{{ $tpl['label'] }}</span>
                                            <span class="mt-0.5 block text-[11px] leading-snug text-zinc-400">{{ $tpl['description'] }}</span>
                                            @if($tpl['is_dynamic'])
                                                <span class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-sky-300/10 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-sky-200">
                                                    <span class="h-1 w-1 animate-pulse rounded-full bg-sky-300"></span> live data
                                                </span>
                                            @endif
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </x-admin.section>

            {{-- Form --}}
            <x-admin.section :title="__('Лист')">
                <form
                    method="POST"
                    action="{{ route('admin.newsletter.blast') }}"
                    class="grid gap-3"
                    @submit="if (!confirmSend($event)) $event.preventDefault();"
                >
                    @csrf
                    <input type="hidden" name="template_key" x-model="currentTemplate">

                    <div>
                        <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Тема') }}</label>
                        <input
                            name="subject"
                            x-model="subject"
                            @input.debounce.500ms="refreshPreview()"
                            required maxlength="200"
                            class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:outline-none"
                            placeholder="{{ __('Топ-моделі тижня · 3Dify') }}"
                        >
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label class="block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Тіло листа') }}</label>
                            <button
                                type="button"
                                @click="bodyExpanded = !bodyExpanded"
                                class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500 hover:text-zinc-300"
                                x-text="bodyExpanded ? '{{ __('згорнути') }}' : '{{ __('розгорнути') }}'"
                            ></button>
                        </div>
                        <textarea
                            name="body"
                            x-model="body"
                            @input.debounce.700ms="refreshPreview()"
                            required maxlength="50000"
                            :rows="bodyExpanded ? 24 : 10"
                            class="w-full rounded-xl border border-white/10 bg-zinc-950/60 p-3 font-mono text-[12px] leading-relaxed text-zinc-200 focus:border-emerald-300 focus:outline-none"
                            placeholder="{{ __('Тут зʼявиться HTML тіло листа після обрання шаблону. Можеш редагувати або писати свій з нуля.') }}"
                        ></textarea>
                        <p class="mt-1 text-[11px] text-zinc-500">
                            {{ __('Підтримується HTML. Якщо HTML тегів немає — переноси рядків автоматично замінюються на <br>.') }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.14em] text-zinc-400">{{ __('Аудиторія') }}</label>
                            <select
                                name="audience"
                                x-model="audience"
                                class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white focus:border-emerald-300 focus:outline-none"
                            >
                                <option value="all_subscribers">{{ __('Усі активні') }} · {{ $audienceCounts['all_subscribers'] }}</option>
                                <option value="authors">{{ __('Автори (підписані)') }} · {{ $audienceCounts['authors'] }}</option>
                                <option value="buyers">{{ __('Покупці (підписані)') }} · {{ $audienceCounts['buyers'] }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col justify-end">
                            <p class="text-[11px] text-zinc-500" x-show="audience">
                                {{ __('Лист піде') }}
                                <span class="font-bold text-white" x-text="recipientLabel()"></span>
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-300/20 bg-amber-300/[0.04] p-3">
                        <label class="flex cursor-pointer items-start gap-2 text-xs text-amber-100">
                            <input type="checkbox" name="confirm" value="1" required class="mt-0.5 h-4 w-4 rounded border-white/20 bg-zinc-950/60 text-emerald-400 focus:ring-emerald-300/40">
                            <span>
                                {{ __('Я перевірив(ла) контент у preview справа і підтверджую розсилку.') }}
                                <span class="mt-0.5 block text-[10px] text-amber-200/70">{{ __('Це незворотна дія: листи стануть у чергу і будуть надіслані найближчим часом.') }}</span>
                            </span>
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button
                            type="submit"
                            class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-400 px-5 text-sm font-bold text-zinc-950 hover:bg-emerald-300"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m22 2-11 11"/><path d="M22 2 15 22l-4-9-9-4 20-7Z"/></svg>
                            {{ __('Поставити в чергу') }}
                        </button>
                        <button
                            type="button"
                            @click="reset()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-4 text-xs font-bold text-zinc-300 hover:bg-white/[0.08]"
                        >
                            {{ __('Очистити') }}
                        </button>
                        <button
                            type="button"
                            @click="copyToClipboard()"
                            class="inline-flex h-10 items-center gap-2 rounded-xl border border-white/10 bg-white/[0.04] px-4 text-xs font-bold text-zinc-300 hover:bg-white/[0.08]"
                            x-text="copyLabel"
                        >{{ __('Копіювати HTML') }}</button>
                    </div>
                </form>
            </x-admin.section>
        </div>

        <!-- RIGHT: live preview -->
        <div class="xl:sticky xl:top-4 xl:self-start">
            <x-admin.section :title="__('Живий preview')">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 rounded-full border border-white/10 bg-zinc-950/40 p-1 text-xs">
                        <button
                            type="button"
                            @click="device = 'desktop'"
                            :class="device === 'desktop' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-300 hover:bg-white/[0.06]'"
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 font-semibold transition"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-3.5 w-3.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            {{ __('Desktop') }}
                        </button>
                        <button
                            type="button"
                            @click="device = 'mobile'"
                            :class="device === 'mobile' ? 'bg-emerald-400 text-zinc-950' : 'text-zinc-300 hover:bg-white/[0.06]'"
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 font-semibold transition"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-3.5 w-3.5"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                            {{ __('Mobile') }}
                        </button>
                    </div>
                    <div class="flex items-center gap-2 text-[11px]">
                        <span x-show="loading" class="inline-flex items-center gap-1.5 text-amber-200">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-300"></span>
                            {{ __('оновлення…') }}
                        </span>
                        <span x-show="!loading && lastUpdated" class="text-zinc-500" x-text="lastUpdated"></span>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-white/10 bg-zinc-950">
                    <!-- Email header strip -->
                    <div class="flex items-center gap-2 border-b border-white/5 bg-zinc-950/80 px-3 py-2 text-[11px] text-zinc-500">
                        <div class="flex items-center gap-1">
                            <span class="h-2 w-2 rounded-full bg-rose-400/60"></span>
                            <span class="h-2 w-2 rounded-full bg-amber-400/60"></span>
                            <span class="h-2 w-2 rounded-full bg-emerald-400/60"></span>
                        </div>
                        <span class="ml-2 truncate font-mono">📧 <span x-text="subject || '{{ __('Тема листа') }}'"></span></span>
                    </div>

                    <div
                        class="bg-[#0a0f0d] transition-all duration-300"
                        :class="device === 'mobile' ? 'mx-auto max-w-[380px] py-4' : ''"
                    >
                        <iframe
                            x-ref="preview"
                            sandbox="allow-same-origin"
                            class="block w-full border-0 transition-all"
                            :style="`height: ${device === 'mobile' ? 720 : 800}px;`"
                            srcdoc=""
                        ></iframe>
                    </div>
                </div>

                <p class="mt-3 text-[11px] leading-relaxed text-zinc-500">
                    {{ __('Це фактичний рендер листа — саме так його побачить підписник. Footer із кнопкою «Відписатися» додається автоматично.') }}
                </p>
            </x-admin.section>

            {{-- Recent blasts --}}
            <x-admin.section :title="__('Останні розсилки')" class="mt-4">
                @if($blasts->isEmpty())
                    <p class="py-8 text-center text-xs text-zinc-500">{{ __('Поки розсилок не було. Перша — попереду :)') }}</p>
                @else
                    <ul class="space-y-2">
                        @foreach($blasts as $b)
                            <li class="rounded-xl border border-white/10 bg-white/[0.04] p-3 text-sm">
                                <p class="truncate font-bold text-white">{{ $b->subject }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-zinc-500">
                                    <span class="rounded-md bg-white/[0.06] px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-wider">{{ $b->audience }}</span>
                                    <span>{{ $b->recipients_count }} {{ __('одержувачів') }}</span>
                                    <span class="text-zinc-600">·</span>
                                    <span>{{ $b->createdBy?->name ?? '—' }}</span>
                                    <span class="text-zinc-600">·</span>
                                    <span>{{ optional($b->sent_at)->translatedFormat('d M H:i') }}</span>
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.section>
        </div>
    </div>

    {{-- Subscribers table --}}
    <x-admin.section :title="__('Підписники')" class="mt-6">
        <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('Email або імʼя…') }}" class="h-9 w-64 rounded-full border border-white/10 bg-white/[0.04] px-3 text-sm text-white placeholder:text-zinc-500">
            <select name="status" onchange="this.form.submit()" class="h-9 rounded-full border border-white/10 bg-white/[0.04] px-3 text-xs text-white">
                <option value="active" @selected($status === 'active')>{{ __('Активні') }}</option>
                <option value="unsubscribed" @selected($status === 'unsubscribed')>{{ __('Відписалися') }}</option>
                <option value="all" @selected($status === 'all')>{{ __('Усі') }}</option>
            </select>
            <button class="inline-flex h-9 items-center rounded-full bg-emerald-400 px-4 text-xs font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Знайти') }}</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-950/40 text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                        <th class="px-4 py-3">{{ __('Імʼя') }}</th>
                        <th class="px-4 py-3">{{ __('Локаль') }}</th>
                        <th class="px-4 py-3">{{ __('Джерело') }}</th>
                        <th class="px-4 py-3">{{ __('Підписано') }}</th>
                        <th class="px-4 py-3">{{ __('Статус') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($subs as $s)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-4 py-2.5 font-mono text-xs text-zinc-200">{{ $s->email }}</td>
                            <td class="px-4 py-2.5 text-zinc-300">{{ $s->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs uppercase text-zinc-400">{{ $s->locale ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">{{ $s->source }}</td>
                            <td class="px-4 py-2.5 text-xs text-zinc-400">{{ $s->created_at->format('d.m.Y') }}</td>
                            <td class="px-4 py-2.5">
                                @if($s->unsubscribed_at)
                                    <span class="inline-flex items-center rounded-full border border-rose-300/30 bg-rose-300/[0.08] px-2 py-0.5 text-[10px] font-bold text-rose-200">{{ __('відписаний') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full border border-emerald-300/30 bg-emerald-300/[0.08] px-2 py-0.5 text-[10px] font-bold text-emerald-200">{{ __('активний') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <form method="POST" action="{{ route('admin.newsletter.destroy', $s) }}" onsubmit="return confirm('{{ __('Видалити підписника?') }}')">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex h-7 items-center rounded-md border border-rose-300/30 bg-rose-300/[0.06] px-2.5 text-[11px] font-bold text-rose-200 hover:bg-rose-300/[0.12]">{{ __('видалити') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-zinc-500">{{ __('Підписників немає.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $subs->links() }}</div>
    </x-admin.section>

    @push('scripts')
        <script>
            window.newsletterComposer = function () {
                return {
                    currentTemplate: null,
                    subject: '',
                    body: '',
                    audience: 'all_subscribers',
                    bodyExpanded: false,
                    device: 'desktop',
                    loading: false,
                    lastUpdated: '',
                    copyLabel: '{{ __('Копіювати HTML') }}',
                    audienceCounts: @json($audienceCounts),

                    init() {
                        this.refreshPreview();
                    },

                    async applyTemplate(key) {
                        this.currentTemplate = key;
                        this.loading = true;
                        try {
                            const res = await fetch(`{{ url('admin/newsletter/template') }}/${key}`, {
                                headers: { Accept: 'application/json' },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) throw new Error(res.statusText);
                            const data = await res.json();
                            this.subject = data.subject;
                            this.body = data.body;
                            if (data.audience) this.audience = data.audience;
                            await this.refreshPreview();
                        } catch (e) {
                            console.error(e);
                            this.loading = false;
                            alert('{{ __('Не вдалося завантажити шаблон.') }}');
                        }
                    },

                    async refreshPreview() {
                        if (!this.subject && !this.body) {
                            this.$refs.preview.srcdoc = `<html><body style="margin:0;background:#0a0f0d;color:#71717a;font-family:system-ui;display:flex;align-items:center;justify-content:center;height:100%;font-size:14px;text-align:center;padding:24px;">{{ __('Обери шаблон зліва — preview зʼявиться тут.') }}</body></html>`;
                            return;
                        }
                        this.loading = true;
                        try {
                            const res = await fetch(`{{ route('admin.newsletter.preview') }}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
                                        ?? document.querySelector('input[name=_token]')?.value,
                                    Accept: 'text/html',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    subject: this.subject || ' ',
                                    body: this.body || ' ',
                                }),
                            });
                            if (!res.ok) throw new Error(res.statusText);
                            const html = await res.text();
                            this.$refs.preview.srcdoc = html;
                            this.lastUpdated = '{{ __('оновлено') }} ' + new Date().toLocaleTimeString();
                        } catch (e) {
                            console.error(e);
                        } finally {
                            this.loading = false;
                        }
                    },

                    reset() {
                        if (this.body && !confirm('{{ __('Очистити форму?') }}')) return;
                        this.currentTemplate = null;
                        this.subject = '';
                        this.body = '';
                        this.refreshPreview();
                    },

                    recipientLabel() {
                        const count = this.audienceCounts[this.audience] ?? 0;
                        if (count === 0) return '{{ __('нікому — нема підписників') }}';
                        return `~${count} {{ __('одержувачам') }}`;
                    },

                    confirmSend(e) {
                        const count = this.audienceCounts[this.audience] ?? 0;
                        if (count === 0) {
                            alert('{{ __('У цій аудиторії немає підписників — нічого надсилати.') }}');
                            return false;
                        }
                        return confirm(`{{ __('Надіслати лист') }} ~${count} {{ __('одержувачам?') }}`);
                    },

                    async copyToClipboard() {
                        if (!this.body) return;
                        try {
                            await navigator.clipboard.writeText(this.body);
                            this.copyLabel = '{{ __('Скопійовано ✓') }}';
                            setTimeout(() => { this.copyLabel = '{{ __('Копіювати HTML') }}'; }, 1800);
                        } catch (e) {
                            console.error(e);
                        }
                    },
                };
            };
        </script>
    @endpush
</x-layouts.admin>
