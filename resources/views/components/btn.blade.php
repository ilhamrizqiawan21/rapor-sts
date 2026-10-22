@props([
    'variant' => 'pri', // pri, suc, dan, war, inf, sec
    'href' => null,
    'type' => 'button',
])

@php
    $variantClass = match($variant) {
        'primary', 'pri' => 'b-pri',
        'success', 'suc' => 'b-suc',
        'danger', 'dan' => 'b-dan',
        'warning', 'war' => 'b-war',
        'info', 'inf' => 'b-inf',
        'secondary', 'sec' => 'b-sec',
        default => 'b-pri',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn ' . $variantClass]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => 'btn ' . $variantClass]) }}>
        {{ $slot }}
    </button>
@endif
