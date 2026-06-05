@props([
    'faqs' => [],          // array<int, ['question' => string, 'answer' => string]>
    'title' => null,
    'eyebrow' => null,
    'description' => null,
])

@php
    $faqs = collect($faqs)->filter(fn ($f) => filled($f['question'] ?? null) && filled($f['answer'] ?? null))->values()->all();
@endphp

@if(count($faqs) > 0)
    {{-- FAQPage JSON-LD — feeds Google AI Overviews / SGE short answers --}}
    @push('head')
        {!! \App\Support\Seo::jsonLd(\App\Support\Seo::faqPage($faqs)) !!}
    @endpush

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8" aria-labelledby="faq-heading">
        {{-- 3-col grid with span 1 / span 2 (standard Tailwind, no JIT arbitrary values) --}}
        <div class="grid gap-10 lg:grid-cols-3 lg:gap-16">

            {{-- Left: heading + supporting copy (1/3) --}}
            <div class="min-w-0 lg:col-span-1">
                <div class="lg:sticky lg:top-24">
                    @if($eyebrow)
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-400">{{ $eyebrow }}</p>
                    @endif
                    <h2 id="faq-heading" class="mt-3 text-3xl font-black leading-tight tracking-tight text-white sm:text-4xl lg:text-5xl">
                        {{ $title ?? __('Часті запитання') }}
                    </h2>
                    <p class="mt-4 text-base leading-7 text-zinc-400">
                        {{ $description ?? __('Швидкі відповіді на найпоширеніші питання про 3Dify, формати файлів, оплату й завантаження.') }}
                    </p>
                    <a href="{{ route('pages.show', 'faq') }}" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-emerald-300 transition hover:text-emerald-200">
                        {{ __('Усі питання') }}
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>

            {{-- Right: accordion (2/3) --}}
            <div class="min-w-0 divide-y divide-white/[0.07] lg:col-span-2">
                @foreach($faqs as $i => $faq)
                    <details class="group py-2" @if($i === 0) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-left text-base font-bold text-white transition hover:text-emerald-100 sm:text-lg">
                            <span>{{ $faq['question'] }}</span>
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-white/10 bg-white/[0.04] text-zinc-400 transition group-hover:border-emerald-300/40 group-hover:text-emerald-300 group-open:bg-emerald-400/15 group-open:text-emerald-300">
                                <svg class="h-4 w-4 transition group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </span>
                        </summary>
                        <div class="pb-5 pr-12 text-sm leading-relaxed text-zinc-400 sm:text-base sm:leading-7">{{ $faq['answer'] }}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
