@props(['url' => '', 'title' => '', 'description' => '', 'image' => '', 'embedRoute' => ''])

@php
    $shareUrl = $url ?: request()->fullUrl();
    $shareTitle = $title;
    $shareImage = $image;
    $embedCode = $embedRoute
        ? '<iframe src="' . e($embedRoute) . '" width="800" height="600" frameborder="0" allowfullscreen></iframe>'
        : '';
@endphp

<div
    x-data="{
        open: false,
        copied: false,
        embedModal: false,
        embedCopied: false,
        url: @js($shareUrl),
        title: @js($shareTitle),
        image: @js($shareImage),
        embedCode: @js($embedCode),

        toggle() { this.open = !this.open; },
        sharePopup(href) {
            window.open(href, '_blank', 'width=640,height=600,scrollbars=yes,resizable=yes');
            this.open = false;
        },
        shareFacebook() { this.sharePopup('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(this.url)); },
        shareTwitter() { this.sharePopup('https://twitter.com/intent/tweet?url=' + encodeURIComponent(this.url) + '&text=' + encodeURIComponent(this.title)); },
        shareReddit() { this.sharePopup('https://www.reddit.com/submit?url=' + encodeURIComponent(this.url) + '&title=' + encodeURIComponent(this.title)); },
        sharePinterest() { this.sharePopup('https://pinterest.com/pin/create/button/?url=' + encodeURIComponent(this.url) + '&media=' + encodeURIComponent(this.image) + '&description=' + encodeURIComponent(this.title)); },
        shareTelegram() { this.sharePopup('https://t.me/share/url?url=' + encodeURIComponent(this.url) + '&text=' + encodeURIComponent(this.title)); },
        shareLinkedIn() { this.sharePopup('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(this.url)); },
        async copyLink() {
            try { await navigator.clipboard.writeText(this.url); }
            catch { const i = document.createElement('input'); i.value = this.url; document.body.appendChild(i); i.select(); document.execCommand('copy'); document.body.removeChild(i); }
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
            this.open = false;
        },
        openEmbed() { this.open = false; this.embedModal = true; document.body.classList.add('overflow-hidden'); },
        closeEmbed() { this.embedModal = false; document.body.classList.remove('overflow-hidden'); },
        async copyEmbed() {
            try { await navigator.clipboard.writeText(this.embedCode); }
            catch { const i = document.createElement('input'); i.value = this.embedCode; document.body.appendChild(i); i.select(); document.execCommand('copy'); document.body.removeChild(i); }
            this.embedCopied = true;
            setTimeout(() => this.embedCopied = false, 2500);
        },
    }"
    @keydown.escape.window="open ? (open = false) : (embedModal ? closeEmbed() : null)"
>
    {{-- Trigger button --}}
    <button
        type="button"
        @click="toggle()"
        class="grid h-11 w-11 place-items-center rounded-2xl border border-white/10 bg-white/[0.04] text-zinc-300 transition hover:border-emerald-300/40 hover:bg-emerald-300/10 hover:text-emerald-200"
        aria-label="{{ __('Поширити') }}"
    >
        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
    </button>

    {{-- Share overlay (fixed, always on top) --}}
    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        class="fixed inset-0 z-[9998] bg-black/60"
        @click="open = false"
    ></div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed inset-x-0 bottom-0 z-[9999] mx-auto w-full max-w-sm p-4 sm:bottom-auto sm:top-1/2 sm:-translate-y-1/2 sm:p-0"
    >
        <div class="overflow-hidden rounded-2xl border border-white/10 bg-[#0b1117] shadow-2xl shadow-black/60">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                <h3 class="text-sm font-bold text-white">{{ __('Поширити') }}</h3>
                <button type="button" @click="open = false" class="grid h-8 w-8 place-items-center rounded-lg text-zinc-400 transition hover:bg-white/10 hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            {{-- Social grid --}}
            <div class="grid grid-cols-4 gap-1 px-4 py-4">
                <button type="button" @click="shareFacebook()" class="flex flex-col items-center gap-2 rounded-xl px-2 py-3 transition hover:bg-white/5">
                    <span class="grid h-10 w-10 place-items-center rounded-full" style="background:#1877F2">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </span>
                    <span class="text-[11px] text-zinc-400">Facebook</span>
                </button>
                <button type="button" @click="shareTwitter()" class="flex flex-col items-center gap-2 rounded-xl px-2 py-3 transition hover:bg-white/5">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-zinc-800">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </span>
                    <span class="text-[11px] text-zinc-400">X</span>
                </button>
                <button type="button" @click="shareTelegram()" class="flex flex-col items-center gap-2 rounded-xl px-2 py-3 transition hover:bg-white/5">
                    <span class="grid h-10 w-10 place-items-center rounded-full" style="background:#26A5E4">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0h-.056zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    </span>
                    <span class="text-[11px] text-zinc-400">Telegram</span>
                </button>
                <button type="button" @click="shareReddit()" class="flex flex-col items-center gap-2 rounded-xl px-2 py-3 transition hover:bg-white/5">
                    <span class="grid h-10 w-10 place-items-center rounded-full" style="background:#FF4500">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/></svg>
                    </span>
                    <span class="text-[11px] text-zinc-400">Reddit</span>
                </button>
                <button type="button" @click="sharePinterest()" class="flex flex-col items-center gap-2 rounded-xl px-2 py-3 transition hover:bg-white/5">
                    <span class="grid h-10 w-10 place-items-center rounded-full" style="background:#E60023">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.174.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>
                    </span>
                    <span class="text-[11px] text-zinc-400">Pinterest</span>
                </button>
                <button type="button" @click="shareLinkedIn()" class="flex flex-col items-center gap-2 rounded-xl px-2 py-3 transition hover:bg-white/5">
                    <span class="grid h-10 w-10 place-items-center rounded-full" style="background:#0A66C2">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </span>
                    <span class="text-[11px] text-zinc-400">LinkedIn</span>
                </button>
                <button type="button" @click="copyLink()" class="flex flex-col items-center gap-2 rounded-xl px-2 py-3 transition hover:bg-white/5">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-zinc-800">
                        <svg class="h-5 w-5 text-zinc-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    </span>
                    <span class="text-[11px] text-zinc-400" x-text="copied ? '{{ __("Скопійовано!") }}' : '{{ __("Посилання") }}'"></span>
                </button>
                @if($embedRoute)
                    <button type="button" @click="openEmbed()" class="flex flex-col items-center gap-2 rounded-xl px-2 py-3 transition hover:bg-white/5">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-zinc-800">
                            <svg class="h-5 w-5 text-zinc-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                        </span>
                        <span class="text-[11px] text-zinc-400">Embed</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Embed modal --}}
    @if($embedRoute)
        <div
            x-show="embedModal"
            x-cloak
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
            @click.self="closeEmbed()"
        >
            <div class="w-full max-w-lg rounded-3xl border border-white/10 bg-[#0b1117] p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-black text-white">Embed</h3>
                    <button type="button" @click="closeEmbed()" class="grid h-9 w-9 place-items-center rounded-xl border border-white/10 text-zinc-400 transition hover:bg-white/10 hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
                <div class="rounded-2xl border border-white/10 bg-zinc-950/80 p-4">
                    <code class="block break-all text-xs leading-6 text-emerald-200" x-text="embedCode"></code>
                </div>
                <div class="mt-4 flex gap-3">
                    <button type="button" @click="copyEmbed()" class="inline-flex h-10 items-center gap-2 rounded-xl border border-emerald-300/30 bg-emerald-300/[0.08] px-4 text-sm font-bold text-emerald-100 transition hover:bg-emerald-300/[0.16]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span x-text="embedCopied ? '{{ __("Скопійовано!") }}' : '{{ __("Копіювати код") }}'"></span>
                    </button>
                    <button type="button" @click="closeEmbed()" class="inline-flex h-10 items-center rounded-xl border border-white/10 px-4 text-sm font-bold text-zinc-300 transition hover:bg-white/5">
                        {{ __('Закрити') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
