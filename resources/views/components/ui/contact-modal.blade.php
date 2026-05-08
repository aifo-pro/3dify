<div
    x-data="contactModal()"
    x-cloak
    x-show="open"
    @keydown.escape.window="close()"
    @open-contact-modal.window="openWith($event.detail || {})"
    class="fixed inset-0 z-[9999] grid place-items-center px-4 py-6"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity @click="close()" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        class="relative z-10 w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-zinc-950/95 shadow-2xl shadow-black/60 backdrop-blur-xl"
    >
        <div class="border-b border-white/10 px-6 py-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-300/25 bg-emerald-300/[0.08] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-emerald-200">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        {{ __('Контакт') }}
                    </div>
                    <h2 class="mt-2 text-xl font-black text-white">{{ __('Написати автору') }}</h2>
                    <p class="mt-1 text-xs leading-5 text-zinc-400" x-text="author ? '{{ __('Отримувач') }}: ' + author : '{{ __('Лист буде відправлено напряму автору. Ваш email видно лише адресату.') }}'"></p>
                </div>
                <button type="button" @click="close()" class="grid h-8 w-8 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-300 hover:bg-white/[0.08]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        <form method="POST" :action="action" class="grid gap-4 px-6 py-5">
            @csrf
            <label class="grid gap-1.5">
                <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Тема') }} <span class="text-rose-300">*</span></span>
                <input type="text" name="subject" required maxlength="160" placeholder="{{ __('Питання щодо моделі...') }}" class="h-10 rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
            </label>
            <label class="grid gap-1.5">
                <span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ __('Повідомлення') }} <span class="text-rose-300">*</span></span>
                <textarea name="message" rows="5" required minlength="10" maxlength="5000" placeholder="{{ __('Опишіть ваше питання...') }}" class="rounded-xl border border-white/10 bg-zinc-950/60 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40"></textarea>
            </label>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="close()" class="rounded-xl border border-white/10 bg-white/[0.04] px-4 py-2 text-xs font-semibold text-zinc-300 hover:bg-white/[0.08]">{{ __('Скасувати') }}</button>
                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-emerald-400 px-4 text-xs font-bold text-zinc-950 shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-300">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    {{ __('Надіслати') }}
                </button>
            </div>
        </form>
    </div>

    <script>
        if (typeof window.contactModal === 'undefined') {
            window.contactModal = function () {
                return {
                    open: false,
                    action: '',
                    author: '',
                    openWith(detail) {
                        this.action = detail.action || '';
                        this.author = detail.author || '';
                        this.open = true;
                    },
                    close() { this.open = false; },
                };
            };
        }
    </script>
</div>
