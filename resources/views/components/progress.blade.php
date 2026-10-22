@props([
    'value' => 0,
    'color' => null,
    'showText' => false,
])

@php
    $clampedValue = max(0, min(100, (float) $value));
@endphp

<div {{ $attributes->merge(['class' => 'bar']) }}>
    <span style="width: {{ $clampedValue }}%@if($color); background: {{ $color }}@endif">
        @if($showText)
            {{ $clampedValue }}%
        @endif
    </span>
</div>
