@php
    $locale = app()->getLocale();
    $d = $block->data ?? [];
    $title = $locale === 'en'
        ? (trim((string) ($d['title_en'] ?? '')) ?: trim((string) ($d['title_uk'] ?? '')))
        : (trim((string) ($d['title_uk'] ?? '')) ?: trim((string) ($d['title_en'] ?? '')));
    $level = (int) ($d['level'] ?? 2);
    $tag = $level === 3 ? 'h3' : 'h2';
    $id = $headingIds[$block->id] ?? \App\Support\BlogBlockPlainText::headingFragmentId($block, $d);
@endphp
@if($title !== '')
    <{{ $tag }} id="{{ $id }}" class="scroll-mt-28 text-[1.65rem] font-bold leading-snug tracking-tight text-white sm:text-[1.85rem] lg:text-[2.05rem]">{{ $title }}</{{ $tag }}>
@endif
