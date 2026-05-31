@props(['siteName' => '3Dify'])

<footer class="mt-24 border-t border-white/10 bg-zinc-950/80">
    <div class="h-px bg-gradient-to-r from-transparent via-emerald-300/30 to-transparent"></div>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-5 lg:gap-8 lg:px-8">

        {{-- Brand --}}
        <div class="lg:col-span-2">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 text-lg font-black text-white">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-400 text-zinc-950 shadow-lg shadow-emerald-500/20">3D</span>
                {{ $siteName }}
            </a>
            <p class="mt-4 max-w-xs text-sm leading-6 text-zinc-400">{{ __('Маркетплейс STL, OBJ, GLB та 3MF файлів для 3D-друку. Купуйте, продавайте й друкуйте без зайвих клопотів.') }}</p>
            <div class="mt-5 flex gap-2">
                <a href="#" aria-label="X" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/[0.04] text-xs font-bold text-zinc-400 transition hover:border-emerald-300/40 hover:bg-emerald-300/10 hover:text-emerald-100">X</a>
                <a href="#" aria-label="GitHub" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/[0.04] text-xs font-bold text-zinc-400 transition hover:border-emerald-300/40 hover:bg-emerald-300/10 hover:text-emerald-100">GH</a>
                <a href="#" aria-label="Telegram" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/[0.04] text-xs font-bold text-zinc-400 transition hover:border-emerald-300/40 hover:bg-emerald-300/10 hover:text-emerald-100">TG</a>
            </div>
        </div>

        {{-- Marketplace --}}
        <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.16em] text-zinc-300">Маркетплейс</h3>
            <div class="mt-4 grid gap-2.5">
                <x-ui.footer-link :href="route('products.index')">Каталог моделей</x-ui.footer-link>
                <x-ui.footer-link :href="route('products.index', ['free' => 1])">Безкоштовні</x-ui.footer-link>
                <x-ui.footer-link :href="route('authors.index')">Автори</x-ui.footer-link>
                <x-ui.footer-link :href="route('search')">Пошук</x-ui.footer-link>
                <x-ui.footer-link :href="route('compare')">Порівняти моделі</x-ui.footer-link>
                @auth
                    <x-ui.footer-link :href="route('library')">Моя бібліотека</x-ui.footer-link>
                @endauth
            </div>
        </div>

        {{-- Community --}}
        <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.16em] text-zinc-300">Спільнота</h3>
            <div class="mt-4 grid gap-2.5">
                <x-ui.footer-link :href="route('makes.gallery')">Галерея друків</x-ui.footer-link>
                <x-ui.footer-link :href="route('challenges.index')">Челенджі</x-ui.footer-link>
                <x-ui.footer-link :href="route('leaderboard')">Рейтинг авторів</x-ui.footer-link>
                <x-ui.footer-link :href="route('blog.index')">Блог</x-ui.footer-link>
                @auth
                    <x-ui.footer-link :href="route('referral')">Реферальна програма</x-ui.footer-link>
                @endauth
            </div>
        </div>

        {{-- Info --}}
        <div>
            <h3 class="text-xs font-bold uppercase tracking-[0.16em] text-zinc-300">Інформація</h3>
            <div class="mt-4 grid gap-2.5">
                <x-ui.footer-link :href="auth()->check() ? route('author.products.create') : route('register')">Продати модель</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'publishing-rules')">Правила публікації</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'terms')">Умови використання</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'privacy')">Конфіденційність</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'faq')">FAQ</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'contact')">Контакти</x-ui.footer-link>
                <x-ui.footer-link :href="route('locale.switch', app()->getLocale() === 'uk' ? 'en' : 'uk')">
                    🌐 {{ app()->getLocale() === 'uk' ? 'English' : 'Українська' }}
                </x-ui.footer-link>
            </div>
        </div>
    </div>

    {{-- Newsletter --}}
    <div class="border-t border-white/10">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 md:grid-cols-[1fr_minmax(280px,400px)] md:items-center lg:px-8">
            <div>
                <h3 class="text-sm font-bold text-white">{{ __('Підпишіться на оновлення') }}</h3>
                <p class="mt-1 text-xs text-zinc-400">{{ __('Найкращі моделі тижня, нові автори, акції та поради друкарів.') }}</p>
            </div>
            <form method="POST" action="{{ route('newsletter.subscribe') }}" class="flex items-stretch gap-2">
                @csrf
                <input type="hidden" name="source" value="footer">
                <input type="email" name="email" required placeholder="you@example.com"
                       class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                <button class="inline-flex h-10 shrink-0 items-center rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Підписатися') }}</button>
            </form>
            @if(session('status'))
                <p class="md:col-span-2 text-xs text-emerald-300">{{ session('status') }}</p>
            @endif
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-zinc-500 sm:flex-row sm:px-6 lg:px-8">
            <p>© {{ date('Y') }} {{ $siteName }}. {{ __('Усі права захищено.') }}</p>
            <div class="flex items-center gap-6">
                <a href="{{ route('feed') }}" class="transition hover:text-zinc-300">RSS</a>
                <a href="{{ route('sitemap') }}" class="transition hover:text-zinc-300">Sitemap</a>
                <button type="button" onclick="window.openCookieSettings && window.openCookieSettings()" class="transition hover:text-zinc-300">Cookies</button>
            </div>
            <img src="{{ asset('img/made-in-ukraine.svg') }}" alt="{{ __('Зроблено в Україні') }}" class="h-[34px] w-auto opacity-75 transition hover:opacity-95" loading="lazy">
        </div>
    </div>
</footer>
