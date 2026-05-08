@props([
    'status' => 'neutral',
    'size' => 'sm',
    'icon' => true,
    'pulse' => true,
])

@php
    // If user passed a custom slot text, treat it as label override.
    $label = $slot->isEmpty() ? null : trim($slot);
@endphp

<x-ui.status :status="$status" :label="$label" :size="$size" :icon="$icon" :pulse="$pulse" {{ $attributes }} />
