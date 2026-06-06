@props(['mediaItems' => [], 'viewer' => null, 'productTitle' => ''])

@php
    $seoImages = collect($mediaItems)
        ->filter(fn ($item) => ($item['type'] ?? null) === 'image' && filled($item['url'] ?? null))
        ->values();
    $primarySeoImage = $seoImages->first()['url'] ?? '';
    $primarySeoAlt = $seoImages->first()['alt'] ?? $productTitle;
@endphp

<div
    x-data="{
        media: @js($mediaItems),
        currentIndex: 0,
        lightboxOpen: false,
        viewerOpen: false,
        touchStartX: null,

        current() { return this.media[this.currentIndex] || null; },
        isImage(item) { return item && item.type === 'image'; },
        isViewer(item) { return item && (item.type === 'viewer' || item.type === 'model' || item.type === '3d'); },

        next() {
            if (this.media.length > 1) {
                this.currentIndex = (this.currentIndex + 1) % this.media.length;
                this.afterSelect();
            }
        },
        prev() {
            if (this.media.length > 1) {
                this.currentIndex = (this.currentIndex - 1 + this.media.length) % this.media.length;
                this.afterSelect();
            }
        },
        select(index) {
            this.currentIndex = index;
            this.afterSelect();
        },
        afterSelect() {
            if (this.isViewer(this.current())) {
                this.$nextTick(() => window.dispatchEvent(new CustomEvent('init-model-viewers')));
            }
        },

        open() {
            const item = this.current();
            if (this.isViewer(item)) {
                this.openViewer();
            } else if (this.isImage(item)) {
                this.openLightbox();
            }
        },
        openLightbox() {
            if (!this.isImage(this.current())) return;
            this.lightboxOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        openViewer() {
            this.viewerOpen = true;
            document.body.classList.add('overflow-hidden');
            this.$nextTick(() => window.dispatchEvent(new CustomEvent('init-model-viewers')));
        },
        closeLightbox() {
            this.lightboxOpen = false;
            document.body.classList.remove('overflow-hidden');
        },
        closeViewer() {
            this.viewerOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        get imageItems() { return this.media.filter(item => this.isImage(item)); },
        get imageCount() { return this.imageItems.length; },
        get currentImageIndex() {
            const item = this.current();
            if (!this.isImage(item)) return 0;
            return this.imageItems.findIndex(i => i.url === item.url);
        },

        lightboxNext() {
            const images = this.imageItems;
            if (images.length < 2) return;
            const nextIdx = (this.currentImageIndex + 1) % images.length;
            const globalIdx = this.media.findIndex(m => m.url === images[nextIdx].url);
            if (globalIdx >= 0) this.currentIndex = globalIdx;
        },
        lightboxPrev() {
            const images = this.imageItems;
            if (images.length < 2) return;
            const prevIdx = (this.currentImageIndex - 1 + images.length) % images.length;
            const globalIdx = this.media.findIndex(m => m.url === images[prevIdx].url);
            if (globalIdx >= 0) this.currentIndex = globalIdx;
        },

        handleSwipe(event) {
            if (this.touchStartX === null) return;
            const diff = this.touchStartX - event.changedTouches[0].clientX;
            if (Math.abs(diff) > 45) {
                diff > 0 ? this.lightboxNext() : this.lightboxPrev();
            }
            this.touchStartX = null;
        },

        // Called when the 3D viewer can't get a WebGL context: drop the viewer
        // slide and fall back to images so the page still works out of the box.
        dropViewer() {
            const images = this.media.filter(item => this.isImage(item));
            if (images.length === 0) return; // nothing to fall back to — keep the notice
            this.media = images;
            this.currentIndex = 0;
        },
    }"
    x-init="$nextTick(() => window.dispatchEvent(new CustomEvent('init-model-viewers')))"
    @viewer-webgl-unavailable="dropViewer()"
    @keydown.escape.window="lightboxOpen ? closeLightbox() : (viewerOpen ? closeViewer() : null)"
    @keydown.arrow-right.window="lightboxOpen ? lightboxNext() : next()"
    @keydown.arrow-left.window="lightboxOpen ? lightboxPrev() : prev()"
    class="grid gap-4"
>
    {{-- ===== MAIN GALLERY CONTAINER ===== --}}
    <div class="relative w-full overflow-hidden rounded-3xl" style="aspect-ratio: 4/3; max-height: 620px; background: #05070a;">

        {{-- Image display --}}
        <template x-if="isImage(current())">
            <button
                type="button"
                @click="openLightbox()"
                class="flex h-full w-full cursor-zoom-in items-center justify-center"
                style="background: #05070a;"
                aria-label="{{ __('Відкрити фото') }}"
            >
                <img
                    src="{{ $primarySeoImage }}"
                    alt="{{ $primarySeoAlt }}"
                    :src="current().url"
                    :alt="current().alt"
                    width="1200"
                    height="900"
                    fetchpriority="high"
                    decoding="async"
                    class="h-full w-full object-contain"
                    style="background: #05070a;"
                >
            </button>
        </template>

        {{-- 3D Viewer display — persistent (not re-mounted) so the WebGL scene
             survives slide switches; hidden via x-show when another slide is active. --}}
        @if($viewer && ($viewer['available'] ?? false))
            <div x-show="isViewer(current())" class="absolute inset-0 z-[5]">
                <x-product.model-viewer :viewer="$viewer" :title="$productTitle" :fill="true" />
            </div>
        @endif

        {{-- Navigation arrows --}}
        <template x-if="media.length > 1">
            <div class="pointer-events-none absolute inset-y-0 left-0 right-0 z-10 flex items-center justify-between px-3 sm:px-5">
                <button
                    type="button"
                    @click.stop="prev()"
                    class="pointer-events-auto grid h-11 w-11 place-items-center rounded-full border border-white/10 bg-zinc-950/75 text-white shadow-xl shadow-black/30 backdrop-blur transition hover:border-emerald-300/40 hover:bg-emerald-300/15"
                    aria-label="{{ __('Попереднє') }}"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button
                    type="button"
                    @click.stop="next()"
                    class="pointer-events-auto grid h-11 w-11 place-items-center rounded-full border border-white/10 bg-zinc-950/75 text-white shadow-xl shadow-black/30 backdrop-blur transition hover:border-emerald-300/40 hover:bg-emerald-300/15"
                    aria-label="{{ __('Наступне') }}"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </template>

        {{-- Counter badge --}}
        <div class="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-2 rounded-full border border-white/10 bg-zinc-950/70 px-3 py-1.5 text-xs font-bold text-zinc-200 backdrop-blur">
            <span x-text="currentIndex + 1"></span>
            <span class="text-zinc-500">/</span>
            <span x-text="media.length"></span>
        </div>
    </div>

    {{-- ===== THUMBNAILS ===== --}}
    <template x-if="media.length > 1">
        <div class="flex flex-wrap gap-2 sm:gap-3">
            <template x-for="(item, index) in media" :key="'thumb-' + index">
                <button
                    type="button"
                    @click="select(index)"
                    class="relative shrink-0 overflow-hidden rounded-2xl border-2 transition"
                    :class="currentIndex === index ? 'border-emerald-400 ring-2 ring-emerald-400/25' : 'border-white/10 hover:border-white/25'"
                    style="width: 96px; height: 76px;"
                >
                    <img
                        x-show="isImage(item)"
                        :src="item.url"
                        :alt="item.alt"
                        loading="lazy"
                        class="h-full w-full object-cover"
                    >
                    <span
                        x-show="isViewer(item)"
                        class="grid h-full w-full place-items-center bg-[radial-gradient(circle_at_center,rgba(52,211,153,.18),transparent_55%),#09090b] text-emerald-100"
                    >
                        <span class="text-xs font-black">3D</span>
                    </span>
                </button>
            </template>
        </div>
    </template>

    @if($seoImages->isNotEmpty())
        <noscript>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($seoImages as $image)
                    <img
                        src="{{ $image['url'] }}"
                        alt="{{ $image['alt'] ?? $productTitle }}"
                        loading="lazy"
                        width="1200"
                        height="900"
                        class="rounded-2xl border border-white/10 bg-zinc-950 object-contain"
                    >
                @endforeach
            </div>
        </noscript>
    @endif

    {{-- ===== FULLSCREEN LIGHTBOX (images only) ===== --}}
    <div
        x-show="lightboxOpen"
        x-cloak
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[9999] flex flex-col items-center justify-center"
        style="background: rgba(0,0,0,.94);"
        role="dialog"
        aria-modal="true"
        @click.self="closeLightbox()"
        @touchstart.passive="touchStartX = $event.changedTouches[0].clientX"
        @touchend.passive="handleSwipe($event)"
    >
        {{-- Close button --}}
        <button
            type="button"
            @click="closeLightbox()"
            class="absolute right-4 top-4 z-20 grid h-12 w-12 place-items-center rounded-full border border-white/15 bg-zinc-900/80 text-white backdrop-blur transition hover:bg-white/10"
            aria-label="{{ __('Закрити') }}"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>

        {{-- Counter --}}
        <div class="absolute left-1/2 top-4 z-20 -translate-x-1/2 rounded-full border border-white/10 bg-zinc-950/70 px-3 py-1.5 text-xs font-bold text-zinc-200 backdrop-blur">
            <span x-text="currentImageIndex + 1"></span>
            <span class="text-zinc-500">/</span>
            <span x-text="imageCount"></span>
        </div>

        {{-- Prev arrow --}}
        <template x-if="imageCount > 1">
            <button
                type="button"
                @click.stop="lightboxPrev()"
                class="absolute left-4 top-1/2 z-20 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full border border-white/15 bg-zinc-900/80 text-white backdrop-blur transition hover:bg-white/10"
                aria-label="{{ __('Попереднє фото') }}"
            >
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </button>
        </template>

        {{-- Image --}}
        <img
            :src="current()?.url"
            :alt="current()?.alt"
            class="object-contain"
            style="max-width: 92vw; max-height: 86vh; width: auto; height: auto;"
        >

        {{-- Next arrow --}}
        <template x-if="imageCount > 1">
            <button
                type="button"
                @click.stop="lightboxNext()"
                class="absolute right-4 top-1/2 z-20 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full border border-white/15 bg-zinc-900/80 text-white backdrop-blur transition hover:bg-white/10"
                aria-label="{{ __('Наступне фото') }}"
            >
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </template>

        {{-- Lightbox thumbnails --}}
        <template x-if="imageCount > 1">
            <div class="absolute bottom-4 left-1/2 flex max-w-[90vw] -translate-x-1/2 gap-2 overflow-x-auto rounded-2xl border border-white/10 bg-zinc-950/70 p-2 backdrop-blur [scrollbar-width:thin]">
                <template x-for="(image, idx) in imageItems" :key="'lb-thumb-' + idx">
                    <button
                        type="button"
                        @click.stop="select(media.findIndex(m => m.url === image.url))"
                        class="shrink-0 overflow-hidden rounded-xl border-2 transition"
                        :class="currentImageIndex === idx ? 'border-emerald-400' : 'border-transparent hover:border-white/30'"
                        style="width: 64px; height: 48px;"
                    >
                        <img :src="image.url" :alt="image.alt" loading="lazy" class="h-full w-full object-cover">
                    </button>
                </template>
            </div>
        </template>
    </div>

</div>
