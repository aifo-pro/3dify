@php
    $raw = $body ?? '';
    /** Якщо в тексті є HTML-теги — рендеримо як HTML (листи з адмінки). Інакше — plain text + nl2br. */
    $looksHtml = is_string($raw) && preg_match('/<[a-z][\s\S]*>/i', trim($raw));
@endphp
@if ($looksHtml)
    {!! $raw !!}
@else
    {!! nl2br(e($raw)) !!}
@endif
