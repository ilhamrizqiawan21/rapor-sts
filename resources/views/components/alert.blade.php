@props([
    'variant' => 'war', // war, inf, lt, lav
])

@php
    $variantClass = match($variant) {
        'warning', 'war' => 'a-war',
        'info', 'inf' => 'a-inf',
        'light', 'lt' => 'a-lt',
        'lavender', 'lav' => 'a-lav',
        default => 'a-war',
    };
@endphp

<div {{ $attributes->merge(['class' => 'alert ' . $variantClass]) }}>
    {{ $slot }}
</div>
