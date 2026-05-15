@php
    $d = $block->data ?? [];
    $sizes = ['sm' => 'h-4', 'md' => 'h-10', 'lg' => 'h-20', 'xl' => 'h-32'];
    $class = $sizes[$d['size'] ?? 'md'] ?? 'h-10';
@endphp
<div class="{{ $class }}" aria-hidden="true"></div>
