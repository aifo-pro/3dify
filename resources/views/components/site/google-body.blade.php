@php
    $gtmId = trim((string) app(\App\Services\SiteSettings::class)->string('seo.gtm_id', ''));
@endphp

@if($gtmId)
{{-- Google Tag Manager (noscript fallback) --}}
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
