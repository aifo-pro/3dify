@props(['siteName' => '3Dify'])

<footer class="mt-24 border-t border-white/10 bg-zinc-950/80">
    <div class="h-px bg-gradient-to-r from-transparent via-emerald-300/30 to-transparent"></div>

    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-[1.5fr_repeat(3,minmax(0,1fr))] lg:gap-10 lg:px-8">
        {{-- Brand --}}
        <div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 text-lg font-black text-white">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-400 text-zinc-950 shadow-lg shadow-emerald-500/20">3D</span>
                {{ $siteName }}
            </a>
            <p class="mt-5 max-w-sm text-sm leading-6 text-zinc-400">{{ __('Преміальний marketplace для STL, OBJ, GLB, GLTF, ZIP та 3MF файлів: публікуйте, купуйте й друкуйте моделі без хаосу в архівах.') }}</p>
            <div class="mt-6 flex gap-2">
                <a href="#" aria-label="X" class="grid h-10 w-10 place-items-center rounded-full border border-white/10 bg-white/[0.04] text-xs font-bold text-zinc-400 transition hover:border-emerald-300/40 hover:bg-emerald-300/10 hover:text-emerald-100">X</a>
                <a href="#" aria-label="GitHub" class="grid h-10 w-10 place-items-center rounded-full border border-white/10 bg-white/[0.04] text-xs font-bold text-zinc-400 transition hover:border-emerald-300/40 hover:bg-emerald-300/10 hover:text-emerald-100">GH</a>
                <a href="#" aria-label="Telegram" class="grid h-10 w-10 place-items-center rounded-full border border-white/10 bg-white/[0.04] text-xs font-bold text-zinc-400 transition hover:border-emerald-300/40 hover:bg-emerald-300/10 hover:text-emerald-100">TG</a>
            </div>
        </div>

        {{-- Marketplace --}}
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-zinc-300">{{ __('Marketplace') }}</h3>
            <div class="mt-5 grid gap-3">
                <x-ui.footer-link :href="route('products.index')">{{ __('Каталог') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('products.index').'#categories'">{{ __('Категорії') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('authors.index')">{{ __('Автори') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('products.index', ['free' => 1])">{{ __('Безкоштовні моделі') }}</x-ui.footer-link>
            </div>
        </div>

        {{-- Authors --}}
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-zinc-300">{{ __('Авторам') }}</h3>
            <div class="mt-5 grid gap-3">
                <x-ui.footer-link :href="auth()->check() ? route('author.products.create') : route('register')">{{ __('Продати модель') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'publishing-rules')">{{ __('Правила публікації') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'copyright')">{{ __('Copyright') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'terms')">{{ __('Terms') }}</x-ui.footer-link>
            </div>
        </div>

        {{-- Support --}}
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-zinc-300">{{ __('Підтримка') }}</h3>
            <div class="mt-5 grid gap-3">
                <x-ui.footer-link :href="route('pages.show', 'contact')">{{ __('Contact') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'faq')">{{ __('FAQ') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('pages.show', 'privacy')">{{ __('Privacy') }}</x-ui.footer-link>
                <x-ui.footer-link :href="route('locale.switch', app()->getLocale() === 'uk' ? 'en' : 'uk')">{{ __('Мова') }}: {{ app()->getLocale() === 'uk' ? 'Українська' : 'English' }}</x-ui.footer-link>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 md:grid-cols-[1fr_minmax(280px,400px)] md:items-center lg:px-8">
            <div>
                <h3 class="text-sm font-bold text-white">{{ __('Підпишіться на оновлення') }}</h3>
                <p class="mt-1 text-xs text-zinc-400">{{ __('Найкращі моделі тижня, нові автори, акції та поради друкарів.') }}</p>
            </div>
            <form method="POST" action="{{ route('newsletter.subscribe') }}" class="flex items-stretch gap-2">
                @csrf
                <input type="hidden" name="source" value="footer">
                <input type="email" name="email" required placeholder="you@example.com" class="h-10 w-full rounded-xl border border-white/10 bg-zinc-950/60 px-3 text-sm text-white placeholder:text-zinc-500 focus:border-emerald-300 focus:ring-1 focus:ring-emerald-300/40">
                <button class="inline-flex h-10 shrink-0 items-center rounded-xl bg-emerald-400 px-4 text-sm font-bold text-zinc-950 hover:bg-emerald-300">{{ __('Підписатися') }}</button>
            </form>
            @if(session('status'))
                <p class="md:col-span-2 text-xs text-emerald-300">{{ session('status') }}</p>
            @endif
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-6 text-xs text-zinc-500 sm:flex-row sm:px-6 lg:px-8">
            <p>© {{ date('Y') }} {{ $siteName }}. {{ __('Усі права захищено.') }}</p>
            <p class="text-zinc-600">{{ __('Зроблено для мейкерів та 3D-друкарів.') }}</p>
        </div>
    </div>
</footer>
