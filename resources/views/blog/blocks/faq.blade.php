@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $items = is_array($d['items'] ?? null) ? $d['items'] : [];
@endphp
@if($items !== [])
    <div class="rounded-3xl border border-white/10 bg-zinc-950/50 p-6 sm:p-8" x-data="{ open: null }">
        <h3 class="text-lg font-bold text-white">{{ __('blog.faq_block_title') }}</h3>
        <div class="mt-4 divide-y divide-white/10">
            @foreach($items as $i => $item)
                @if(is_array($item))
                    @php
                        $q = $locale === 'en'
                            ? (trim((string) ($item['question_en'] ?? '')) ?: trim((string) ($item['question_uk'] ?? '')))
                            : (trim((string) ($item['question_uk'] ?? '')) ?: trim((string) ($item['question_en'] ?? '')));
                        $a = $locale === 'en'
                            ? (trim((string) ($item['answer_en'] ?? '')) ?: trim((string) ($item['answer_uk'] ?? '')))
                            : (trim((string) ($item['answer_uk'] ?? '')) ?: trim((string) ($item['answer_en'] ?? '')));
                    @endphp
                    @if($q !== '' && $a !== '')
                        <div class="py-3 first:pt-0">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 text-left text-sm font-semibold text-white transition hover:text-emerald-100"
                                @click="open = open === {{ (int) $i }} ? null : {{ (int) $i }}"
                                :aria-expanded="(open === {{ (int) $i }}).toString()"
                            >
                                <span class="min-w-0">{{ $q }}</span>
                                <span class="shrink-0 text-emerald-400 transition" :class="open === {{ (int) $i }} ? 'rotate-180' : ''" aria-hidden="true">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                                </span>
                            </button>
                            <div
                                x-show="open === {{ (int) $i }}"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="mt-2 text-sm leading-relaxed text-zinc-400"
                            >
                                <div class="prose prose-invert prose-sm max-w-none text-zinc-400 prose-p:my-1">{!! $a !!}</div>
                            </div>
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </div>
@endif
