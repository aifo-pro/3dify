{{--
    Single global instance — include once on a page.
    Open via:
        <button @click="$dispatch('open-download-modal', { url: '{{ route('products.download-options', $product) }}', title: $product->localized('title') })">
            Скачати / друкувати
        </button>

    The component lazily fetches a JSON payload from the given URL when opened
    so signed slicer URLs are always fresh (5 min TTL). All access checks
    happen server-side; this view is only UI glue.
--}}

<div
    x-data="downloadModal()"
    x-cloak
    x-show="open"
    @keydown.escape.window="close()"
    @open-download-modal.window="openWith($event.detail)"
    class="fixed inset-0 z-[9999] grid place-items-center px-4 py-6"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        @click="close()"
        class="absolute inset-0 bg-black/70 backdrop-blur-sm"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-[0.98]"
        class="relative z-10 flex w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-white/10 bg-zinc-950/95 shadow-2xl shadow-black/60 backdrop-blur-xl max-h-[min(94vh,720px)]"
    >
        {{-- Header --}}
        <div class="relative shrink-0 border-b border-white/10 px-6 pt-6 pb-5 sm:px-8">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(52,211,153,.10),transparent_50%),radial-gradient(circle_at_top_right,rgba(56,189,248,.08),transparent_50%)]"></div>
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-200">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        {{ __('3D friendly') }}
                    </div>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-white">{{ __('Підготовка до друку') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-zinc-400" x-text="title || '{{ __('Скачайте файл або відкрийте його у slicer') }}'"></p>
                </div>
                <button
                    type="button"
                    @click="close()"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:bg-white/10"
                    :aria-label="'{{ __('Закрити') }}'"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="mt-5 flex gap-1.5 rounded-2xl border border-white/10 bg-white/[0.04] p-1">
                <button
                    type="button"
                    @click="tab = 'download'"
                    :class="tab === 'download' ? 'bg-emerald-400 text-zinc-950 shadow-lg shadow-emerald-500/25' : 'text-zinc-300 hover:bg-white/[0.06] hover:text-white'"
                    class="flex h-9 flex-1 items-center justify-center gap-2 rounded-xl text-sm font-bold transition"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    {{ __('Скачати файли') }}
                </button>
                <button
                    type="button"
                    @click="tab = 'slicer'"
                    :class="tab === 'slicer' ? 'bg-emerald-400 text-zinc-950 shadow-lg shadow-emerald-500/25' : 'text-zinc-300 hover:bg-white/[0.06] hover:text-white'"
                    class="flex h-9 flex-1 items-center justify-center gap-2 rounded-xl text-sm font-bold transition"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    {{ __('Відкрити у slicer') }}
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-6 py-6 sm:px-8">

            {{-- Loading skeleton --}}
            <div x-show="loading" class="grid gap-3">
                <template x-for="i in 3">
                    <div class="h-16 animate-pulse rounded-2xl border border-white/5 bg-white/[0.03]"></div>
                </template>
            </div>

            {{-- Error state --}}
            <div x-show="!loading && error" class="rounded-2xl border border-rose-300/30 bg-rose-300/[0.08] p-5 text-sm text-rose-100">
                <p class="font-semibold">{{ __('Не вдалося отримати файли') }}</p>
                <p class="mt-1 text-xs text-rose-200/80" x-text="error"></p>
            </div>

            {{-- TAB: download --}}
            <div x-show="!loading && !error && tab === 'download'" class="grid gap-3">
                <template x-if="files.length === 0">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-6 text-center text-sm text-zinc-400">
                        {{ __('Доступних файлів поки немає.') }}
                    </div>
                </template>
                <template x-for="file in files" :key="file.id">
                    <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-4 transition hover:border-white/20">
                        <span
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-zinc-950 font-mono text-[11px] font-black tracking-wider"
                            :class="extColor(file.extension)"
                            x-text="file.extension_label"
                        ></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-white" x-text="file.name"></p>
                            <p class="text-xs text-zinc-500">
                                <span x-text="file.size"></span>
                                <span class="mx-1.5 text-zinc-700">·</span>
                                <span x-text="file.extension_label"></span>
                            </p>
                        </div>
                        <a
                            :href="file.download_url"
                            class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            {{ __('Скачати') }}
                        </a>
                    </div>
                </template>
            </div>

            {{-- TAB: slicer --}}
            <div x-show="!loading && !error && tab === 'slicer'" class="grid gap-3">
                {{-- File picker (only if multiple slicer-compatible files) --}}
                <template x-if="slicerFiles.length > 1">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ __('Файл для slicer') }}</p>
                        <div class="grid gap-1.5">
                            <template x-for="file in slicerFiles" :key="file.id">
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/5 bg-zinc-950/40 px-3 py-2 text-sm text-zinc-200 transition hover:border-white/15 hover:bg-zinc-950/70">
                                    <input type="radio" :value="file.id" x-model.number="selectedFileId" class="h-4 w-4 border-white/20 bg-zinc-950 text-emerald-400 focus:ring-emerald-300">
                                    <span class="grid h-7 w-9 shrink-0 place-items-center rounded-md bg-zinc-900 font-mono text-[10px] font-black" :class="extColor(file.extension)" x-text="file.extension_label"></span>
                                    <span class="min-w-0 flex-1 truncate" x-text="file.name"></span>
                                    <span class="shrink-0 text-xs text-zinc-500" x-text="file.size"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="slicerFiles.length === 0">
                    <div class="rounded-2xl border border-amber-300/30 bg-amber-300/[0.08] p-5 text-sm text-amber-100">
                        <p class="font-semibold">{{ __('Цю модель не можна відкрити у slicer напряму') }}</p>
                        <p class="mt-1 text-xs leading-5 text-amber-200/80">{{ __('Slicer-програми очікують файли формату STL, OBJ або 3MF. Скачайте архів вручну та розпакуйте його.') }}</p>
                    </div>
                </template>

                {{-- Slicer cards --}}
                <template x-for="slicer in slicers" :key="slicer.id">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4 transition hover:border-white/20">
                        <div class="flex items-start gap-4">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border" :class="slicer.iconClass" x-html="slicer.icon"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-bold text-white" x-text="slicer.name"></h3>
                                    <span x-show="slicer.warning" class="inline-flex items-center gap-1 rounded-full border border-amber-300/30 bg-amber-300/[0.10] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-200">
                                        <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        beta
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs leading-5 text-zinc-500" x-text="slicer.description"></p>
                                <template x-if="slicer.id === 'bambu'">
                                    <p class="mt-1.5 text-[11px] leading-5 text-amber-200/80">{{ __('Bambu Studio може не підтримувати відкриття файлів із браузера. Якщо нічого не сталося — скачайте файл і відкрийте вручну.') }}</p>
                                </template>
                            </div>
                            <button
                                type="button"
                                @click="openInSlicer(slicer)"
                                :disabled="slicerFiles.length === 0"
                                class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-xl border border-white/15 bg-white/[0.06] px-3.5 text-xs font-bold text-white transition hover:border-emerald-300/40 hover:bg-emerald-300/10 hover:text-emerald-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                {{ __('Відкрити') }}
                            </button>
                        </div>
                    </div>
                </template>

                {{-- Fallback toast --}}
                <div
                    x-show="lastTried"
                    x-transition.opacity
                    class="rounded-2xl border border-sky-300/30 bg-sky-300/[0.08] p-4 text-sm text-sky-100"
                >
                    <p class="font-semibold">
                        <span x-text="'{{ __('Спроба відкрити') }} ' + lastTried + '.'"></span>
                    </p>
                    <p class="mt-1 text-xs leading-5 text-sky-200/80">{{ __('Якщо програма не запустилася — її, ймовірно, не встановлено або custom-protocol не зареєстровано в системі. Скачайте файл і відкрийте його у slicer вручну.') }}</p>
                </div>

                <p class="mt-1 text-center text-[11px] leading-5 text-zinc-500">
                    {{ __('Посилання на slicer діє 5 хв і недоступне публічно. 3Dify не передає дані стороннім сервісам.') }}
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="shrink-0 border-t border-white/10 bg-zinc-950/60 px-6 py-3 sm:px-8">
            <div class="flex items-center justify-between gap-3 text-[11px] text-zinc-500">
                <span class="inline-flex items-center gap-1.5">
                    <svg class="h-3 w-3 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    {{ __('Захищене завантаження') }}
                </span>
                <button type="button" @click="close()" class="text-zinc-400 transition hover:text-white">
                    {{ __('Закрити') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof window.downloadModal === 'undefined') {
        window.downloadModal = function () {
            return {
                open: false,
                tab: 'download',
                loading: false,
                error: null,
                title: '',
                files: [],
                slicerLogUrl: null,
                selectedFileId: null,
                lastTried: null,
                slicers: [
                    {
                        id: 'orca',
                        name: 'OrcaSlicer',
                        protocol: 'orcaslicer',
                        description: 'Open-source slicer with great profile management for many printers.',
                        warning: false,
                        iconClass: 'border-orange-400/30 bg-orange-400/10 text-orange-200',
                        icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 13c1.5-2 4-3 8-3"/><circle cx="9" cy="10" r="0.6" fill="currentColor"/></svg>',
                    },
                    {
                        id: 'cura',
                        name: 'Ultimaker Cura',
                        protocol: 'cura',
                        description: 'Industry-standard slicer compatible with virtually all FDM printers.',
                        warning: false,
                        iconClass: 'border-sky-400/30 bg-sky-400/10 text-sky-200',
                        icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 22 7 22 17 12 22 2 17 2 7 12 2"/><polyline points="2 7 12 12 22 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>',
                    },
                    {
                        id: 'prusa',
                        name: 'PrusaSlicer',
                        protocol: 'prusaslicer',
                        description: 'Powerful slicer optimized for Prusa printers and modifications.',
                        warning: false,
                        iconClass: 'border-amber-400/30 bg-amber-400/10 text-amber-200',
                        icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18"/><path d="M5 7v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7"/><path d="M9 11h6"/><path d="M9 15h6"/></svg>',
                    },
                    {
                        id: 'bambu',
                        name: 'Bambu Studio',
                        protocol: 'bambustudio',
                        description: 'Native slicer for Bambu Lab printers with cloud printing features.',
                        warning: true,
                        iconClass: 'border-emerald-400/30 bg-emerald-400/10 text-emerald-200',
                        icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12c4-1 8-1 12 0s5 1 6 0"/><path d="M3 8c4-1 8-1 12 0"/><path d="M3 16c4-1 8-1 12 0"/></svg>',
                    },
                ],
                get slicerFiles() {
                    return this.files.filter(f => f.is_slicer_compatible);
                },
                extColor(ext) {
                    const map = {
                        stl: 'text-emerald-200',
                        obj: 'text-amber-200',
                        glb: 'text-sky-200',
                        gltf: 'text-sky-200',
                        '3mf': 'text-violet-200',
                        zip: 'text-zinc-300',
                    };
                    return map[ext] || 'text-zinc-300';
                },
                async openWith(detail) {
                    this.tab = 'download';
                    this.error = null;
                    this.lastTried = null;
                    this.title = detail.title || '';
                    this.open = true;
                    this.loading = true;
                    document.body.style.overflow = 'hidden';
                    try {
                        const res = await fetch(detail.url, {
                            credentials: 'same-origin',
                            headers: {'Accept': 'application/json'},
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        this.files = data.files || [];
                        this.slicerLogUrl = data.slicer_log_url || null;
                        this.selectedFileId = this.slicerFiles[0]?.id ?? null;
                    } catch (e) {
                        this.error = e.message || 'Network error';
                    } finally {
                        this.loading = false;
                    }
                },
                close() {
                    this.open = false;
                    this.lastTried = null;
                    document.body.style.overflow = '';
                },
                openInSlicer(slicer) {
                    const file = this.slicerFiles.find(f => f.id === this.selectedFileId)
                        || this.slicerFiles[0];
                    if (!file) return;

                    const protoUrl = `${slicer.protocol}://open?file=${encodeURIComponent(file.signed_url)}`;
                    this.lastTried = slicer.name;
                    this.logSlicerOpen(slicer, file);

                    // Try the custom protocol via a transient anchor — most reliable
                    // way to dispatch the URL without changing window.location.
                    const a = document.createElement('a');
                    a.href = protoUrl;
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    setTimeout(() => a.remove(), 100);
                },
                logSlicerOpen(slicer, file) {
                    if (!this.slicerLogUrl) return;

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const params = new URLSearchParams({
                        _token: csrf,
                        slicer: slicer.name,
                        file_id: file.id,
                    });

                    if (navigator.sendBeacon) {
                        const blob = new Blob([params.toString()], {type: 'application/x-www-form-urlencoded;charset=UTF-8'});
                        navigator.sendBeacon(this.slicerLogUrl, blob);
                        return;
                    }

                    fetch(this.slicerLogUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        keepalive: true,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: params,
                    }).catch(() => {});
                },
            };
        };
    }

    /*
     * Bridge any `data-download-trigger` button to the modal — works without
     * an Alpine ancestor (Alpine ignores @click outside x-data scopes, so we
     * use plain delegation on document).
     */
    if (!window.__downloadTriggerBound) {
        window.__downloadTriggerBound = true;
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-download-trigger]');
            if (!btn) return;
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('open-download-modal', {
                detail: {
                    url: btn.dataset.downloadUrl,
                    title: btn.dataset.downloadTitle || '',
                },
            }));
        });
    }
</script>
