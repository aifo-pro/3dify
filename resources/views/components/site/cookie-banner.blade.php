{{--
    Cookie consent banner with Google Consent Mode v2.

    Persists choice in localStorage under "cookie-consent" as
    { necessary: true, analytics: bool, marketing: bool, ts: number, v: 1 }.

    The banner shows on first visit (no stored value) and can be reopened any
    time by calling window.openCookieSettings() — wired up from the footer.
--}}
@once
    <script>
        // Cookie banner Alpine component (registered globally so we can reopen
        // the banner from the footer link via window.openCookieSettings()).
        (function () {
            const COOKIE_CONSENT_KEY = 'cookie-consent';
            const COOKIE_CONSENT_VERSION = 1;

            window.openCookieSettings = function () {
                window.dispatchEvent(new CustomEvent('open-cookie-settings'));
            };

            window.cookieBanner = function () {
                return {
                    visible: false,
                    showDetails: false,
                    prefs: { analytics: false, marketing: false },

                    init() {
                        const saved = this.read();
                        if (!saved) {
                            this.visible = true;
                            return;
                        }
                        this.prefs.analytics = !!saved.analytics;
                        this.prefs.marketing = !!saved.marketing;
                    },

                    open() {
                        const saved = this.read();
                        if (saved) {
                            this.prefs.analytics = !!saved.analytics;
                            this.prefs.marketing = !!saved.marketing;
                        }
                        this.showDetails = true;
                        this.visible = true;
                    },

                    read() {
                        try {
                            const raw = localStorage.getItem(COOKIE_CONSENT_KEY);
                            if (!raw) return null;
                            const data = JSON.parse(raw);
                            if (!data || data.v !== COOKIE_CONSENT_VERSION) return null;
                            return data;
                        } catch (_) { return null; }
                    },

                    persist(prefs) {
                        const payload = {
                            necessary: true,
                            analytics: !!prefs.analytics,
                            marketing: !!prefs.marketing,
                            ts: Date.now(),
                            v: COOKIE_CONSENT_VERSION,
                        };
                        try { localStorage.setItem(COOKIE_CONSENT_KEY, JSON.stringify(payload)); } catch (_) {}
                        this.applyConsent(payload);
                        return payload;
                    },

                    applyConsent(prefs) {
                        // Notify Google tags through Consent Mode v2.
                        if (typeof window.gtag === 'function') {
                            window.gtag('consent', 'update', {
                                analytics_storage: prefs.analytics ? 'granted' : 'denied',
                                ad_storage: prefs.marketing ? 'granted' : 'denied',
                                ad_user_data: prefs.marketing ? 'granted' : 'denied',
                                ad_personalization: prefs.marketing ? 'granted' : 'denied',
                            });
                        }
                        window.dispatchEvent(new CustomEvent('cookie-consent-updated', { detail: prefs }));
                    },

                    acceptAll() {
                        this.prefs.analytics = true;
                        this.prefs.marketing = true;
                        this.persist(this.prefs);
                        this.visible = false;
                        this.showDetails = false;
                    },

                    rejectAll() {
                        this.prefs.analytics = false;
                        this.prefs.marketing = false;
                        this.persist(this.prefs);
                        this.visible = false;
                        this.showDetails = false;
                    },

                    savePrefs() {
                        this.persist(this.prefs);
                        this.visible = false;
                        this.showDetails = false;
                    },
                };
            };
        })();
    </script>
@endonce

<div
    x-data="cookieBanner()"
    x-init="init()"
    @open-cookie-settings.window="open()"
    x-cloak
