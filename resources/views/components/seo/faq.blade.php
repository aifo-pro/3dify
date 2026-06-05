@props([
    'faqs' => [],          // array<int, ['question' => string, 'answer' => string]>
    'title' => null,
    'eyebrow' => null,
])

@php
    $faqs = collect($faqs)->filter(fn ($f) => filled($f['question'] ?? null) && filled($f['answer'] ?? null))->values()->all();
@endphp

@if(count($faqs) > 0)
    {{-- FAQPage JSON-LD — feeds Google AI Overviews / SGE short answers --}}
    @push('head')
        {!! \App\Support\Seo::jsonLd(\App\Support\Seo::faqPage($faqs)) !!}
    @endpush

    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8" aria-labelledby="faq-heading">
        @if($eyebrow)
            <p class="text-center text-xs font-black uppercase tracking-widest text-emerald-400">{{ $eyebrow }}</p>
        @endif
        <h2 id="faq-heading" class="mt-2 text-center text-2xl font-black text-white sm:text-3xl">{{ $title ?? __('Часті запитання') }}</h2>

        <div class="mt-8 divide-y divide-white/[0.07] overflow-hidden rounded-2xl border border-white/[0.08] bg-zinc-900/50">
            @foreach($faqs as $i => $faq)
                <details class="group" @if($i === 0) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-left text-sm font-bold text-white transition hover:bg-white/[0.03] sm:text-base">
                        <span>{{ $faq['question'] }}</span>
                        <svg class="h-4 w-4 shrink-0 text-zinc-500 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-sm leading-relaxed text-zinc-400">{{ $faq['answer'] }}</div>
                </details>
            @endforeach
        </div>
    </section>
@endif
