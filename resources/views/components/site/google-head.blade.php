@php
    $settings = app(\App\Services\SiteSettings::class);
    $gscRaw   = trim((string) $settings->string('seo.gsc_verification', ''));
    $gaId     = trim((string) $settings->string('seo.ga_id', ''));
    $gtmId    = trim((string) $settings->string('seo.gtm_id', ''));

    $gscToken = $gscRaw;
    if ($gscRaw !== '' && preg_match('/content\s*=\s*"([^"]+)"/i', $gscRaw, $m)) {
        $gscToken = $m[1];
    }
@endphp

@if($gscToken)
<meta name="google-site-verification" content="{{ $gscToken }}">
@endif

@if($gtmId)
{{-- Google Tag Manager --}}
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
@endif

@if($gaId)
{{-- Google Analytics 4 --}}
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', @json($gaId), { 'anonymize_ip': true });
</script>
@endif
