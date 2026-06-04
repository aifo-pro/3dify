@props(['label' => null])

@php
    $settings = app(\App\Services\SiteSettings::class);
    $botUsername = trim($settings->string('auth.telegram_bot_username') ?: (string) config('services.telegram.bot_username'));
    $botUsername = ltrim($botUsername, '@');
@endphp

@if($botUsername)
    <div class="overflow-hidden rounded-2xl border border-sky-300/20 bg-sky-400/[0.06] px-4 py-3 text-center shadow-inner shadow-sky-500/5">
        @if($label)
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.16em] text-sky-100/70">{{ $label }}</p>
        @endif
        <div class="flex min-h-10 items-center justify-center">
            <script async src="https://telegram.org/js/telegram-widget.js?22"
                data-telegram-login="{{ $botUsername }}"
                data-size="large"
                data-radius="14"
                data-auth-url="{{ route('auth.telegram') }}"
                data-request-access="write"></script>
        </div>
    </div>
@else
    <div class="rounded-2xl border border-dashed border-white/10 bg-zinc-950/50 px-4 py-3 text-center text-sm text-zinc-500">
        {{ __('Telegram login буде доступний після налаштування бота.') }}
    </div>
@endif
