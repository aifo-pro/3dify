@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $items = is_array($d['items'] ?? null) ? $d['items'] : [];
@endphp
@if($items !== [])
    <div class="rounded-[1.75rem] border border-white/[0.08] bg-zinc-950/55 p-7 sm:p-9" x-data="{ open: null }">
        <h3 class="text-xl font-bold tracking-tight text-white">{{ __('blog.faq_block_title') }}</h3>
        <div class="mt-5 divide-y divide-white/10">
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
                        <div class="py-4 first:pt-0">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 text-left text-base font-semibold leading-snug text-white transition hover:text-emerald-100"
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
                                class="mt-3 text-base leading-[1.78] text-zinc-300"
                            >
                                <div class="prose prose-invert prose-base max-w-none text-zinc-300 prose-p:my-1">{!! $a !!}</div>
                            </div>
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </div>
@endif
