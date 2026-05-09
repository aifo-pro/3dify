<x-layouts.marketplace
    :seoTitle="$seoTitle ?? null"
    :seoDescription="$seoDescription ?? null">

    <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="mb-6 flex items-center gap-2 text-xs text-zinc-500">
            <a href="{{ route('home') }}" class="transition hover:text-emerald-300">{{ __('Головна') }}</a>
            <span class="text-zinc-700">/</span>
            <span class="text-zinc-400">{{ $page->title }}</span>
        </nav>

        {{-- Hero --}}
        <header class="rounded-3xl border border-white/10 bg-gradient-to-br from-emerald-400/10 via-zinc-900/60 to-zinc-950 p-8 shadow-2xl shadow-emerald-500/10 sm:p-10">
            <x-ui.badge>{{ __('Інформація') }}</x-ui.badge>
            <h1 class="mt-4 text-3xl font-black text-white sm:text-4xl lg:text-5xl">{{ $page->title }}</h1>
            @if($page->subtitle)
                <p class="mt-4 max-w-3xl text-base leading-7 text-zinc-300 sm:text-lg">{{ $page->subtitle }}</p>
            @endif
            <p class="mt-5 text-xs text-zinc-500">
                {{ __('Останнє оновлення') }}: {{ $page->updated_at?->translatedFormat('d F Y') ?? '—' }}
            </p>
        </header>

        <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_260px]">
            {{-- Main content --}}
            <article class="rounded-3xl border border-white/10 bg-white/[0.03] p-6 sm:p-8 lg:p-10">
                <div class="legal-content text-zinc-300">
                    {!! $page->body !!}
                </div>
            </article>

            {{-- Sidebar nav --}}
            @if($available->isNotEmpty())
                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-5">
                        <h3 class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">{{ __('Інші сторінки') }}</h3>
                        <nav class="mt-4 grid gap-1">
                            @foreach($available as $item)
                                <a href="{{ route('pages.show', $item->slug) }}"
                                   class="rounded-xl px-3 py-2 text-sm transition
                                          {{ $item->slug === $page->slug
                                                ? 'bg-emerald-400/15 text-emerald-100 font-semibold'
                                                : 'text-zinc-300 hover:bg-white/[0.05] hover:text-white' }}">
                                    {{ $item->title }}
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    <div class="mt-4 rounded-3xl border border-white/10 bg-gradient-to-br from-emerald-400/10 to-zinc-950 p-5">
                        <h3 class="text-sm font-semibold text-white">{{ __('Не знайшли відповідь?') }}</h3>
                        <p class="mt-2 text-xs leading-6 text-zinc-400">
                            {{ __('Напишіть нашій команді — відповідаємо протягом 24 годин у робочі дні.') }}
                        </p>
                        <a href="mailto:support@3dify.dev"
                           class="mt-3 inline-flex items-center gap-2 rounded-xl bg-emerald-400 px-3 py-2 text-xs font-bold text-zinc-950 transition hover:bg-emerald-300">
                            support@3dify.dev
                        </a>
                    </div>
                </aside>
            @endif
        </div>
    </section>

    @push('head')
        <style>
            .legal-content { font-size: 1rem; line-height: 1.75; }
            .legal-content > *:first-child { margin-top: 0; }
            .legal-content h2 {
                margin-top: 2rem;
                margin-bottom: .75rem;
                color: #fff;
                font-size: 1.5rem;
                font-weight: 700;
                letter-spacing: -0.01em;
                padding-left: .75rem;
                border-left: 3px solid rgb(52 211 153);
            }
            .legal-content h3 {
                margin-top: 1.5rem;
                margin-bottom: .5rem;
                color: #fff;
                font-size: 1.2rem;
                font-weight: 700;
            }
            .legal-content p { margin: .75rem 0; color: #d4d4d8; }
            .legal-content strong { color: #fff; font-weight: 600; }
            .legal-content a {
                color: rgb(110 231 183);
                text-decoration: none;
                border-bottom: 1px dashed rgba(110,231,183,.4);
                transition: color .15s, border-color .15s;
            }
            .legal-content a:hover { color: rgb(167 243 208); border-bottom-color: rgb(167 243 208); }
            .legal-content ul, .legal-content ol { margin: .75rem 0; padding-left: 1.5rem; }
            .legal-content ul { list-style: disc; }
            .legal-content ol { list-style: decimal; }
            .legal-content li { margin: .35rem 0; color: #d4d4d8; }
            .legal-content li::marker { color: rgb(52 211 153); }
            .legal-content blockquote {
                margin: 1rem 0;
                padding: .75rem 1rem;
                border-left: 3px solid rgb(52 211 153);
                background: rgba(255,255,255,.03);
                color: #d4d4d8;
                border-radius: .5rem;
            }
            .legal-content code {
                background: rgb(24 24 27);
                color: rgb(167 243 208);
                padding: .125rem .375rem;
                border-radius: .25rem;
                font-size: .9em;
            }
            .legal-content hr { border: 0; border-top: 1px solid rgba(255,255,255,.1); margin: 2rem 0; }
            .legal-content details {
                margin: .75rem 0;
                padding: .9rem 1.1rem;
                border-radius: .9rem;
                border: 1px solid rgba(255,255,255,.08);
                background: rgba(255,255,255,.03);
                transition: border-color .2s, background .2s;
            }
            .legal-content details[open] {
                border-color: rgba(16,185,129,.35);
                background: rgba(16,185,129,.05);
            }
            .legal-content details > summary {
                cursor: pointer;
                color: #fff;
                font-weight: 600;
                list-style: none;
                position: relative;
                padding-right: 2rem;
            }
            .legal-content details > summary::-webkit-details-marker { display: none; }
            .legal-content details > summary::after {
                content: '+';
                position: absolute;
                right: 0;
                top: 0;
                font-weight: 700;
                color: rgb(110 231 183);
                font-size: 1.25rem;
                line-height: 1;
                transition: transform .2s ease;
            }
            .legal-content details[open] > summary::after { content: '−'; }
            .legal-content details > p { margin-top: .6rem; }
        </style>
    @endpush
</x-layouts.marketplace>
