@php
    $name = $term->localized('name');
    if ($type === 'category') {
        $seoTitle = ($term->localized('seo_title') ?: $name).' · '.__('blog.meta.index_title');
        $seoDescription = $term->localized('seo_description') ?: $term->localized('description') ?: $name;
        $canonical = route('blog.category', $term);
    } else {
        $seoTitle = $name.' · '.__('blog.meta.index_title');
        $seoDescription = __('blog.term_tag_description', ['tag' => $name]);
        $canonical = route('blog.tag', $term);
    }
@endphp
<x-layouts.marketplace
    :seo-title="$seoTitle"
    :seo-description="$seoDescription"
    :seo-canonical="$canonical"
>
    {{-- Header --}}
    <div class="border-b border-white/[0.06] bg-zinc-950 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="mb-5 flex items-center gap-x-2 text-xs text-zinc-500" aria-label="Breadcrumb">
                <a href="{{ route('blog.index') }}" class="transition hover:text-emerald-300">{{ __('blog.breadcrumb_blog') }}</a>
                <span class="text-zinc-700">›</span>
                <span class="text-zinc-400">{{ $name }}</span>
            </nav>
            <span class="inline-flex items-center rounded-full border border-emerald-400/30 bg-emerald-400/[0.08] px-3 py-1 text-[11px] font-black uppercase tracking-widest text-emerald-300">{{ $type === 'category' ? __('blog.label_category') : __('blog.label_tag') }}</span>
            <h1 class="mt-4 text-3xl font-black text-white sm:text-4xl lg:text-5xl">{{ $name }}</h1>
            @if($seoDescription && $type === 'category')
                <p class="mt-3 max-w-2xl text-zinc-400">{{ $seoDescription }}</p>
            @endif
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-14 lg:px-8">
        @if($posts->isEmpty())
            <x-ui.empty-state :title="__('blog.term_empty_title')" :description="__('blog.term_empty_hint')" />
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($posts as $post)
                    @include('marketplace.blog.partials.card', ['post' => $post])
                @endforeach
            </div>
            @if($posts->hasPages())
                <div class="mt-8">{{ $posts->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.marketplace>