>
    {{-- Floating banner (shown until user picks something) --}}
    <div
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-8"
        class="fixed inset-x-0 bottom-0 z-[60] px-4 pb-4 sm:px-6 sm:pb-6"
        role="dialog"
        aria-live="polite"
        aria-label="{{ __('Налаштування cookies') }}"
    >
        <div class="mx-auto w-full max-w-4xl overflow-hidden rounded-3xl border border-emerald-300/20 bg-zinc-950/95 shadow-2xl shadow-emerald-500/15 backdrop-blur supports-[backdrop-filter]:bg-zinc-950/80">
            {{-- Compact view --}}
            <div x-show="!showDetails" class="grid gap-4 p-5 sm:p-6 lg:grid-cols-[1fr_auto] lg:items-center lg:gap-6">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-400/15 text-emerald-300">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/><path d="M8.5 8.5h.01"/><path d="M15 9.5h.01"/><path d="M9.5 14h.01"/><path d="M14.5 14.5h.01"/></svg>
                        </span>
                        <h2 class="text-base font-bold text-white">{{ __('Ми використовуємо cookies') }}</h2>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-zinc-300">
                        {{ __('Необхідні cookies потрібні для роботи сайту (вхід, кошик, безпека). Аналітичні та маркетингові — лише з вашого дозволу.') }}
                        <a href="{{ route('pages.show', 'privacy') }}" class="text-emerald-300 underline-offset-4 hover:underline">{{ __('Політика приватності') }}</a>.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap lg:justify-end">
                    <button type="button" @click="rejectAll()"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-200 transition hover:bg-white/10">
                        {{ __('Лише необхідні') }}
                    </button>
                    <button type="button" @click="showDetails = true"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-white/10 bg-transparent px-4 text-xs font-semibold text-zinc-300 transition hover:bg-white/[0.04]">
                        {{ __('Налаштувати') }}
                    </button>
                    <button type="button" @click="acceptAll()"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-400 px-5 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                        {{ __('Прийняти всі') }}
                    </button>
                </div>
            </div>

            {{-- Detailed view with toggles --}}
            <div x-show="showDetails" x-cloak class="grid gap-5 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-white">{{ __('Налаштування cookies') }}</h2>
                    <button type="button" @click="showDetails = false" class="grid h-8 w-8 place-items-center rounded-lg text-zinc-400 hover:bg-white/[0.06] hover:text-white" aria-label="{{ __('Закрити') }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                {{-- Necessary --}}
                <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-white">{{ __('Необхідні') }}</p>
                                <span class="rounded-full border border-emerald-300/30 bg-emerald-300/[0.08] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-200">{{ __('Завжди') }}</span>
                            </div>
                            <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('Сесія, безпека (CSRF), збереження мови та налаштувань акаунта. Без них сайт не працюватиме.') }}</p>
                        </div>
                        <span class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-emerald-400/40">
                            <span class="absolute right-0.5 inline-block h-5 w-5 transform rounded-full bg-emerald-300 shadow"></span>
                        </span>
                    </div>
                </div>

                {{-- Analytics --}}
                <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ __('Аналітичні') }}</p>
                            <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('Google Analytics та анонімізована статистика, щоб ми бачили популярні моделі та поліпшували сайт.') }}</p>
                        </div>
                        <button type="button" role="switch" :aria-checked="prefs.analytics" @click="prefs.analytics = !prefs.analytics"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition"
                            :class="prefs.analytics ? 'bg-emerald-400' : 'bg-white/15'">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                                :class="prefs.analytics ? 'translate-x-5' : 'translate-x-0.5'"></span>
                        </button>
                    </div>
                </div>

                {{-- Marketing --}}
                <div class="rounded-2xl border border-white/10 bg-white/[0.02] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-white">{{ __('Маркетингові') }}</p>
                            <p class="mt-1 text-xs leading-5 text-zinc-400">{{ __('Персоналізовані рекомендації моделей, ремаркетинг у Google Ads, метрики ефективності рекламних кампаній.') }}</p>
                        </div>
                        <button type="button" role="switch" :aria-checked="prefs.marketing" @click="prefs.marketing = !prefs.marketing"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition"
                            :class="prefs.marketing ? 'bg-emerald-400' : 'bg-white/15'">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                                :class="prefs.marketing ? 'translate-x-5' : 'translate-x-0.5'"></span>
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                    <a href="{{ route('pages.show', 'privacy') }}" class="text-xs text-emerald-300 underline-offset-4 hover:underline">{{ __('Дізнатися більше') }}</a>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="rejectAll()"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] px-4 text-xs font-semibold text-zinc-200 transition hover:bg-white/10">
                            {{ __('Лише необхідні') }}
                        </button>
                        <button type="button" @click="savePrefs()"
                            class="inline-flex h-10 items-center justify-center rounded-xl border border-emerald-300/30 bg-emerald-300/[0.10] px-4 text-xs font-bold text-emerald-100 transition hover:bg-emerald-300/15">
                            {{ __('Зберегти вибір') }}
                        </button>
                        <button type="button" @click="acceptAll()"
                            class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-400 px-5 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                            {{ __('Прийняти всі') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
